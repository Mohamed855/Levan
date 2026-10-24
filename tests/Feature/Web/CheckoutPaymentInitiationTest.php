<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Actions\Web\InitiateCheckoutPaymentAction;
use App\Enums\Admin\Bills\BillStatus;
use App\Models\Admin\Bank;
use App\Models\Admin\Coupon;
use App\Models\Admin\Plans\Plan;
use App\Services\Web\CouponQuoteService;
use App\Services\Web\CouponRedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CheckoutPaymentInitiationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_transfer_creates_one_pending_bill_and_one_private_attempt_for_the_checkout_token(): void
    {
        Storage::fake('local');
        $plan = Plan::query()->create(['name' => 'خطة', 'price' => '100.00', 'is_active' => true, 'included_users' => 1, 'limits_mode' => 'limited']);
        $bank = Bank::query()->create(['name' => 'بنك', 'account_name' => 'LOT', 'iban' => 'SA1234567890123456789012', 'is_active' => true]);
        $checkout = $this->checkout($plan);

        $first = app(InitiateCheckoutPaymentAction::class)->execute($checkout, $plan, 'bank_transfer', $bank->id, UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'));
        $second = app(InitiateCheckoutPaymentAction::class)->execute($checkout, $plan, 'bank_transfer', $bank->id, UploadedFile::fake()->create('second.pdf', 10, 'application/pdf'));

        self::assertSame($first->id, $second->id);
        $this->assertDatabaseCount('bills', 1);
        $this->assertDatabaseCount('payment_attempts', 1);
        self::assertSame('pending_verification', $first->status);
        self::assertSame('100.00', $first->bill->amount);
        self::assertSame('شركة اختبار', $first->bill->metadata['provisioning']['billing_company_name']);
        self::assertArrayNotHasKey('billing_password_hash', $first->bill->metadata);
        self::assertArrayNotHasKey('otp_hash', $first->bill->metadata);
        self::assertArrayNotHasKey('expires_at', $first->bill->metadata);
        Storage::disk('local')->assertExists($first->receipt_path);
    }

    public function test_coupon_quote_uses_the_server_plan_price_and_discount_cap(): void
    {
        $plan = Plan::query()->create(['name' => 'خطة', 'price' => '100.00', 'is_active' => true, 'included_users' => 1, 'limits_mode' => 'limited']);
        Coupon::query()->create(['name' => 'خصم', 'code' => 'SAVE50', 'discount_type' => 'percentage', 'discount_value' => 50, 'max_discount' => 30, 'is_active' => true]);

        $quote = app(CouponQuoteService::class)->quote($plan, 'save50');

        self::assertSame(30.0, $quote['discount_amount']);
        self::assertSame(70.0, $quote['total_amount']);
        self::assertSame('SAVE50', $quote['coupon_code']);
    }

    public function test_coupon_use_is_reserved_once_and_redeemed_once_for_a_paid_bill(): void
    {
        Storage::fake('local');
        $plan = Plan::query()->create(['name' => 'خطة', 'price' => '100.00', 'is_active' => true, 'included_users' => 1, 'limits_mode' => 'limited']);
        $coupon = Coupon::query()->create(['name' => 'خصم', 'code' => 'SAVE10', 'discount_type' => 'fixed', 'discount_value' => 10, 'usage_limit' => 1, 'is_active' => true]);
        $bank = Bank::query()->create(['name' => 'بنك', 'account_name' => 'LOT', 'iban' => 'SA1234567890123456789012', 'is_active' => true]);
        $checkout = $this->checkout($plan);
        $checkout['pricing'] = app(CouponQuoteService::class)->quote($plan, 'SAVE10');

        $attempt = app(InitiateCheckoutPaymentAction::class)->execute($checkout, $plan, 'bank_transfer', $bank->id, UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'));
        $attempt->bill->update(['status' => BillStatus::PAID]);
        app(CouponRedemptionService::class)->redeem($attempt->bill);
        app(CouponRedemptionService::class)->redeem($attempt->bill);

        self::assertSame('90.00', $attempt->bill->amount);
        self::assertSame(1, $coupon->fresh()->used_count);
        $this->assertDatabaseHas('coupon_redemptions', ['bill_id' => $attempt->bill_id, 'coupon_id' => $coupon->id, 'status' => 'redeemed']);
    }

    private function checkout(Plan $plan): array
    {
        return [
            'checkout_token' => '3d5fb4db-545a-45a3-b1c9-5c6670568a84', 'plan_id' => $plan->id,
            'billing_name' => 'مالك الاختبار', 'billing_email' => 'owner@example.test', 'billing_phone' => '0500000000',
            'billing_password_hash' => bcrypt('password123'), 'billing_company_name' => 'شركة اختبار',
            'billing_commercial_number' => '1234567890', 'billing_tax_number' => '300000000000003',
            'billing_city_id' => 1, 'billing_address' => 'الرياض',
            'pricing' => app(CouponQuoteService::class)->quote($plan),
        ];
    }
}
