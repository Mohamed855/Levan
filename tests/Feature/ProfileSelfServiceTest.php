<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Admin\Profile\ProfileController as AdminProfileController;
use App\Http\Controllers\Tenants\Profile\ProfileController as TenantProfileController;
use App\Http\Middleware\EnsureAdminTwoFactorIsEnforced;
use App\Http\Middleware\Tenancy\EnsureTenantTwoFactorIsEnforced;
use App\Http\Middleware\Tenancy\InitializeTenancyBySession;
use App\Http\Middleware\Tenancy\TenantMaintenance;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Tests\TestCase;

final class ProfileSelfServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'tenancy.bootstrappers' => [],
            'activitylog.enabled' => false,
        ]);
        DB::purge('mysql');
        DB::purge('tenant');
        $this->withoutMiddleware([
            EnsureAdminTwoFactorIsEnforced::class, EnsureTenantTwoFactorIsEnforced::class,
            InitializeTenancyByPath::class, InitializeTenancyBySession::class, TenantMaintenance::class,
        ]);
        $this->withoutVite();
        view()->share('errors', new ViewErrorBag);

        foreach (['mysql' => 'admin_users', 'tenant' => 'users'] as $connection => $tableName) {
            Schema::connection($connection)->create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('password');
                $table->string('image')->nullable();
                $table->string('status')->default('active');
                $table->boolean('is_super_admin')->default(false);
                $table->string('global_uid')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
            Schema::connection($connection)->create('roles', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
            Schema::connection($connection)->create('model_has_roles', function (Blueprint $table): void {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
            });
        }
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
    public function test_profile_update_only_changes_the_signed_in_users_personal_data(string $scope): void
    {
        [$user, $other] = $this->signIn($scope);
        $originalHash = $user->password;

        $this->put($this->profileUrl($scope), [
            'name' => 'Updated Owner', 'email' => $user->email, 'phone' => '0512345678',
            'user_id' => $other->id, 'password' => 'InjectedPassword123!',
            'password_confirmation' => 'InjectedPassword123!', 'role_id' => 999,
            'status' => 'inactive', 'is_super_admin' => true,
        ])->assertRedirect($this->profileUrl($scope))->assertSessionHas('success');

        self::assertSame('Updated Owner', $user->fresh()->name);
        self::assertSame($originalHash, $user->fresh()->password);
        self::assertSame('Other User', $other->fresh()->name);
        self::assertFalse((bool) $user->fresh()->is_super_admin);
        self::assertSame('active', $user->fresh()->getRawOriginal('status'));
        self::assertSame(0, $user->roles()->count());
    }

    #[DataProvider('scopes')]
    public function test_profile_email_uniqueness_cannot_ignore_another_user_from_the_payload(string $scope): void
    {
        [$user, $other] = $this->signIn($scope);

        $this->putJson($this->profileUrl($scope), [
            'name' => 'Updated Owner', 'email' => $other->email, 'phone' => '0512345678',
            'user_id' => $other->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        self::assertSame('Owner User', $user->fresh()->name);
    }

    #[DataProvider('scopes')]
    public function test_personal_edit_form_has_no_password_fields_but_management_edit_keeps_them(string $scope): void
    {
        [$user] = $this->signIn($scope);
        $view = $scope === 'admin' ? 'admin.users.partials._form' : 'tenant.user-management.users.partials._form';
        $data = ['user' => $user, 'is_edit' => true, 'roles' => collect()];
        $action = $scope === 'admin' ? route('admin.profile.update') : tenant_route_name('profile.update');

        $this->view($view, $data + ['is_profile' => true])
            ->assertSee('action="'.$action.'"', false)
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="password_confirmation"', false);

        $this->view($view, $data)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    #[DataProvider('scopes')]
    public function test_profile_update_is_wired_to_the_shared_validation_endpoint(string $scope): void
    {
        [$user] = $this->signIn($scope);
        $validationUrl = $scope === 'admin'
            ? route('admin.wizard-drafts.validate-step')
            : 'http://localhost/profile-test/wizard-drafts/validate-step';

        $this->postJson($validationUrl, [
            'target_path' => $this->profileUrl($scope), 'target_method' => 'PUT',
            'fields' => ['name', 'email', 'phone'],
            'name' => $user->name, 'email' => $user->email, 'phone' => '0512345678',
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_superadmin_can_edit_their_own_personal_data(): void
    {
        [$user] = $this->signIn('admin');
        $user->update(['is_super_admin' => true]);

        $this->put($this->profileUrl('admin'), [
            'name' => 'Updated Superadmin', 'email' => $user->email, 'phone' => '0512345678',
        ])->assertRedirect($this->profileUrl('admin'));

        self::assertSame('Updated Superadmin', $user->fresh()->name);
        self::assertTrue($user->fresh()->is_super_admin);
    }

    #[DataProvider('scopes')]
    public function test_personal_profile_routes_use_a_separate_controller(string $scope): void
    {
        $routeName = $scope === 'admin' ? 'admin.profile.show' : 'path.profile.show';
        self::assertTrue(Route::has($routeName));
        $controller = $scope === 'admin' ? AdminProfileController::class : TenantProfileController::class;
        self::assertSame($controller.'@show', Route::getRoutes()->getByName($routeName)->getActionName());
    }

    private function signIn(string $scope): array
    {
        if ($scope === 'tenant') {
            $tenant = new Tenant;
            $tenant->id = 'profile-test';
            tenancy()->initialize($tenant);
            DB::setDefaultConnection('tenant');
        }

        $model = $scope === 'admin' ? AdminUser::class : User::class;
        $user = $model::create([
            'name' => 'Owner User', 'email' => 'owner@example.test',
            'password' => Hash::make('OldPassword123!'), 'phone' => '0512345678', 'status' => 'active',
        ]);
        $other = $model::create([
            'name' => 'Other User', 'email' => 'other@example.test',
            'password' => Hash::make('OtherPassword123!'), 'status' => 'active',
        ]);
        $user->load('roles');
        $this->actingAs($user, $scope);

        return [$user, $other];
    }

    private function profileUrl(string $scope): string
    {
        return $scope === 'admin' ? 'http://localhost/superadmin/profile' : 'http://localhost/profile-test/profile';
    }
}
