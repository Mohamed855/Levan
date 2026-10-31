<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\Dashboard\NotificationsChanged;
use App\Http\Controllers\Shared\DashboardNotificationController;
use App\Http\Middleware\EnsureAdminTwoFactorIsEnforced;
use App\Http\Middleware\Tenancy\EnsureTenantTwoFactorIsEnforced;
use App\Http\Middleware\Tenancy\InitializeTenancyBySession;
use App\Http\Middleware\Tenancy\TenantMaintenance;
use App\Http\Requests\Shared\DashboardNotificationRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Notifications\NotificationDelivery;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Shared\Notifications\DashboardNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class DashboardNotificationFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'tenancy.bootstrappers' => [], 'activitylog.enabled' => false,
            'dashboard-notifications.enabled' => true, 'dashboard-notifications.broadcast' => false,
        ]);
        DB::purge('mysql');
        DB::purge('tenant');
        $this->withoutMiddleware([
            EnsureAdminTwoFactorIsEnforced::class, EnsureTenantTwoFactorIsEnforced::class,
            InitializeTenancyBySession::class, TenantMaintenance::class,
        ]);

        foreach (['mysql' => 'admin_users', 'tenant' => 'users'] as $connection => $tableName) {
            Schema::connection($connection)->create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('password');
                $table->string('status');
                $table->boolean('is_super_admin')->default(false);
                $table->string('global_uid')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
            Schema::connection($connection)->create('notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
        Schema::connection('mysql')->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->text('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::connection('mysql')->create('dashboard_notification_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('scope');
            $table->string('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->text('payload');
            $table->timestamp('delivered_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public static function scopes(): array
    {
        return [['admin'], ['tenant']];
    }

    #[DataProvider('scopes')]
    public function test_feed_counts_follow_reads_and_deletes_and_remain_scoped(string $scope): void
    {
        [$user, $other] = $this->signIn($scope);
        $unread = $this->notice($user);
        $this->notice($user);
        $this->notice($user)->markAsRead();
        $foreign = $this->notice($other);

        $this->assertCounts($scope, 3, 2, 1);
        $this->postJson($this->url($scope, '/'.$foreign->id.'/read'))->assertNotFound();
        $this->deleteJson($this->url($scope, '/'.$foreign->id))->assertNotFound();
        $this->postJson($this->url($scope, '/'.$unread->id.'/read'))->assertOk();
        $this->assertCounts($scope, 3, 1, 2);
        $this->postJson($this->url($scope, '/read-all'))->assertOk();
        $this->assertCounts($scope, 3, 0, 3);
        self::assertNull($foreign->fresh()->read_at);
        $this->deleteJson($this->url($scope, '/'.$unread->id))->assertOk();
        $this->assertCounts($scope, 2, 0, 2);
    }

    #[DataProvider('scopes')]
    public function test_delivery_is_persisted_once_for_the_correct_dashboard(string $scope): void
    {
        [$user] = $this->signIn($scope);
        $service = app(DashboardNotificationService::class);
        $tenantId = $scope === 'tenant' ? 'notification-test' : null;
        $payload = DashboardNotificationService::payload('test.event', 'Test notification', 'Message', '/dashboard');

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $service->enqueue($scope, $tenantId, (int) $user->id, 'test.event:123', $payload);
        }

        self::assertSame(1, $user->notifications()->count());
        $delivery = NotificationDelivery::firstOrFail();
        self::assertNotNull($delivery->delivered_at);
        self::assertNull($delivery->last_error);
        $this->getJson($this->url($scope))->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('items.0.data.title', 'Test notification')
            ->assertJsonPath('broadcast.channel', $service->channel($scope, $tenantId, (int) $user->id));
    }

    #[DataProvider('scopes')]
    public function test_broadcast_auth_rejects_another_account_or_tenant_channel(string $scope): void
    {
        [$user] = $this->signIn($scope);
        $channel = $scope === 'admin' ? 'private-dashboard.admin.999' : 'private-dashboard.tenant.other.user.'.$user->id;
        $this->postJson($this->url($scope, '/auth'), ['socket_id' => '123.456', 'channel_name' => $channel])
            ->assertForbidden();
    }

    #[DataProvider('scopes')]
    public function test_filters_include_only_the_signed_in_users_matching_notifications(string $scope): void
    {
        [$user, $other] = $this->signIn($scope);
        $unread = $this->notice($user);
        $read = $this->notice($user);
        $read->markAsRead();
        $this->notice($other);

        foreach (['all' => 2, 'unread' => 1, 'read' => 1] as $filter => $count) {
            $request = DashboardNotificationRequest::create($this->url($scope, '/all').'?filter='.$filter);
            $route = app('router')->getRoutes()->match($request);
            $request->setRouteResolver(fn () => $route);
            $request->setUserResolver(fn () => $user);
            $data = app(DashboardNotificationController::class)->index($request)->getData();

            self::assertSame($count, $data['notifications']->total());
            self::assertSame(2, $data['totalCount']);
            self::assertSame(1, $data['unreadCount']);
            self::assertSame(1, $data['readCount']);
            if ($filter !== 'all') {
                self::assertSame($filter === 'read' ? $read->id : $unread->id, $data['notifications']->first()->id);
            }
        }
    }

    #[DataProvider('scopes')]
    public function test_delivery_emits_a_refresh_on_the_recipients_private_channel(string $scope): void
    {
        [$user] = $this->signIn($scope);
        config(['dashboard-notifications.broadcast' => true]);
        Event::fake([NotificationsChanged::class]);
        $service = app(DashboardNotificationService::class);
        $tenantId = $scope === 'tenant' ? 'notification-test' : null;

        $service->enqueue($scope, $tenantId, (int) $user->id, 'broadcast:123', ['title' => 'Test']);

        Event::assertDispatched(NotificationsChanged::class, fn ($event) => $event->channel === $service->channel($scope, $tenantId, (int) $user->id));
    }

    #[DataProvider('scopes')]
    public function test_broadcast_auth_accepts_the_current_accounts_private_channel(string $scope): void
    {
        [$user] = $this->signIn($scope);
        $service = app(DashboardNotificationService::class);
        $tenantId = $scope === 'tenant' ? 'notification-test' : null;
        $channel = 'private-'.$service->channel($scope, $tenantId, (int) $user->id);

        $this->postJson($this->url($scope, '/auth'), ['socket_id' => '123.456', 'channel_name' => $channel])
            ->assertOk()->assertJsonStructure(['auth']);
    }

    private function assertCounts(string $scope, int $all, int $unread, int $read): void
    {
        $this->getJson($this->url($scope))->assertOk()
            ->assertJsonPath('count', $unread)
            ->assertJsonPath('counts.all', $all)
            ->assertJsonPath('counts.unread', $unread)
            ->assertJsonPath('counts.read', $read);
    }

    private function signIn(string $scope): array
    {
        if ($scope === 'tenant') {
            DB::connection('mysql')->table('tenants')->insert(['id' => 'notification-test']);
            tenancy()->initialize(Tenant::findOrFail('notification-test'));
            DB::setDefaultConnection('tenant');
        }
        $model = $scope === 'admin' ? AdminUser::class : User::class;
        $user = $model::create([
            'name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'Password123!', 'status' => 'active',
        ]);
        $other = $model::create([
            'name' => 'Other', 'email' => 'other@example.test', 'password' => 'Password123!', 'status' => 'active',
        ]);
        $this->actingAs($user, $scope);

        return [$user, $other];
    }

    private function notice(AdminUser|User $user): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => 'dashboard.event',
            'data' => ['title' => 'Test notification'],
        ]);
    }

    private function url(string $scope, string $suffix = ''): string
    {
        return ($scope === 'admin' ? 'http://localhost/superadmin' : 'http://localhost/notification-test')
            .'/dashboard-notifications'.$suffix;
    }
}
