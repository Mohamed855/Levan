<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Admin\PublicRequests\ApproveTrialRequestAction;
use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Mail\Tenants\TenantWelcomeMail;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\Companies\CompanyService;
use App\Services\Shared\Cpanel\CpanelService;
use App\Services\Shared\Mail\SystemMailService;
use App\Services\Shared\Subscription\SubscriptionManager;
use App\Services\Shared\Subscription\SubscriptionService;
use App\Services\Shared\Tenant\TenantCreator;
use App\Services\Shared\Tenant\TenantInitializer;
use Database\Seeders\tenant\PermissionsRolesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantWelcomeMailFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        Mail::fake();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    /**
     * 1. Test SystemMailService::sendTenantWelcome dispatches TenantWelcomeMail with correct data
     */
    public function test_send_tenant_welcome_dispatches_mailable_with_correct_payload(): void
    {
        $tenantId = 'test_tenant_welcome_' . Str::lower(Str::random(6));
        $tenant = new Tenant([
            'id'                   => $tenantId,
            'folderName'           => $tenantId,
            'company_name'         => 'شركة تجربة الإيميل الترحيبي',
            'primary_contact_name' => 'محمد عبدالله',
            'primary_contact_email'=> 'admin@test-company.com',
        ]);
        $tenant->exists = true;

        $sent = SystemMailService::sendTenantWelcome(
            tenant: $tenant,
            loginEmail: 'admin@test-company.com',
            source: 'subscription'
        );

        self::assertTrue($sent);

        Mail::assertQueued(TenantWelcomeMail::class, function (TenantWelcomeMail $mail) use ($tenant) {
            return $mail->hasTo('admin@test-company.com')
                && $mail->loginEmail === 'admin@test-company.com'
                && $mail->loginUrl === $tenant->getLoginUrl()
                && str_contains($mail->loginUrl, $tenant->id . '/login')
                && $mail->creationSource === 'subscription';
        });
    }

    /**
     * 2. Test TenantWelcomeMail renders all required elements across creation sources
     */
    public function test_tenant_welcome_mail_view_renders_login_link_and_login_email(): void
    {
        $tenantId = 'test_render_' . Str::lower(Str::random(6));
        $tenant = new Tenant([
            'id'                   => $tenantId,
            'folderName'           => $tenantId,
            'company_name'         => 'مؤسسة الفهد للمزادات',
            'primary_contact_name' => 'فهد الأحمد',
            'primary_contact_email'=> 'fahad@alfahed.sa',
        ]);
        $tenant->exists = true;

        $loginUrl = $tenant->getLoginUrl();

        // Case A: Subscription (Paid)
        $subMail = new TenantWelcomeMail(
            tenant: $tenant,
            loginUrl: $loginUrl,
            loginEmail: 'fahad@alfahed.sa',
            creationSource: 'subscription'
        );
        $subHtml = $subMail->render();

        self::assertStringContainsString('fahad@alfahed.sa', $subHtml);
        self::assertStringContainsString($loginUrl, $subHtml);
        self::assertStringContainsString('مؤسسة الفهد للمزادات', $subHtml);
        self::assertStringContainsString('لوت Levan', $subHtml);
        self::assertStringContainsString('الدخول إلى لوحة التحكم الخاصة بك', $subHtml);

        // Case B: SuperAdmin
        $adminMail = new TenantWelcomeMail(
            tenant: $tenant,
            loginUrl: $loginUrl,
            loginEmail: 'fahad@alfahed.sa',
            creationSource: 'superadmin',
            temporaryPassword: 'TempPassword123!'
        );
        $adminHtml = $adminMail->render();

        self::assertStringContainsString('fahad@alfahed.sa', $adminHtml);
        self::assertStringContainsString($loginUrl, $adminHtml);
        self::assertStringContainsString('TempPassword123!', $adminHtml);
        self::assertStringContainsString('إدارة النظام', $adminHtml);

        // Case C: Trial
        $trialMail = new TenantWelcomeMail(
            tenant: $tenant,
            loginUrl: $loginUrl,
            loginEmail: 'fahad@alfahed.sa',
            creationSource: 'trial',
            trialDays: 7,
            trialEndDate: '2026-11-05'
        );
        $trialHtml = $trialMail->render();

        self::assertStringContainsString('fahad@alfahed.sa', $trialHtml);
        self::assertStringContainsString($loginUrl, $trialHtml);
        self::assertStringContainsString('7 أيام مجاناً', $trialHtml);
        self::assertStringContainsString('2026-11-05', $trialHtml);
        self::assertStringContainsString('الحساب التجريبي', $trialHtml);
    }

    /**
     * 3. Test that normal subscription processNewSubscription triggers TenantWelcomeMail
     */
    public function test_normal_subscription_flow_sends_tenant_welcome_mail(): void
    {
        $tenant = new Tenant([
            'id'                   => 'tenant_paid_' . Str::lower(Str::random(6)),
            'folderName'           => 'tenant_paid',
            'company_name'         => 'شركة الدفع العادي',
            'primary_contact_name' => 'سالم الحربي',
            'primary_contact_email'=> 'salem@payment.test',
        ]);
        $tenant->exists = true;

        $user = new User([
            'id'         => 1,
            'name'       => 'سالم الحربي',
            'email'      => 'salem@payment.test',
            'global_uid' => Str::uuid()->toString(),
        ]);

        $mockCreator = \Mockery::mock(TenantCreator::class);
        $mockCreator->shouldReceive('createTenantEnvironment')->once()->andReturn([$tenant, $user]);

        $plan = Plan::first() ?? Plan::create([
            'name'                        => 'باقة الاختبار',
            'slug'                        => 'plan-test-' . uniqid(),
            'price'                       => 200,
            'price_after_discount'        => 200,
            'subscription_duration_type'  => 'limited',
            'subscription_billing_cycle'  => 'monthly',
            'included_users'              => 5,
            'properties_limit'            => 50,
            'storage_quota_gb'            => 10,
            'active_auctions_limit'       => 20,
            'is_active'                   => true,
            'created_by'                  => 1,
        ]);

        $mockInitializer = \Mockery::mock(TenantInitializer::class);
        $mockInitializer->shouldReceive('createCoreSeat')->once();

        $subManager = app(SubscriptionManager::class);

        $subscriptionService = new SubscriptionService(
            $mockCreator,
            $mockInitializer,
            $subManager,
            app(\App\Services\Shared\Coupons\CouponService::class),
            app(\App\Services\Shared\Bills\BillService::class)
        );

        $bill = Bill::create([
            'bill_number'    => 'BILL-' . strtoupper(Str::random(8)),
            'amount'         => 200,
            'original_amount'=> 200,
            'payment_method' => PaymentMethod::ONLINE,
            'type'           => BillType::NEW_SUBSCRIPTION,
            'status'         => BillStatus::UNPAID,
            'metadata'       => [
                'plan_id'       => $plan->id,
                'billing_email' => 'salem@payment.test',
                'billing_name'  => 'سالم الحربي',
            ],
        ]);

        $subscriptionService->completeOnlineSubscription($bill, ['payment_id' => 'pay_123']);

        Mail::assertQueued(TenantWelcomeMail::class, function (TenantWelcomeMail $mail) use ($tenant) {
            return $mail->hasTo('salem@payment.test')
                && $mail->loginEmail === 'salem@payment.test'
                && $mail->loginUrl === $tenant->getLoginUrl()
                && $mail->creationSource === 'subscription';
        });

        $bill->delete();
    }

    /**
     * 4. Test that SuperAdmin company creation triggers TenantWelcomeMail
     */
    public function test_superadmin_creation_flow_sends_tenant_welcome_mail(): void
    {
        $tenant = new Tenant([
            'id'                   => 'tenant_superadmin_' . Str::lower(Str::random(6)),
            'folderName'           => 'tenant_superadmin',
            'company_name'         => 'شركة المشرف العام',
            'primary_contact_name' => 'خالد السعيد',
            'primary_contact_email'=> 'admin@superadmin-company.test',
        ]);
        $tenant->exists = true;

        $user = new User([
            'id'         => 1,
            'name'       => 'خالد السعيد',
            'email'      => 'admin@superadmin-company.test',
            'global_uid' => Str::uuid()->toString(),
        ]);

        $mockCreator = \Mockery::mock(TenantCreator::class);
        $mockCreator->shouldReceive('createTenantEnvironment')->once()->andReturn([$tenant, $user]);

        $plan = Plan::first() ?? Plan::create([
            'name'                        => 'باقة المشرف',
            'slug'                        => 'plan-admin-' . uniqid(),
            'price'                       => 300,
            'price_after_discount'        => 300,
            'subscription_duration_type'  => 'limited',
            'subscription_billing_cycle'  => 'monthly',
            'included_users'              => 10,
            'properties_limit'            => 100,
            'storage_quota_gb'            => 20,
            'active_auctions_limit'       => 50,
            'is_active'                   => true,
            'created_by'                  => 1,
        ]);

        $mockInitializer = \Mockery::mock(TenantInitializer::class);
        $mockInitializer->shouldReceive('createCoreSeat')->once();

        $subManager = app(SubscriptionManager::class);

        $subscriptionService = new SubscriptionService(
            $mockCreator,
            $mockInitializer,
            $subManager,
            app(\App\Services\Shared\Coupons\CouponService::class),
            app(\App\Services\Shared\Bills\BillService::class)
        );

        $bill = Bill::create([
            'bill_number'    => 'BILL-' . strtoupper(Str::random(8)),
            'amount'         => 300,
            'original_amount'=> 300,
            'payment_method' => PaymentMethod::ADMIN,
            'type'           => BillType::NEW_SUBSCRIPTION,
            'status'         => BillStatus::UNPAID,
            'metadata'       => [
                'plan_id'       => $plan->id,
                'billing_email' => 'admin@superadmin-company.test',
                'billing_name'  => 'خالد السعيد',
            ],
        ]);

        $subscriptionService->completeAdminSubscription($bill, [
            'activate_immediately' => 1,
            'password'             => 'AdminPass123!',
        ]);

        Mail::assertQueued(TenantWelcomeMail::class, function (TenantWelcomeMail $mail) use ($tenant) {
            return $mail->hasTo('admin@superadmin-company.test')
                && $mail->loginEmail === 'admin@superadmin-company.test'
                && $mail->loginUrl === $tenant->getLoginUrl()
                && $mail->creationSource === 'superadmin'
                && $mail->temporaryPassword === 'AdminPass123!';
        });

        $bill->delete();
    }

    /**
     * 5. Test that Trial request approval triggers TenantWelcomeMail
     */
    public function test_trial_request_approval_flow_sends_tenant_welcome_mail(): void
    {
        $city = City::firstOrFail();
        $admin = AdminUser::firstOrFail();

        $trialEmail = 'trial-' . Str::lower(Str::random(8)) . '@example.test';
        $trialRequest = TrialRequest::create([
            'customer_name'         => 'طارق المنصور',
            'customer_email'        => $trialEmail,
            'customer_phone'        => '05' . random_int(10000000, 99999999),
            'company_name'          => 'مؤسسة التجربة العقارية ' . Str::random(6),
            'commercial_number'     => (string) random_int(1000000000, 1999999999),
            'tax_number'            => '3' . random_int(1000000000000, 9999999999999) . '3',
            'city_id'               => $city->id,
            'address'               => 'جدة، حي الشاطئ',
            'password_hash'         => bcrypt('Password123!'),
            'email_verified_at'     => now(),
            'status'                => 'pending',
            'provisioning_status'   => 'ready',
        ]);

        $tenant = new Tenant([
            'id'                   => 'tenant_trial_' . Str::lower(Str::random(6)),
            'folderName'           => 'tenant_trial',
            'company_name'         => $trialRequest->company_name,
            'primary_contact_name' => $trialRequest->customer_name,
            'primary_contact_email'=> $trialRequest->customer_email,
        ]);
        $tenant->exists = true;

        $user = new User([
            'id'         => 1,
            'name'       => $trialRequest->customer_name,
            'email'      => $trialRequest->customer_email,
            'global_uid' => Str::uuid()->toString(),
        ]);

        $mockCreator = \Mockery::mock(TenantCreator::class);
        $mockCreator->shouldReceive('createTenantEnvironment')
            ->once()
            ->andReturnUsing(function ($meta, $cb) use ($tenant, $user, $trialRequest) {
                if ($cb) {
                    $cb($tenant);
                }
                return [$tenant, $user];
            });

        $mockInitializer = \Mockery::mock(TenantInitializer::class);
        $mockInitializer->shouldReceive('createCoreSeat')->once();

        $action = new ApproveTrialRequestAction(
            $mockCreator,
            $mockInitializer,
            app(SubscriptionManager::class)
        );

        $action->execute($trialRequest, $admin);

        Mail::assertQueued(TenantWelcomeMail::class, function (TenantWelcomeMail $mail) use ($trialEmail, $tenant) {
            return $mail->hasTo($trialEmail)
                && $mail->loginEmail === $trialEmail
                && $mail->loginUrl === $tenant->getLoginUrl()
                && $mail->creationSource === 'trial'
                && $mail->trialDays === 7;
        });

        $trialRequest->delete();
    }
}
