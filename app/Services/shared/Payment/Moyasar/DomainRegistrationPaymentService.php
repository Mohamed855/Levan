<?php

namespace App\Services\shared\Payment\Moyasar;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Exceptions\Payment\PaymentInitiationException;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\ControlDomain\ControlDomain;
use App\Models\Admin\Payments\DomainPaymentAttempt;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Domain;
use App\Services\shared\NameCheap\CheckMultipleDomainService;
use App\Services\shared\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stancl\Tenancy\Facades\Tenancy;

class DomainRegistrationPaymentService
{
    public function __construct(
        private CheckMultipleDomainService $domainAvailability,
        private MoyasarFormConfiguration $moyasarFormConfiguration,
        private PaymentGatewayInterface $paymentGateway,
    ) {}

    public function start(string $tenantId, string $domain): DomainPaymentAttempt
    {
        $this->ensureTestMode();
        $domain = strtolower($domain);
        $availability = $this->domainAvailability->checkSingleDomain($domain, true);

        if (! ($availability['success'] ?? false)) {
            throw new PaymentInitiationException('تعذر التحقق من توفر النطاق. حاول مرة أخرى.');
        }

        if (($availability['available'] ?? false) !== true) {
            throw new PaymentInitiationException('هذا النطاق لم يعد متاحاً للشراء.');
        }

        return Tenancy::central(function () use ($tenantId, $domain) {
            $price = $this->resolvePrice($domain);
            $subscription = Subscription::query()
                ->with('currentPeriod')
                ->where('tenant_id', $tenantId)
                ->latest('id')
                ->firstOrFail();

            $this->moyasarFormConfiguration->getPublishableKey();
            $this->moyasarFormConfiguration->amountInHalala((float) $price);

            return DomainPaymentAttempt::create([
                'public_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'subscription_id' => $subscription->id,
                'period_id' => $subscription->currentPeriod?->id,
                'operation' => 'register',
                'domain' => $domain,
                'years' => 1,
                'amount' => $price,
                'currency' => 'SAR',
                'status' => 'pending',
            ]);
        });
    }

    public function checkoutData(DomainPaymentAttempt $attempt, string $callbackUrl): array
    {
        if ($attempt->status !== 'pending') {
            throw new PaymentInitiationException('لا يمكن متابعة عملية الدفع في حالتها الحالية.');
        }

        return [
            'amountInHalala' => $this->moyasarFormConfiguration->amountInHalala((float) $attempt->amount),
            'callbackUrl' => $callbackUrl,
            'description' => match ($attempt->operation) {
                'renew' => 'تجديد النطاق ' . $attempt->domain,
                'transfer' => 'نقل النطاق ' . $attempt->domain,
                default => 'شراء النطاق ' . $attempt->domain,
            },
            'mode' => $this->moyasarFormConfiguration->getMode(),
            'publishableKey' => $this->moyasarFormConfiguration->getPublishableKey(),
        ];
    }

    public function verifyAndMarkPaid(string $publicId, string $tenantId, string $paymentId, string $expectedOperation): DomainPaymentAttempt
    {
        return Tenancy::central(function () use ($publicId, $tenantId, $paymentId, $expectedOperation) {
            return DB::transaction(function () use ($publicId, $tenantId, $paymentId, $expectedOperation) {
                $attempt = DomainPaymentAttempt::query()
                    ->where('public_id', $publicId)
                    ->where('tenant_id', $tenantId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($attempt->operation !== $expectedOperation) {
                    throw new PaymentVerificationException('بيانات عملية الدفع غير متطابقة.');
                }

                if (in_array($attempt->status, ['paid', 'processing', 'completed'], true)) {
                    return $attempt;
                }

                if ($attempt->status !== 'pending') {
                    throw new PaymentVerificationException('هذه العملية غير متاحة للمعالجة.');
                }

                $payment = $this->paymentGateway->getPayment($paymentId);
                $expectedAmount = $this->moyasarFormConfiguration->amountInHalala((float) $attempt->amount);

                if (($payment['status'] ?? null) !== 'paid'
                    || ($payment['id'] ?? null) !== $paymentId
                    || strtoupper((string) ($payment['currency'] ?? '')) !== $attempt->currency
                    || (int) ($payment['amount'] ?? 0) !== $expectedAmount) {
                    throw new PaymentVerificationException('تعذر التحقق من عملية الدفع.');
                }

                $paymentWasUsed = DomainPaymentAttempt::query()
                    ->where('gateway_payment_id', $paymentId)
                    ->whereKeyNot($attempt->id)
                    ->exists()
                    || Bill::query()->where('gateway_payment_id', $paymentId)->exists();

                if ($paymentWasUsed) {
                    throw new PaymentVerificationException('تمت معالجة عملية الدفع هذه مسبقاً.');
                }

                $attempt->update([
                    'gateway_payment_id' => $payment['id'],
                    'status' => 'paid',
                    'paid_at' => now(),
                    'metadata' => array_merge($attempt->metadata ?? [], ['gateway_response' => $payment]),
                ]);

                Bill::firstOrCreate(
                    ['gateway_payment_id' => $payment['id']],
                    [
                        'tenant_id' => $attempt->tenant_id,
                        'subscription_id' => $attempt->subscription_id,
                        'period_id' => $attempt->period_id,
                        'amount' => $attempt->amount,
                        'original_amount' => $attempt->amount,
                        'discount_amount' => 0,
                        'type' => match ($attempt->operation) {
                            'renew' => BillType::RENEWAL_DOMAIN,
                            'transfer' => BillType::TRANSFER_DOMAIN,
                            default => BillType::NEW_DOMAIN,
                        },
                        'payment_method' => PaymentMethod::ONLINE,
                        'status' => BillStatus::PAID,
                        'paid_at' => now(),
                        'metadata' => [
                            'domain_payment_attempt_id' => $attempt->id,
                            'domain' => $attempt->domain,
                            'operation' => $attempt->operation,
                        ],
                    ],
                );

                return $attempt->refresh();
            });
        });
    }

    public function renewalPreview(string $tenantId, int $domainId): array
    {
        return Tenancy::central(function () use ($tenantId, $domainId) {
            $domain = $this->findRenewableDomain($tenantId, $domainId);
            $unitPrice = $this->resolvePrice($domain->domain);

            return [
                'domain' => $domain,
                'unit_price' => $unitPrice,
            ];
        });
    }

    public function startRenewal(string $tenantId, int $domainId, int $years): DomainPaymentAttempt
    {
        $this->ensureTestMode();

        return Tenancy::central(function () use ($tenantId, $domainId, $years) {
            $domain = $this->findRenewableDomain($tenantId, $domainId);
            $unitPrice = $this->resolvePrice($domain->domain);
            $amount = round($unitPrice * $years, 2);
            $subscription = Subscription::query()
                ->with('currentPeriod')
                ->where('tenant_id', $tenantId)
                ->latest('id')
                ->firstOrFail();

            $this->moyasarFormConfiguration->getPublishableKey();
            $this->moyasarFormConfiguration->amountInHalala($amount);

            return DomainPaymentAttempt::create([
                'public_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'subscription_id' => $subscription->id,
                'period_id' => $subscription->currentPeriod?->id,
                'operation' => 'renew',
                'domain' => $domain->domain,
                'years' => $years,
                'amount' => $amount,
                'currency' => 'SAR',
                'status' => 'pending',
                'metadata' => [
                    'domain_id' => $domain->id,
                    'unit_price' => $unitPrice,
                ],
            ]);
        });
    }

    public function startTransfer(string $tenantId, string $domain, string $eppCode): DomainPaymentAttempt
    {
        $this->ensureTestMode();
        $domain = strtolower($domain);
        $availability = $this->domainAvailability->checkSingleDomain($domain, true);

        if (! ($availability['success'] ?? false)) {
            throw new PaymentInitiationException('تعذر التحقق من حالة النطاق. حاول مرة أخرى.');
        }

        if (($availability['available'] ?? null) !== false) {
            throw new PaymentInitiationException('لا يمكن نقل نطاق متاح للحجز أو لم يعد مسجلاً.');
        }

        return Tenancy::central(function () use ($tenantId, $domain, $eppCode) {
            $transferExists = Domain::query()
                ->where('domain', $domain)
                ->where('type', 'transfered')
                ->exists();

            if ($transferExists) {
                throw new PaymentInitiationException('يوجد طلب نقل قائم لهذا النطاق.');
            }

            $price = $this->resolvePrice($domain);
            $subscription = Subscription::query()
                ->with('currentPeriod')
                ->where('tenant_id', $tenantId)
                ->latest('id')
                ->firstOrFail();

            $this->moyasarFormConfiguration->getPublishableKey();
            $this->moyasarFormConfiguration->amountInHalala($price);

            return DomainPaymentAttempt::create([
                'public_id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'subscription_id' => $subscription->id,
                'period_id' => $subscription->currentPeriod?->id,
                'operation' => 'transfer',
                'domain' => $domain,
                'epp_code' => $eppCode,
                'years' => 1,
                'amount' => $price,
                'currency' => 'SAR',
                'status' => 'pending',
            ]);
        });
    }

    private function resolvePrice(string $domain): float
    {
        $extension = '.' . ltrim((string) strrchr($domain, '.'), '.');

        $controlDomain = ControlDomain::query()
            ->where('is_active', true)
            ->whereIn('domain_extension', [$extension, ltrim($extension, '.')])
            ->first();

        if (! $controlDomain) {
            throw new PaymentInitiationException('امتداد النطاق غير متاح للشراء.');
        }

        return (float) $controlDomain->price;
    }

    private function findRenewableDomain(string $tenantId, int $domainId): Domain
    {
        return Domain::query()
            ->whereKey($domainId)
            ->where('tenant_id', $tenantId)
            ->where('type', 'registered')
            ->firstOrFail();
    }

    private function ensureTestMode(): void
    {
        if ($this->moyasarFormConfiguration->getMode() !== 'test') {
            throw new PaymentInitiationException('عمليات النطاقات مفعلة حالياً في بيئة الاختبار فقط.');
        }
    }
}
