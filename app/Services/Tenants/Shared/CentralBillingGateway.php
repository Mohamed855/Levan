<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Contracts\Tenants\BankTransferReceiptGateway;
use App\Enums\Admin\Bills\BillStatus;
use App\Enums\Admin\Bills\PaymentMethod;
use App\Models\Admin\Bank;
use App\Models\Admin\Bill;
use App\Models\Admin\PaymentAttempt;
use App\Models\Admin\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Stancl\Tenancy\Exceptions\TenancyNotInitializedException;

final class CentralBillingGateway implements BankTransferReceiptGateway
{
    public function billForCurrentTenant(int|string $id): Bill
    {
        return $this->billsQueryForCurrentTenant()
            ->with('tenant')
            ->whereKey($id)
            ->firstOrFail();
    }

    public function receiptPathForCurrentTenant(Bill $bill): string
    {
        $tenantId = $this->currentTenantId();
        if ((string) $bill->tenant_id !== $tenantId) {
            abort(404);
        }

        $path = $bill->metadata['receipt_path'] ?? null;
        $expectedPrefix = 'receipts/'.$tenantId.'/';
        if (! is_string($path) || ! str_starts_with($path, $expectedPrefix)) {
            abort(404);
        }

        return $path;
    }

    public function billsQueryForCurrentTenant(): Builder
    {
        return $this->centralBillQuery()->where('tenant_id', $this->currentTenantId());
    }

    /**
     * @return Collection<int, Bank>
     */
    public function activeBanks(): Collection
    {
        return $this->centralBankQuery()->where('is_active', true)->get();
    }

    public function bankIsActive(int|string $id): bool
    {
        return $this->centralBankQuery()->whereKey($id)->where('is_active', true)->exists();
    }

    /** @return array<string, mixed> */
    public function platformGeneralSettings(): array
    {
        return $this->centralSettingQuery()
            ->where('key', 'general')
            ->value('value') ?? [];
    }

    public function submitBankTransferReceipt(int|string $billId, string $receiptPath, int $bankId): Bill
    {
        $tenantId = $this->currentTenantId();
        $connection = $this->centralConnectionName();

        return DB::connection($connection)->transaction(function () use ($billId, $receiptPath, $bankId, $tenantId): Bill {
            $bankExists = $this->centralBankQuery()->whereKey($bankId)->where('is_active', true)->exists();
            abort_unless($bankExists, 422, 'الحساب البنكي المحدد غير متاح.');

            $bill = $this->centralBillQuery()
                ->whereKey($billId)
                ->where('tenant_id', $tenantId)
                ->where('status', BillStatus::UNPAID->value)
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

            (new PaymentAttempt())->setConnection($connection)->newQuery()
                ->where('bill_id', $bill->getKey())
                ->where('provider', 'bank_transfer')
                ->whereIn('status', ['awaiting_receipt', 'pending_verification'])
                ->update(['status' => 'pending_verification']);

            return $bill;
        });
    }

    public function currentSubscription(): ?array
    {
        $tenantId = $this->currentTenantId();

        $subscription = (new \App\Models\Admin\Subscriptions\Subscription())
            ->setConnection($this->centralConnectionName())
            ->newQuery()
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
    }

    public function statsForCurrentTenant(): array
    {
        $base = $this->billsQueryForCurrentTenant();

        return [
            'total' => (clone $base)->count(),
            'paid' => (clone $base)->where('status', BillStatus::PAID->value)->count(),
            'pending_verification' => (clone $base)->where('status', BillStatus::PENDING_VERIFICATION->value)->count(),
            'unpaid' => (clone $base)->where('status', BillStatus::UNPAID->value)->count(),
            'overdue' => (clone $base)->whereIn('status', [BillStatus::OVERDUE->value, BillStatus::REJECTED->value, BillStatus::CANCELLED->value])->count(),
        ];
    }

    private function centralBillQuery(): Builder
    {
        return (new Bill())->setConnection($this->centralConnectionName())->newQuery();
    }

    private function centralBankQuery(): Builder
    {
        return (new Bank())->setConnection($this->centralConnectionName())->newQuery();
    }

    private function centralSettingQuery(): Builder
    {
        return (new Setting())->setConnection($this->centralConnectionName())->newQuery();
    }

    private function centralConnectionName(): string
    {
        $connection = (string) config('tenancy.database.central_connection');

        if ($connection === '') {
            throw new RuntimeException('The central database connection must be configured.');
        }

        return $connection;
    }

    private function currentTenantId(): string
    {
        $tenantId = tenant('id');

        if ($tenantId === null || $tenantId === '') {
            throw new TenancyNotInitializedException();
        }

        return (string) $tenantId;
    }
}

