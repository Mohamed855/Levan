<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Admin\PublicRequests\ApproveTrialRequestAction;
use App\Actions\Tenants\Auth\RegisterTenantUserAction;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\City;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\shared\Cpanel\CpanelService;
use App\Services\shared\Subscription\SubscriptionLimitsResolver;
use App\Services\shared\Tenant\TenantInitializer;
use App\Services\Tenants\Shared\CentralSubscriptionContextGateway;
use App\Services\Tenants\UserManagement\User\UserService;
use Database\Seeders\tenant\PermissionsRolesSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TrialRegistrationFlowTest extends TestCase
{
    private array $trialIds = [];

    private ?string $databaseName = null;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['services.moyasar.mode' => 'test']);
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        foreach (TrialRequest::whereIn('id', $this->trialIds)->get() as $request) {
            $tenant = $request->tenant;
            $request->delete();
            $tenant?->forceDelete();
        }
        if ($this->databaseName && preg_match('/^levan_trial_flow_[a-f0-9]{12}$/', $this->databaseName)) {
            DB::connection('mysql')->statement('DROP DATABASE `'.$this->databaseName.'`');
        }
        parent::tearDown();
    }

    private function payload(): array
    {
        return [
            'billing_company_name' => 'شركة اختبار التجربة '.Str::random(8),
            'billing_commercial_number' => (string) random_int(1000000000, 1999999999),
            'billing_tax_number' => '300000000000003', 'billing_city_id' => City::firstOrFail()->id,
            'billing_address' => 'الرياض', 'billing_name' => 'مدير التجربة',
            'billing_email' => 'trial-'.Str::lower(Str::random(12)).'@example.test',
            'billing_phone' => '05'.random_int(10000000, 99999999),
            'billing_password' => 'TrialFlow123!', 'billing_password_confirmation' => 'TrialFlow123!',
        ];
    }

    public function test_verified_request_approval_provisions_a_working_single_user_tenant(): void
    {
        $this->withoutExceptionHandling();
        $data = $this->payload();
        $bills = Bill::count();
        $this->get(route('site.trial'))->assertOk()->assertSee('billing_company_name', false)
            ->assertSee('checkout-grid', false)->assertSee('checkout-summary-col', false)
            ->assertSee('تجربة مجانية')->assertSee('0.00')
            ->assertDontSee('coupon_code', false)->assertDontSee('apply-coupon-btn', false);
        $this->post(route('site.trial.store'), $data)->assertRedirect(route('site.trial'));
        self::assertFalse(TrialRequest::where('customer_email', $data['billing_email'])->exists());
        $this->get(route('site.trial'))->assertOk()->assertSee('تأكيد وإرسال طلب التجربة')->assertDontSee('TrialFlow123!')
            ->assertSee('checkout-otp-timer', false)->assertSee('checkout-summary-col', false)
            ->assertDontSee('coupon_code', false)->assertDontSee('payment_method_card', false);
        $this->post(route('site.trial.verify'), ['otp' => '000000'])->assertRedirect(route('site.trial.submitted'));
        $request = TrialRequest::where('customer_email', $data['billing_email'])->firstOrFail();
        $this->trialIds[] = $request->id;
        $this->post(route('site.trial.verify'), ['otp' => '000000'])->assertSessionHasErrors('otp');
        self::assertSame(1, TrialRequest::where('customer_email', $data['billing_email'])->count());
        self::assertTrue(Hash::check($data['billing_password'], $request->password_hash));
        self::assertNotNull($request->email_verified_at);
        self::assertSame($bills, Bill::count());

        $this->databaseName = 'levan_trial_flow_'.substr(bin2hex(random_bytes(6)), 0, 12);
        $database = $this->databaseName;
        $this->partialMock(CpanelService::class, function ($mock) use ($database) {
            $mock->shouldReceive('createDatabase')->once()->andReturnUsing(function () use ($database): array {
                DB::connection('mysql')->statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

                return [
                    'database' => $database, 'username' => config('database.connections.mysql.username'),
                    'password' => config('database.connections.mysql.password'),
                    'host' => config('database.connections.mysql.host'),
                ];
            });
        });

        $admin = AdminUser::where('email', 'superadmin@gmail.com')->firstOrFail();
        $initializer = \Mockery::mock(TenantInitializer::class, [[PermissionsRolesSeeder::class]])->makePartial();
        $initializer->shouldReceive('createCoreSeat')->once()->andThrow(new \RuntimeException('Simulated provisioning interruption'));
        $initializer->shouldReceive('createCoreSeat')->once()->passthru();
        $this->instance(TenantInitializer::class, $initializer);
        $this->actingAs($admin, 'admin')->post(route('admin.trial-requests.approve', $request))->assertSessionHas('error');
        $request->refresh();
        self::assertSame('pending', $request->status->value);
        self::assertSame('failed', $request->provisioning_status);
        self::assertNotNull($request->tenant_id);
        $firstTenantId = $request->tenant_id;
        $this->app->forgetInstance(TenantInitializer::class);
        $this->actingAs($admin, 'admin')->post(route('admin.trial-requests.approve', $request))
            ->assertRedirect(route('admin.trial-requests.show', $request));
        $request->refresh();
        self::assertSame('approved', $request->status->value);
        self::assertSame($firstTenantId, $request->tenant_id);
        self::assertNull($request->password_hash);
        $this->get(route('admin.trial-requests.show', $request))->assertOk()->assertSee('فتح رابط دخول العميل');
        $subscription = $request->subscription;
        self::assertNull($subscription->plan_id);
        self::assertSame('trial', $subscription->status->value);
        self::assertSame(1, (int) $subscription->total_users);
        self::assertSame(1, (int) $subscription->used_users);
        self::assertEquals(7, $subscription->starts_at->diffInDays($subscription->ends_at));
        self::assertFalse($subscription->auto_renew);
        self::assertSame($bills, Bill::count());

        $this->actingAs($admin, 'admin')->post(route('admin.trial-requests.approve', $request))->assertSessionHas('error');
        self::assertSame(1, Tenant::whereKey($request->tenant_id)->count());
        self::assertSame(1, Subscription::where('tenant_id', $request->tenant_id)->count());
        Auth::forgetGuards();
        $login = route('path.login', ['tenant' => $request->tenant_id]);
        $this->get($login)->assertOk();
        $this->post($login, ['email' => $data['billing_email'], 'password' => $data['billing_password']])->assertRedirect();
        $this->assertAuthenticated('tenant');
        $dashboard = route('path.dashboard', ['tenant' => $request->tenant_id]);
        $this->get($dashboard)->assertOk();
        $this->get(route('path.assignments.index', ['tenant' => $request->tenant_id]))->assertOk();
        self::assertSame(1, User::count());
        $owner = User::firstOrFail();
        self::assertTrue($owner->hasRole('Admin'));
        self::assertNotNull($owner->email_verified_at);
        $limits = app(SubscriptionLimitsResolver::class)->resolveForTenant($request->tenant_id);
        self::assertSame(1, $limits['included_users']);
        self::assertSame(1, $limits['properties_limit']);
        $this->get(route('path.users.index', ['tenant' => $request->tenant_id]))->assertOk()->assertSee('btn btn-secondary btn-add disabled', false);
        $this->get(route('path.subscriptions.index', ['tenant' => $request->tenant_id]))->assertOk()->assertSee('تجربة مجانية');
        self::assertFalse(app(CentralSubscriptionContextGateway::class)->canAddUser(1));
        $this->withExceptionHandling();
        $response = $this->post(route('path.users.store', ['tenant' => $request->tenant_id]), []);
        self::assertTrue($response->isRedirect() || $response->status() === 403);
        self::assertSame(1, User::count());
        $this->get(route('path.users.create', ['tenant' => $request->tenant_id]))->assertForbidden();
        foreach ([app(UserService::class), app(RegisterTenantUserAction::class)] as $creator) {
            try {
                $creator instanceof RegisterTenantUserAction ? $creator->execute([]) : $creator->createUser([]);
                self::fail('Trial user creation must be rejected.');
            } catch (ValidationException $exception) {
                self::assertArrayHasKey('user_limit', $exception->errors());
            }
        }
        self::assertSame(1, User::count());
        $this->travel(8)->days();
        $this->get($dashboard)->assertRedirect();
        $this->travelBack();
    }

    public function test_invalid_and_expired_otp_do_not_create_a_trial_request(): void
    {
        $data = $this->payload();
        $this->post(route('site.trial.store'), $data)->assertRedirect();
        $this->post(route('site.trial.verify'), ['otp' => '111111'])->assertSessionHasErrors('otp');
        self::assertFalse(TrialRequest::where('customer_email', $data['billing_email'])->exists());
        $this->travel(3)->minutes();
        $this->post(route('site.trial.verify'), ['otp' => '000000'])->assertSessionHasErrors('otp');
        self::assertFalse(TrialRequest::where('customer_email', $data['billing_email'])->exists());
        $this->travelBack();
    }

    public function test_legacy_incomplete_request_cannot_be_approved(): void
    {
        $request = TrialRequest::create(['customer_name' => 'طلب قديم', 'customer_email' => 'legacy-'.Str::random(8).'@example.test', 'customer_phone' => '0500000000', 'status' => 'pending']);
        $this->trialIds[] = $request->id;
        $this->expectException(ValidationException::class);
        app(ApproveTrialRequestAction::class)->execute($request, AdminUser::firstOrFail());
    }

    public function test_shared_validation_checks_the_full_trial_details(): void
    {
        $this->postJson(route('site.form-validation'), [
            'target_path' => '/trial', 'target_method' => 'POST',
            'fields' => ['billing_commercial_number'], 'billing_commercial_number' => '123',
        ])->assertUnprocessable()->assertJsonValidationErrors('billing_commercial_number');
    }

    public function test_legacy_request_can_be_completed_only_after_verifying_its_email(): void
    {
        $data = $this->payload();
        $legacy = TrialRequest::create(['customer_name' => 'طلب قديم', 'customer_email' => $data['billing_email'], 'customer_phone' => $data['billing_phone'], 'status' => 'pending']);
        $this->trialIds[] = $legacy->id;
        $this->post(route('site.trial.store'), $data)->assertSessionHasNoErrors();
        self::assertNull($legacy->fresh()->email_verified_at);
        $this->post(route('site.trial.verify'), ['otp' => '000000'])->assertRedirect(route('site.trial.submitted'));
        $legacy->refresh();
        self::assertNotNull($legacy->email_verified_at);
        self::assertSame($data['billing_company_name'], $legacy->company_name);
        self::assertSame(1, TrialRequest::where('customer_email', $data['billing_email'])->count());
    }
}
