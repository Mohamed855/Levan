<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Admin\Plans\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CheckoutVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_cannot_be_completed_before_checkout_email_is_verified(): void
    {
        $plan = Plan::query()->create([
            'name' => 'خطة اختبار', 'price' => '100.00', 'is_active' => true,
            'included_users' => 1, 'limits_mode' => 'limited',
        ]);
        $sessionKey = 'web.checkout.'.$plan->getKey();

        $response = $this->withSession([$sessionKey => [
            'plan_id' => $plan->getKey(), 'expires_at' => now()->addHour()->timestamp,
            'billing_email' => 'owner@example.test',
        ]])->post(route('site.plans.checkout.payment', $plan), ['payment_method' => 'card']);

        $response->assertRedirect(route('site.plans.checkout', $plan));
        $response->assertSessionHasErrors('payment_method');
        self::assertNotNull(session($sessionKey));
    }
}
