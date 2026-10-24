<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Admin\Bills\BillStatus;
use App\Enums\Admin\Bills\BillType;
use App\Enums\Admin\Bills\PaymentMethod;
use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Admin\AdminUser;
use App\Models\Admin\Bill;
use App\Models\Admin\PaymentAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class BillManagementRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_bill_details_render_enum_labels_and_available_transitions(): void
    {
        $bill = $this->bill();
        $this->actingAs($this->admin(), 'admin')->get(route('admin.bills.show', $bill))
            ->assertOk()->assertSee(BillStatus::PENDING->label())->assertSee(BillType::RENEWAL->label())
            ->assertViewHas('allowedTransitions', ['paid', 'overdue', 'cancelled']);
    }

    public function test_bill_reviewer_can_open_the_printable_invoice(): void
    {
        $bill = $this->bill();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.bills.print', $bill))
            ->assertOk()
            ->assertSee('فاتورة')
            ->assertSee($bill->bill_number);
    }

    public function test_status_update_passes_validated_data_and_actor_to_the_action(): void
    {
        $bill = $this->bill();
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->patch(route('admin.bills.status', $bill), [
            'status' => 'paid', 'payment_method' => 'bank_transfer', 'settled_amount' => '100.00', 'notes' => 'تمت المراجعة',
        ])->assertRedirect();

        $bill->refresh();
        $this->assertSame(BillStatus::PAID, $bill->status);
        $this->assertSame(PaymentMethod::BANK_TRANSFER, $bill->payment_method);
        $this->assertSame('100.00', $bill->settled_amount);
        $this->assertNotNull($bill->paid_at);
        $this->assertSame('pending', $bill->metadata['history'][0]['from_status']);
        $this->assertSame($admin->id, $bill->metadata['history'][0]['admin_id']);
    }

    public function test_invalid_payment_method_is_rejected_before_enum_casting(): void
    {
        $bill = $this->bill();
        $this->actingAs($this->admin(), 'admin')->patchJson(route('admin.bills.status', $bill), [
            'status' => 'paid', 'payment_method' => 'unknown_provider',
        ])->assertUnprocessable()->assertJsonValidationErrors('payment_method');
        $this->assertSame(BillStatus::PENDING, $bill->fresh()->status);
    }

    public function test_disallowed_transition_is_rejected_without_mutation(): void
    {
        $bill = $this->bill();
        $bill->update(['status' => BillStatus::REFUNDED]);
        $this->actingAs($this->admin(), 'admin')->patchJson(route('admin.bills.status', $bill), [
            'status' => 'paid',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertSame(BillStatus::REFUNDED, $bill->fresh()->status);
    }

    public function test_checkout_bill_cannot_be_marked_paid_through_the_generic_status_endpoint(): void
    {
        $bill = $this->bill();
        $bill->update(['metadata' => ['checkout_source' => 'site_checkout']]);

        $this->actingAs($this->admin(), 'admin')
            ->patchJson(route('admin.bills.status', $bill), ['status' => 'paid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame(BillStatus::PENDING, $bill->fresh()->status);
    }

    public function test_bill_reviewer_can_download_only_the_bill_bank_transfer_receipt(): void
    {
        Storage::fake('local');
        $bill = $this->bill();
        $bill->update(['metadata' => ['checkout_source' => 'site_checkout']]);
        $path = 'checkout-receipts/3d5fb4db-545a-45a3-b1c9-5c6670568a84/receipt.pdf';
        Storage::disk('local')->put($path, 'receipt-content');
        PaymentAttempt::query()->create([
            'bill_id' => $bill->id,
            'checkout_token' => '3d5fb4db-545a-45a3-b1c9-5c6670568a84',
            'provider' => 'bank_transfer',
            'payment_method' => 'bank_transfer',
            'status' => 'pending_verification',
            'amount' => '100.00',
            'receipt_path' => $path,
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.bills.bank-transfer.receipt', $bill))
            ->assertOk()
            ->assertDownload('bank-transfer-TEST-BILL-001.pdf');
    }

    private function bill(): Bill
    {
        return Bill::query()->create(['bill_number' => 'TEST-BILL-001', 'type' => BillType::RENEWAL, 'amount' => '100.00', 'status' => BillStatus::PENDING]);
    }

    private function admin(): AdminUser
    {
        $admin = AdminUser::factory()->create();
        $role = Role::query()->create(['guard' => 'admin', 'name' => 'bill_reviewer', 'display_name' => 'مراجع الفواتير']);
        foreach (['bills.view', 'bills.update'] as $name) {
            $permission = Permission::query()->firstOrCreate(['guard' => 'admin', 'name' => $name], ['display_name' => $name]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
        $admin->assignRole($role);
        return $admin;
    }
}
