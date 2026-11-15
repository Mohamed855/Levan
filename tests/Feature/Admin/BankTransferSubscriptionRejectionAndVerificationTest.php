<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Mail\Billing\BankTransferRejectedMail;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Shared\Tenant\TenantCreator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankTransferSubscriptionRejectionAndVerificationTest extends TestCase
{
    protected AdminUser $superAdmin;

    private array $cleanupBillIds = [];

    private array $cleanupTenantIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'admin']);
        $this->superAdmin = AdminUser::where('email', 'superadmin@gmail.com')->first() ?? AdminUser::first();
        if ($this->superAdmin && ! $this->superAdmin->hasRole('SuperAdmin')) {
            $this->superAdmin->assignRole($superAdminRole);
        }

        $this->actingAs($this->superAdmin, 'admin');
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        if (! empty($this->cleanupBillIds)) {
            Bill::whereIn('id', $this->cleanupBillIds)->forceDelete();
        }

        if (! empty($this->cleanupTenantIds)) {
            Tenant::withTrashed()->whereIn('id', $this->cleanupTenantIds)->forceDelete();
        }

        Mockery::close();
        parent::tearDown();
    }

    protected function adminUrl(string $path = ''): string
    {
        $path = ltrim($path, '/');

        return $path ? 'http://localhost/superadmin/'.$path : 'http://localhost/superadmin';
    }

    public function test_super_admin_can_reject_new_subscription_bank_transfer_and_sends_email(): void
    {
        Mail::fake();

        $plan = Plan::first() ?? Plan::create([
            'name' => 'باقة الاختبار '.rand(100, 999),
            'slug' => 'plan-test-'.uniqid(),
            'price' => 300,
            'price_after_discount' => 300,
            'subscription_duration_type' => 'limited',
            'subscription_billing_cycle' => 'monthly',
            'included_users' => 3,
            'properties_limit' => 20,
            'storage_quota_gb' => 5,
            'active_auctions_limit' => 10,
            'is_active' => true,
            'created_by' => 1,
        ]);

        $bill = Bill::create([
            'tenant_id' => null,
            'amount' => 300,
            'original_amount' => 300,
            'type' => BillType::NEW_SUBSCRIPTION,
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'status' => BillStatus::PENDING_VERIFICATION,
            'metadata' => [
                'plan_id' => $plan->id,
                'billing_name' => 'خالد العتيبي',
                'billing_email' => 'khaled.bank@example.com',
                'billing_phone' => '0555555555',
                'billing_company_name' => 'شركة خالد العقارية',
                'billing_commercial_number' => '1010101010',
            ],
        ]);
        $this->cleanupBillIds[] = $bill->id;

        $response = $this->post($this->adminUrl("bills/{$bill->id}/reject"), [
            'reject_reason' => 'صورة التحويل غير واضحة ورقم الحساب غير مطابق',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $fresh = $bill->fresh();
        $this->assertSame(BillStatus::REJECTED, $fresh->status);
        $this->assertSame('صورة التحويل غير واضحة ورقم الحساب غير مطابق', $fresh->reject_reason);
        $this->assertSame($this->superAdmin->id, (int) $fresh->verified_by);
        $this->assertNotNull($fresh->verified_at);
        $this->assertNull($fresh->tenant_id);
        $this->assertSame('شركة خالد العقارية', $fresh->metadata['billing_company_name']);

        Mail::assertQueued(BankTransferRejectedMail::class, function (BankTransferRejectedMail $mail) use ($bill) {
            return $mail->hasTo('khaled.bank@example.com')
                && $mail->bill->id === $bill->id
                && str_contains($mail->reason ?? '', 'صورة التحويل غير واضحة');
        });
    }

    public function test_super_admin_can_confirm_payment_for_rejected_bill_and_creates_tenant(): void
    {
        $plan = Plan::first() ?? Plan::create([
            'name' => 'باقة الاختبار '.rand(100, 999),
            'slug' => 'plan-test-'.uniqid(),
            'price' => 300,
            'price_after_discount' => 300,
            'subscription_duration_type' => 'limited',
            'subscription_billing_cycle' => 'monthly',
            'included_users' => 3,
            'properties_limit' => 20,
            'storage_quota_gb' => 5,
            'active_auctions_limit' => 10,
            'is_active' => true,
            'created_by' => 1,
        ]);

        $tenantId = 'test_tn_'.rand(10000, 99999);
        $tenant = Tenant::create([
            'id' => $tenantId,
            'folderName' => $tenantId,
            'db_database' => 'test_db_'.$tenantId,
            'db_username' => 'test_user',
            'db_password' => 'test_pass',
            'commercial_number' => '1010101010',
            'company_name' => 'مؤسسة خالد الحديثة',
            'primary_contact_name' => 'خالد الحربي',
            'primary_contact_email' => 'khaled.modern@example.com',
            'primary_contact_phone' => '0555555556',
        ]);
        $this->cleanupTenantIds[] = $tenant->id;

        $user = new User([
            'id' => 1,
            'name' => 'خالد الحربي',
            'email' => 'khaled.modern@example.com',
            'global_uid' => Str::uuid()->toString(),
        ]);

        $mockCreator = Mockery::mock(TenantCreator::class);
        $mockCreator->shouldReceive('createTenantEnvironment')->once()->andReturn([$tenant, $user]);
        $this->app->instance(TenantCreator::class, $mockCreator);

        $bill = Bill::create([
            'tenant_id' => null,
            'amount' => 300,
            'original_amount' => 300,
            'type' => BillType::NEW_SUBSCRIPTION,
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'status' => BillStatus::REJECTED,
            'reject_reason' => 'تم الرفض سابقاً لعدم استلام المبلغ',
            'metadata' => [
                'plan_id' => $plan->id,
                'billing_name' => 'خالد الحربي',
                'billing_email' => 'khaled.modern@example.com',
                'billing_phone' => '0555555556',
                'billing_company_name' => 'مؤسسة خالد الحديثة',
                'plain_password' => 'SecurePass123!',
            ],
        ]);
        $this->cleanupBillIds[] = $bill->id;

        $response = $this->post($this->adminUrl("bills/{$bill->id}/verify"));

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $fresh = $bill->fresh();
        $this->assertSame(BillStatus::PAID, $fresh->status);
        $this->assertSame($tenant->id, $fresh->tenant_id);
        $this->assertNotNull($fresh->subscription_id);
        $this->assertNull($fresh->reject_reason);
        $this->assertNotNull($fresh->paid_at);
        $this->assertSame($this->superAdmin->id, (int) $fresh->verified_by);
    }

    public function test_rejected_bill_show_page_renders_confirm_payment_option_for_admin(): void
    {
        $bill = Bill::create([
            'tenant_id' => null,
            'amount' => 500,
            'original_amount' => 500,
            'type' => BillType::NEW_SUBSCRIPTION,
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'status' => BillStatus::REJECTED,
            'reject_reason' => 'الإيصال قديم',
            'metadata' => [
                'billing_name' => 'عبدالله الدوسري',
                'billing_email' => 'abdullah@example.com',
                'billing_phone' => '0500000000',
                'billing_company_name' => 'شركة الدوسري العقارية',
            ],
        ]);
        $this->cleanupBillIds[] = $bill->id;

        $response = $this->get($this->adminUrl("bills/{$bill->id}"));

        $response->assertStatus(200);
        $response->assertSee('الإيصال قديم');
        $response->assertSee('تأكيد الدفع');
        $response->assertSee('شركة الدوسري العقارية');
        $response->assertSee(route('admin.bills.verify', $bill->id));
    }
}
