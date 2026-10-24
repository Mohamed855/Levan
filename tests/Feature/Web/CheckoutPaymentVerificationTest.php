<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Actions\Web\VerifyCheckoutPaymentAction;
use App\Contracts\Payments\PaymentGateway;
use App\Enums\Admin\Bills\BillStatus;
use App\Enums\Admin\Bills\BillType;
use App\Enums\Admin\Bills\PaymentMethod;
use App\Models\Admin\Bill;
use App\Models\Admin\PaymentAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class CheckoutPaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_with_a_different_checkout_token_cannot_settle_the_bill(): void
    {
        $attempt = $this->attempt();
        app()->instance(PaymentGateway::class, new class implements PaymentGateway {
            public function fetchPayment(string $paymentId): array
            {
                return ['id' => $paymentId, 'status' => 'paid', 'amount' => 10000, 'currency' => 'SAR', 'metadata' => ['checkout_token' => 'wrong-token']];
            }
        });

        try {
            app(VerifyCheckoutPaymentAction::class)->execute('INV-VERIFY-001', 'pay-verify-001');
            self::fail('The mismatched payment metadata should be rejected.');
        } catch (RuntimeException) {
            // Expected: provider metadata does not belong to this payment attempt.
        }

        self::assertSame(BillStatus::PENDING, $attempt->bill->fresh()->status);
    }

    private function attempt(): PaymentAttempt
    {
        $bill = Bill::query()->create([
            'bill_number' => 'INV-VERIFY-001',
            'type' => BillType::NEW_SUBSCRIPTION,
            'amount' => '100.00',
            'payment_method' => PaymentMethod::ONLINE,
            'status' => BillStatus::PENDING,
            'metadata' => ['checkout_source' => 'site_checkout'],
        ]);

        return PaymentAttempt::query()->create([
            'bill_id' => $bill->id,
            'checkout_token' => '3d5fb4db-545a-45a3-b1c9-5c6670568a84',
            'provider' => 'moyasar',
            'payment_method' => 'card',
            'status' => 'initiated',
            'amount' => '100.00',
            'expires_at' => now()->addHour(),
        ])->load('bill');
    }
}
