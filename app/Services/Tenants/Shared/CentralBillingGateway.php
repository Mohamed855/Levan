<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Enums\Admin\Bills\BillStatus;
use App\Enums\Admin\Bills\PaymentMethod;
use App\Models\Admin\Bank;
use App\Models\Admin\Bill;
use App\Models\Admin\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;

final class CentralBillingGateway
{
    public function billForCurrentTenant(int|string $id): Bill
    {
        $tenantId = (string) tenant('id');

        return Tenancy::central(fn (): Bill => Bill::query()
            ->with('tenant')
            ->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail());
    }

    public function billsQueryForCurrentTenant(): Builder
    {
        $tenantId = (string) tenant('id');

        return Bill::query()->where('tenant_id', $tenantId);
    }

    /**
     * @return Collection<int, Bank>
     */
    public function activeBanks(): Collection
    {
        return Tenancy::central(fn (): Collection => Bank::query()->where('is_active', true)->get());
    }

    public function bankIsActive(int|string $id): bool
    {
        return Tenancy::central(fn (): bool => Bank::query()->whereKey($id)->where('is_active', true)->exists());
    }

    public function platformSettings(): ?Setting
    {
        return Tenancy::central(fn (): ?Setting => Setting::query()->first());
    }

    public function submitBankTransferReceipt(int|string $billId, string $receiptPath, int $bankId): Bill
    {
        $tenantId = (string) tenant('id');

        return Tenancy::central(function () use ($billId, $receiptPath, $bankId, $tenantId): Bill {
            $bankExists = Bank::query()->whereKey($bankId)->where('is_active', true)->exists();
            abort_unless($bankExists, 422, 'الحساب البنكي المحدد غير متاح.');

            $bill = Bill::query()
                ->whereKey($billId)
                ->where('tenant_id', $tenantId)
                ->where('status', BillStatus::UNPAID)
                ->lockForUpdate()
                ->firstOrFail();

            $metadata = $bill->metadata ?? [];
            $metadata['receipt_path'] = $receiptPath;
            $metadata['bank_id'] = $bankId;
            $metadata['submitted_at'] = now()->toDateTimeString();

            $bill->update([
                'payment_method' => PaymentMethod::BANK_TRANSFER,
                'status' => BillStatus::PENDING_VERIFICATION,
                'metadata' => $metadata,
            ]);

            return $bill;
        });
    }

    public function currentSubscription(): ?array
    {
        $tenantId = (string) tenant('id');

        return Tenancy::central(function () use ($tenantId): ?array {
            $subscription = \App\Models\Admin\Subscriptions\Subscription::query()
                ->with('plan')
                ->where('tenant_id', $tenantId)
                ->latest()
                ->first();

            if (! $subscription) {
                return null;
            }

            $daysRemaining = $subscription->ends_at ? (int) now()->diffInDays($subscription->ends_at, false) : null;

            return [
                'plan_name' => $subscription->plan?->name ?? 'باقة النظام',
                'status' => $subscription->status,
                'ends_at' => $subscription->ends_at?->format('Y-m-d'),
                'days_remaining' => $daysRemaining !== null ? max(0, $daysRemaining) : null,
                'is_expired' => $daysRemaining !== null && $daysRemaining <= 0,
            ];
        });
    }
}

