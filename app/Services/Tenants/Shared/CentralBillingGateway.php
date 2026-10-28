<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Models\Admin\Bank\Bank;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Settings\Settings;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;

final class CentralBillingGateway
{
    public function billForCurrentTenant(int|string $id): Bill
    {
        $tenantId = (string) tenant('id');

        return Tenancy::central(fn (): Bill => Bill::query()
            ->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail());
    }

    /** @return array<string, int> */
    public function billStatisticsForCurrentTenant(): array
    {
        $tenantId = (string) tenant('id');

        return Tenancy::central(function () use ($tenantId): array {
            $stats = Bill::toBase()
                ->where('tenant_id', $tenantId)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            $paid = (int) ($stats->get(BillStatus::PAID->value, 0));
            $unpaid = (int) ($stats->get(BillStatus::UNPAID->value, 0));
            $pending = (int) ($stats->get(BillStatus::PENDING->value, 0) + $stats->get(BillStatus::PENDING_VERIFICATION->value, 0));
            $rejected = (int) ($stats->get(BillStatus::REJECTED->value, 0) + $stats->get(BillStatus::CANCELLED->value, 0));
            $total = (int) $stats->sum();

            return [
                'total' => $total,
                'paid' => $paid,
                'pending' => $pending,
                'unpaid' => $unpaid,
                'rejected' => $rejected,
            ];
        });
    }

    /** @return Collection<int, Bank> */
    public function activeBanks(): Collection
    {
        return Tenancy::central(fn (): Collection => Bank::query()->where('is_active', true)->get());
    }

    public function bankIsActive(int|string $id): bool
    {
        return Tenancy::central(fn (): bool => Bank::query()->whereKey($id)->where('is_active', true)->exists());
    }

    public function platformSettings(): Settings
    {
        return Tenancy::central(fn (): Settings => Settings::query()->firstOrFail());
    }

    /** @return Collection<int, Plan> */
    public function activePlans(): Collection
    {
        return Tenancy::central(fn (): Collection => Plan::query()->where('is_active', true)->get());
    }

    public function plan(int|string $id): Plan
    {
        return Tenancy::central(fn (): Plan => Plan::query()->whereKey($id)->where('is_active', true)->firstOrFail());
    }

    public function currentTenantPlan(): Plan
    {
        $tenantId = (string) tenant('id');

        return Tenancy::central(function () use ($tenantId): Plan {
            $tenant = Tenant::query()->with('lastSubscription.plan')->findOrFail($tenantId);
            $plan = $tenant->lastSubscription?->plan;
            abort_unless($plan !== null && $plan->is_active, 404, 'لا يوجد اشتراك نشط.');

            return $plan;
        });
    }

    public function currentTenantHasSubscription(): bool
    {
        $tenantId = (string) tenant('id');

        return Tenancy::central(fn (): bool => Tenant::query()
            ->whereKey($tenantId)
            ->whereHas('subscriptions')
            ->exists());
    }

    public function submitBankTransferReceipt(int|string $billId, string $receiptPath, int $bankId): Bill
    {
        $tenantId = (string) tenant('id');

        return Tenancy::central(fn (): Bill => DB::connection('mysql')->transaction(function () use ($billId, $receiptPath, $bankId, $tenantId): Bill {
            $bankExists = Bank::query()->whereKey($bankId)->where('is_active', true)->exists();
            abort_unless($bankExists, 422, 'الحساب البنكي المحدد غير متاح.');

            $bill = Bill::query()
                ->whereKey($billId)
                ->where('tenant_id', $tenantId)
                ->where('status', BillStatus::UNPAID)
                ->lockForUpdate()
                ->firstOrFail();

            $bill->update([
                'invoice_url' => $receiptPath,
                'bank_id' => $bankId,
                'payment_method' => PaymentMethod::BANK_TRANSFER,
                'status' => BillStatus::PENDING_VERIFICATION,
            ]);

            return $bill;
        }));
    }
}
