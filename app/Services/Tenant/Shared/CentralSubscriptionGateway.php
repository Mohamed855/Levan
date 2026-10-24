<?php

declare(strict_types=1);

namespace App\Services\Tenant\Shared;

use App\Enums\Admin\Bills\BillStatus;
use App\Enums\Admin\Bills\BillType;
use App\Enums\Admin\Bills\PaymentMethod;
use App\Models\Admin\Bill;
use App\Models\Admin\PaymentAttempt;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscriptions\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Stancl\Tenancy\Exceptions\TenancyNotInitializedException;

/** Tenant-facing commercial reads and payment intent creation, always on central storage. */
final class CentralSubscriptionGateway
{
    public function current(): ?Subscription
    {
        return $this->subscriptions()->with(['plan', 'currentPeriod'])->where('tenant_id', $this->tenantId())->latest('id')->first();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Plan> */
    public function availablePlans()
    {
        return $this->plans()->publiclyAvailable()->orderBy('sort_order')->get();
    }

    public function initiateRenewal(string $paymentMethod): PaymentAttempt
    {
        $subscription = $this->activeSubscription();
        if ($subscription === null || $subscription->plan === null) {
            throw new RuntimeException('لا يوجد اشتراك قابل للتجديد.');
        }

        return $this->createAttempt($subscription, BillType::RENEWAL, $paymentMethod, (float) $subscription->plan->price_after_discount, []);
    }

    public function initiateLicensePurchase(int $count, string $paymentMethod): PaymentAttempt
    {
        if ($count < 1) {
            throw new RuntimeException('عدد التراخيص الإضافية غير صالح.');
        }
        $subscription = $this->activeSubscription();
        if ($subscription === null || $subscription->plan === null) {
            throw new RuntimeException('لا يوجد اشتراك نشط لشراء تراخيص إضافية.');
        }

        return $this->createAttempt(
            $subscription,
            BillType::LICENSE_PURCHASE,
            $paymentMethod,
            (float) $subscription->plan->extra_user_license_price * $count,
            ['license_count' => $count, 'license_unit_price' => (float) $subscription->plan->extra_user_license_price],
        );
    }

    public function attemptForCurrentTenant(string $billNumber): PaymentAttempt
    {
        return $this->attempts()->with('bill')->whereHas('bill', function (Builder $query) use ($billNumber): void {
            $query->where('tenant_id', $this->tenantId())->where('bill_number', $billNumber);
        })->where('provider', 'moyasar')->firstOrFail();
    }

    private function createAttempt(Subscription $subscription, BillType $type, string $paymentMethod, float $amount, array $operationMetadata): PaymentAttempt
    {
        if (! in_array($paymentMethod, ['online', 'bank_transfer'], true)) {
            throw new RuntimeException('طريقة الدفع غير صالحة.');
        }
        if ($amount <= 0) {
            throw new RuntimeException('قيمة عملية الاشتراك يجب أن تكون أكبر من صفر.');
        }

        $connection = $this->connection();
        $isBankTransfer = $paymentMethod === 'bank_transfer';

        return DB::connection($connection)->transaction(function () use ($subscription, $type, $paymentMethod, $amount, $operationMetadata, $isBankTransfer, $connection): PaymentAttempt {
            $pending = $this->bills()->where('tenant_id', $this->tenantId())
                ->where('type', $type->value)
                ->whereIn('status', [BillStatus::PENDING->value, BillStatus::PENDING_VERIFICATION->value])
                ->where('due_at', '>', now())
                ->where('metadata->checkout_source', 'tenant_commercial')
                ->latest('id')
                ->lockForUpdate()
                ->first();
            if ($pending !== null) {
                return $this->attempts()->where('bill_id', $pending->getKey())->latest('id')->firstOrFail();
            }

            $bill = $this->bills()->create([
                'bill_number' => 'INV-'.now()->format('Ym').'-'.Str::upper(Str::random(12)),
                'tenant_id' => $this->tenantId(),
                'type' => $type,
                'amount' => $amount,
                'payment_method' => $isBankTransfer ? PaymentMethod::BANK_TRANSFER : PaymentMethod::ONLINE,
                'status' => $isBankTransfer ? BillStatus::UNPAID : BillStatus::PENDING,
                'due_at' => $isBankTransfer ? now()->addDays(7) : now()->addHour(),
                'metadata' => [
                    'checkout_source' => 'tenant_commercial',
                    'commercial_operation' => $type === BillType::RENEWAL ? 'renewal' : 'license_purchase',
                    'subscription_id' => $subscription->getKey(),
                    ...$operationMetadata,
                ],
            ]);

            return $this->attempts()->create([
                'bill_id' => $bill->getKey(),
                'checkout_token' => (string) Str::uuid(),
                'provider' => $isBankTransfer ? 'bank_transfer' : 'moyasar',
                'payment_method' => $paymentMethod,
                'status' => $isBankTransfer ? 'awaiting_receipt' : 'initiated',
                'amount' => $amount,
                'expires_at' => $bill->due_at,
            ]);
        });
    }

    private function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()->with('plan')->where('tenant_id', $this->tenantId())
            ->whereIn('status', ['active', 'trial'])->latest('id')->first();
    }

    private function subscriptions(): Builder { return (new Subscription())->setConnection($this->connection())->newQuery(); }
    private function plans(): Builder { return (new Plan())->setConnection($this->connection())->newQuery(); }
    private function bills(): Builder { return (new Bill())->setConnection($this->connection())->newQuery(); }
    private function attempts(): Builder { return (new PaymentAttempt())->setConnection($this->connection())->newQuery(); }

    private function connection(): string
    {
        $connection = (string) config('tenancy.database.central_connection');
        if ($connection === '') { throw new RuntimeException('The central database connection must be configured.'); }
        return $connection;
    }

    private function tenantId(): string
    {
        $id = tenant('id');
        if ($id === null || $id === '') { throw new TenancyNotInitializedException(); }
        return (string) $id;
    }
}
