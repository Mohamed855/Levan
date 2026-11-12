<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Admin\Coupons\CouponDiscountType;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Plans\Plan;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class LandingCheckoutCouponPersistenceTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    public function test_applied_coupon_persists_on_page_refresh_and_clears_on_explicit_removal(): void
    {
        $plan = Plan::query()->where('code', '!=', 'trial_7_days')->firstOrFail();
        $admin = AdminUser::firstOrFail();

        $coupon = Coupon::create([
            'admin_user_id' => $admin->id,
            'created_by' => $admin->id,
            'name' => 'خصم الاستدامة',
            'code' => 'STAY20',
            'discount_type' => CouponDiscountType::PERCENTAGE,
            'discount_value' => 20,
            'is_active' => true,
            'start_date' => now()->subDay(),
            'expiry_date' => now()->addDays(10),
        ]);

        // 1. Apply coupon via AJAX endpoint
        $applyResponse = $this->postJson('/api/central/coupon/validate-coupon', [
            'coupon_code' => $coupon->code,
            'total' => (float) $plan->price_after_discount,
        ]);

        $applyResponse->assertOk();
        $applyResponse->assertJson(['success' => true]);

        // 2. Refresh checkout page (Simulate browser reload)
        $checkoutResponse = $this->get(route('site.plans.checkout', $plan->id));

        $checkoutResponse->assertOk();
        // Coupon code appears in hidden field and display input
        $checkoutResponse->assertSee('value="STAY20"', false);
        // Display input is marked disabled
        $checkoutResponse->assertSee('id="coupon_code_input"', false);
        // Discount row is visible and rendered
        $checkoutResponse->assertSee('id="discount-row"', false);
        $checkoutResponse->assertSee('id="discount-display"', false);

        // Calculate expected pricing
        $expectedDiscount = round((float) $plan->price_after_discount * 0.20, 2);
        $expectedTotal = (float) $plan->price_after_discount - $expectedDiscount;
        $checkoutResponse->assertSee(number_format($expectedTotal, 2));

        // 3. Explicitly remove coupon via removal endpoint
        $removeResponse = $this->postJson('/api/central/coupon/remove-coupon');
        $removeResponse->assertOk();
        $removeResponse->assertJson(['success' => true]);

        // 4. Refresh checkout page after removal and ensure original price is restored
        $refreshedAfterRemove = $this->get(route('site.plans.checkout', $plan->id));
        $refreshedAfterRemove->assertOk();
        $refreshedAfterRemove->assertDontSee('value="STAY20"', false);
        $refreshedAfterRemove->assertSee(number_format($plan->price_after_discount, 2));
    }
}

