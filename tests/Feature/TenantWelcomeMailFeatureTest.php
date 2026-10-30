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
use App\Services\Shared\Mail\SystemMailService;
use App\Services\Shared\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Shared\Subscription\SubscriptionManager;
use App\Services\Shared\Subscription\SubscriptionService;
use App\Services\Shared\Tenant\TenantCreator;
use App\Services\Shared\Tenant\TenantInitializer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantWelcomeMailFeatureTest extends TestCase
{
    private array $cleanupTenantIds = [];
    private array $cleanupBillIds = [];
    private array $cleanupTrialIds = [];

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

        if (! empty($this->cleanupBillIds)) {
            Bill::whereIn('id', $this->cleanupBillIds)->delete();
        }

        if (! empty($this->cleanupTrialIds)) {
            TrialRequest::whereIn('id', $this->cleanupTrialIds)->delete();
        }

        if (! empty($this->cleanupTenantIds)) {
            Tenant::withTrashed()->whereIn('id', $this->cleanupTenantIds)->forceDelete();
        }

        parent::tearDown();
    }

    private function createPersistedTenant(string $email, string $companyName, string $contactName): Tenant
    {
        $id = 'test_tw_' . rand(10000, 99999);
        $tenant = Tenant::create([
            'id'                   => $id,
            'folderName'           => $id,
            'db_database'          => 'test_db_' . $id,
            'db_username'          => 'test_user',
            'db_password'          => 'test_pass',
            'company_name'         => $companyName,
            'commercial_number'    => (string) rand(1000000000, 9999999999),
            'primary_contact_name' => $contactName,
            'primary_contact_email'=> $email,
        ]);

        $this->cleanupTenantIds[] = $tenant->id;

        return $tenant;
    }

    /**
     * 1. Test SystemMailService::sendTenantWelcome dispatches TenantWelcomeMail with correct data
     */
    public function test_send_tenant_welcome_dispatches_mailable_with_correct_payload(): void
    {
        $tenant = $this->createPersistedTenant('admin@test-company.com', 'شركة تجربة الإيميل الترحيبي', 'محمد عبدالله');

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

    private function useImmediateDeliveryTransport(): ArrayTransport
    {
        config([
            'mail.default' => 'array',
            'queue.default' => 'database',
            'queue.connections.database.connection' => 'tenant_welcome_mail_queue',
            'queue.connections.database.table' => 'jobs',
            'database.connections.tenant_welcome_mail_queue' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);

        Schema::connection('tenant_welcome_mail_queue')->create('jobs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('queue');
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Mail::swap(new MailManager($this->app));
        $transport = Mail::mailer('array')->getSymfonyTransport();
        self::assertInstanceOf(ArrayTransport::class, $transport);

        return $transport;
    }

    private function assertWelcomeDelivered(ArrayTransport $transport, string $recipient, Tenant $tenant): void
    {
        self::assertCount(1, $transport->messages());
        $message = $transport->messages()->sole()->getOriginalMessage();
        self::assertSame($recipient, $message->getTo()[0]->getAddress());
        self::assertStringContainsString(url('/'.$tenant->id.'/login'), $message->getHtmlBody());
        self::assertSame(0, DB::connection('tenant_welcome_mail_queue')->table('jobs')
            ->where('payload', 'like', '%TenantWelcomeMail%')->count());
    }

    public function test_tenant_welcome_is_delivered_immediately_when_the_default_queue_is_database(): void
    {
        $transport = $this->useImmediateDeliveryTransport();
        $tenant = $this->createPersistedTenant('welcome@delivery.example.test', 'Immediate delivery test', 'Test owner');

        foreach (['subscription', 'superadmin', 'trial'] as $index => $source) {
            self::assertTrue(SystemMailService::sendTenantWelcome($tenant, source: $source));
            self::assertCount($index + 1, $transport->messages(), $source);
            self::assertSame(0, DB::connection('tenant_welcome_mail_queue')->table('jobs')->count(), $source);
        }
    }

    /**
     * 2. Test TenantWelcomeMail renders all required elements across creation sources
     */
    public function test_tenant_welcome_mail_view_renders_login_link_and_login_email(): void
    {
        $tenant = $this->createPersistedTenant('fahad@alfahed.sa', 'مؤسسة الفهد للمزادات', 'فهد الأحمد');
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
        self::assertStringContainsString('فترة تجريبية مجانية', $trialHtml);
        self::assertStringContainsString('7 أيام', $trialHtml);
        self::assertStringContainsString('2026-11-05', $trialHtml);
        self::assertStringContainsString('الحساب التجريبي', $trialHtml);
    }

    /**
     * 3. Test that normal subscription processNewSubscription triggers TenantWelcomeMail
     */
    public function test_normal_subscription_flow_sends_tenant_welcome_mail(): void
    {
        $transport = $this->useImmediateDeliveryTransport();
        $tenant = $this->createPersistedTenant('salem@payment.test', 'شركة الدفع العادي', 'سالم الحربي');

        $user = new User([
            'id'         => 1,
            'name'       => 'سالم الحربي',
            'email'      => 'salem@payment.test',
            'global_uid' => Str::uuid()->toString(),
        ]);

        $mockCreator = \Mockery::mock(TenantCreator::class);
        $mockCreator->shouldReceive('createTenantEnvironment')->once()->andReturn([$tenant, $user]);

        $plan = Plan::first() ?? Plan::create([
            'name'                        => 'باقة الاختبار ' . rand(100, 999),
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
                'checkout_source' => 'site_checkout',
            ],
        ]);
        $this->cleanupBillIds[] = $bill->id;

        $this->instance(SubscriptionService::class, $subscriptionService);
        $this->mock(PaymentGatewayInterface::class, function ($mock): void {
            $mock->shouldReceive('getPayment')->once()->with('pay_welcome_test')->andReturn([
                'id' => 'pay_welcome_test',
                'status' => 'paid',
                'amount' => 20000,
                'currency' => 'SAR',
            ]);
        });

        $callback = route('site.plans.checkout.callback', ['bill' => $bill->bill_number, 'id' => 'pay_welcome_test']);
        $this->get($callback)->assertRedirect(url('/'.$tenant->id.'/login'))->assertSessionHas('success');
        self::assertSame(BillStatus::PAID, $bill->fresh()->status);
        $this->assertWelcomeDelivered($transport, 'salem@payment.test', $tenant);

        $this->get($callback)->assertRedirect(url('/'.$tenant->id.'/login'));
        $this->assertWelcomeDelivered($transport, 'salem@payment.test', $tenant);
    }

    /**
     * 4. Test that SuperAdmin company creation triggers TenantWelcomeMail
     */
    public function test_superadmin_creation_flow_sends_tenant_welcome_mail(): void
    {
        $transport = $this->useImmediateDeliveryTransport();
        $tenant = $this->createPersistedTenant('admin@superadmin-company.test', 'شركة المشرف العام', 'خالد السعيد');

        $user = new User([
            'id'         => 1,
            'name'       => 'خالد السعيد',
            'email'      => 'admin@superadmin-company.test',
            'global_uid' => Str::uuid()->toString(),
        ]);

        $mockCreator = \Mockery::mock(TenantCreator::class);
        $mockCreator->shouldReceive('createTenantEnvironment')->once()->andReturn([$tenant, $user]);

        $plan = Plan::first() ?? Plan::create([
            'name'                        => 'باقة المشرف ' . rand(100, 999),
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

        $this->instance(SubscriptionService::class, $subscriptionService);
        $admin = AdminUser::where('email', 'superadmin@gmail.com')->firstOrFail();
        $response = $this->actingAs($admin, 'admin')->post(route('admin.clients.store'), [
            'company_name' => 'Welcome route test '.Str::random(10),
            'commercial_number' => (string) random_int(1000000000, 1999999999),
            'tax_number' => '3'.random_int(1000000000000, 9999999999999).'3',
            'city_id' => City::firstOrFail()->id,
            'address' => 'الرياض',
            'name' => 'خالد السعيد',
            'phone' => '05'.random_int(10000000, 99999999),
            'email' => 'admin@superadmin-company.test',
            'password' => 'AdminPass123!',
            'password_confirmation' => 'AdminPass123!',
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'activate_immediately' => true,
        ]);
        $this->cleanupBillIds = array_merge($this->cleanupBillIds,
            Bill::where('metadata->billing_email', 'admin@superadmin-company.test')->pluck('id')->all());

        $response->assertRedirect(route('admin.clients.index'))->assertSessionHas('success')->assertSessionHasNoErrors();
        $this->assertWelcomeDelivered($transport, 'admin@superadmin-company.test', $tenant);
        self::assertStringContainsString('AdminPass123!', $transport->messages()->sole()->getOriginalMessage()->getHtmlBody());
    }

    /**
     * 5. Test that Trial request approval triggers TenantWelcomeMail
     */
    public function test_trial_request_approval_flow_sends_tenant_welcome_mail(): void
    {
        $transport = $this->useImmediateDeliveryTransport();
        $city = City::first() ?? City::create(['name' => 'الرياض', 'is_active' => true]);
        $admin = AdminUser::first() ?? AdminUser::create([
            'name'     => 'Super Admin',
            'email'    => 'superadmin@gmail.com',
            'password' => bcrypt('password'),
            'phone'    => '0500000001',
            'status'   => \App\Enums\Admin\Users\AdminUserStatus::ACTIVE,
        ]);

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
        $this->cleanupTrialIds[] = $trialRequest->id;

        $tenant = $this->createPersistedTenant($trialRequest->customer_email, $trialRequest->company_name . ' المنشأة', $trialRequest->customer_name);
        $trialRequest->update(['tenant_id' => $tenant->id]);

        $user = new User([
            'id'         => 1,
            'name'       => $trialRequest->customer_name,
            'email'      => $trialRequest->customer_email,
            'global_uid' => Str::uuid()->toString(),
        ]);

        $mockCreator = \Mockery::mock(TenantCreator::class);

        $mockInitializer = \Mockery::mock(TenantInitializer::class);
        $mockInitializer->shouldReceive('setupTenantEnvironment')->once()->andReturn($user);
        $mockInitializer->shouldReceive('createCoreSeat')->once();

        $action = new ApproveTrialRequestAction(
            $mockCreator,
            $mockInitializer,
            app(SubscriptionManager::class)
        );

        $this->instance(ApproveTrialRequestAction::class, $action);
        $this->actingAs($admin, 'admin')->post(route('admin.trial-requests.approve', $trialRequest))
            ->assertRedirect(route('admin.trial-requests.show', $trialRequest))->assertSessionHas('success');

        self::assertSame('approved', $trialRequest->fresh()->status->value);
        $this->assertWelcomeDelivered($transport, $trialEmail, $tenant);
        self::assertStringContainsString('7 أيام', $transport->messages()->sole()->getOriginalMessage()->getHtmlBody());
    }
}
