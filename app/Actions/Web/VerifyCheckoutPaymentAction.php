<?php

declare(strict_types=1);

namespace App\Actions\Web;

use App\Contracts\Payments\PaymentGateway;
use App\Enums\Admin\Bills\BillStatus;
use App\Enums\Admin\Bills\PaymentMethod;
use App\Models\Admin\PaymentAttempt;
use App\Services\Web\CouponRedemptionService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class VerifyCheckoutPaymentAction
{
    public function __construct(private readonly PaymentGateway $gateway, private readonly CouponRedemptionService $coupons) {}

    public function execute(string $billNumber, string $paymentId): PaymentAttempt
    {
        if ($billNumber === '' || $paymentId === '') {
            throw new RuntimeException('بيانات عملية الدفع غير مكتملة.');
        }

        $payment = $this->gateway->fetchPayment($paymentId);

        $connection = (string) config('tenancy.database.central_connection');
        if ($connection === '') {
            throw new RuntimeException('The central database connection must be configured.');
        }

        return DB::connection($connection)->transaction(function () use ($billNumber, $paymentId, $payment, $connection): PaymentAttempt {
            $attempt = (new PaymentAttempt())->setConnection($connection)->newQuery()
                ->where('provider', 'moyasar')
                ->whereHas('bill', fn ($query) => $query->where('bill_number', $billNumber))
                ->with('bill')
                ->lockForUpdate()
                ->firstOrFail();

            if ($attempt->processed_at !== null) {
                return $attempt;
            }

            if ($attempt->expires_at !== null && $attempt->expires_at->isPast()) {
                throw new RuntimeException('انتهت صلاحية محاولة الدفع.');
            }

            if (($payment['status'] ?? null) !== 'paid') {
                throw new RuntimeException('لم تؤكد بوابة الدفع نجاح العملية.');
            }

            if (! hash_equals($paymentId, (string) ($payment['id'] ?? ''))) {
                throw new RuntimeException('معرّف عملية الدفع المستلم لا يطابق استجابة المزود.');
            }

            $metadata = $payment['metadata'] ?? null;
            if (! is_array($metadata)
                || ! hash_equals($attempt->checkout_token, (string) ($metadata['checkout_token'] ?? ''))
                || ! hash_equals($billNumber, (string) ($metadata['bill_number'] ?? ''))) {
                throw new RuntimeException('مرجع عملية الدفع لا يطابق الفاتورة المطلوبة.');
            }

            if (strtoupper((string) ($payment['currency'] ?? '')) !== 'SAR') {
                throw new RuntimeException('عملة الدفع لا تطابق العملة المتوقعة.');
            }

            $paidAmount = (int) ($payment['amount'] ?? 0) / 100;
            if (abs($paidAmount - (float) $attempt->amount) > 0.01) {
                throw new RuntimeException('مبلغ الدفع لا يطابق الفاتورة.');
            }

            $claimed = (new PaymentAttempt())->setConnection($connection)->newQuery()->where('provider_payment_id', $paymentId)->lockForUpdate()->first();
            if ($claimed && $claimed->getKey() !== $attempt->getKey()) {
                throw new RuntimeException('معرّف عملية الدفع مستخدم لفاتورة أخرى.');
            }

            $attempt->update([
                'provider_payment_id' => $paymentId,
                'status' => 'paid_pending_provision',
                'provider_payload' => $payment,
                'paid_at' => now(),
            ]);
            $attempt->bill->update([
                'status' => BillStatus::PAID,
                'payment_method' => PaymentMethod::ONLINE,
                'settled_amount' => $attempt->amount,
                'paid_at' => now(),
            ]);
            $this->coupons->redeem($attempt->bill);

            return $attempt->fresh('bill');
        });
    }
}
