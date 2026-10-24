<?php

declare(strict_types=1);

namespace App\Actions\Web;

use App\Enums\Admin\Bills\BillStatus;
use App\Enums\Admin\Bills\BillType;
use App\Enums\Admin\Bills\PaymentMethod;
use App\Models\Admin\Bank;
use App\Models\Admin\Bill;
use App\Models\Admin\PaymentAttempt;
use App\Models\Admin\Plans\Plan;
use App\Services\Web\CouponRedemptionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class InitiateCheckoutPaymentAction
{
    public function __construct(private readonly CouponRedemptionService $coupons) {}

    /** @param array<string, mixed> $checkout */
    public function execute(array $checkout, Plan $plan, string $paymentMethod, ?int $bankId = null, ?UploadedFile $receipt = null): PaymentAttempt
    {
        $checkoutToken = (string) ($checkout['checkout_token'] ?? '');
        if (! Str::isUuid($checkoutToken)) {
            throw ValidationException::withMessages(['payment_method' => 'محاولة الدفع غير صالحة.']);
        }

        $existing = PaymentAttempt::query()->where('checkout_token', $checkoutToken)->first();
        if ($existing) {
            return $existing;
        }

        $receiptPath = $this->storeReceipt($checkoutToken, $paymentMethod, $bankId, $receipt);

        try {
            return DB::transaction(function () use ($checkout, $checkoutToken, $plan, $paymentMethod, $bankId, $receiptPath): PaymentAttempt {
                $existing = PaymentAttempt::query()->where('checkout_token', $checkoutToken)->lockForUpdate()->first();
                if ($existing) {
                    if ($receiptPath !== null) {
                        Storage::disk('local')->delete($receiptPath);
                    }

                    return $existing;
                }

                $isBankTransfer = $paymentMethod === 'bank_transfer';
                $pricing = $checkout['pricing'] ?? null;
                if (! is_array($pricing) || ! isset($pricing['total_amount'])) {
                    throw ValidationException::withMessages(['payment_method' => 'تسعير الطلب غير مكتمل.']);
                }

                $bill = Bill::query()->create([
                    'bill_number' => 'INV-'.now()->format('Ym').'-'.Str::upper(Str::random(12)),
                    'type' => BillType::NEW_SUBSCRIPTION,
                    'amount' => $pricing['total_amount'],
                    'payment_method' => $isBankTransfer ? PaymentMethod::BANK_TRANSFER : PaymentMethod::ONLINE,
                    'status' => $isBankTransfer ? BillStatus::PENDING_VERIFICATION : BillStatus::PENDING,
                    'due_at' => $isBankTransfer ? now()->addDays(7) : now()->addHour(),
                    'metadata' => $this->checkoutMetadata($checkout, $plan, $bankId, $pricing),
                ]);

                $this->coupons->reserve($bill, $pricing);

                return PaymentAttempt::query()->create([
                    'bill_id' => $bill->getKey(),
                    'checkout_token' => $checkoutToken,
                    'provider' => $isBankTransfer ? 'bank_transfer' : 'moyasar',
                    'payment_method' => $paymentMethod,
                    'status' => $isBankTransfer ? 'pending_verification' : 'initiated',
                    'amount' => $bill->amount,
                    'receipt_path' => $receiptPath,
                    'expires_at' => $bill->due_at,
                ]);
            });
        } catch (Throwable $exception) {
            if ($receiptPath !== null) {
                Storage::disk('local')->delete($receiptPath);
            }

            throw $exception;
        }
    }

    private function storeReceipt(string $checkoutToken, string $paymentMethod, ?int $bankId, ?UploadedFile $receipt): ?string
    {
        if ($paymentMethod !== 'bank_transfer') {
            return null;
        }

        $bankIsActive = $bankId !== null && Bank::query()->whereKey($bankId)->where('is_active', true)->exists();
        if (! $bankIsActive || ! $receipt instanceof UploadedFile) {
            throw ValidationException::withMessages(['bank_id' => 'الحساب البنكي المحدد غير متاح.']);
        }

        return $receipt->store('checkout-receipts/'.$checkoutToken, 'local');
    }

    /** @param array<string, mixed> $checkout @param array<string, mixed> $pricing @return array<string, mixed> */
    private function checkoutMetadata(array $checkout, Plan $plan, ?int $bankId, array $pricing): array
    {
        $provisioning = [];
        foreach ([
            'billing_company_name',
            'billing_commercial_number',
            'billing_tax_number',
            'billing_city_id',
            'billing_address',
            'billing_name',
            'billing_email',
            'billing_phone',
            'billing_password_hash',
        ] as $key) {
            if (array_key_exists($key, $checkout)) {
                $provisioning[$key] = $checkout[$key];
            }
        }

        return [
            'checkout_source' => 'site_checkout',
            'plan_id' => $plan->getKey(),
            'bank_id' => $bankId,
            'pricing' => $pricing,
            'provisioning' => $provisioning,
        ];
    }
}
