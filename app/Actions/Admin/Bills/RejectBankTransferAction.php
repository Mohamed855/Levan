<?php

declare(strict_types=1);

namespace App\Actions\Admin\Bills;

use App\Enums\Admin\Bills\BillStatus;
use App\Models\Admin\AdminUser;
use App\Models\Admin\Bill;
use App\Models\Admin\PaymentAttempt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class RejectBankTransferAction
{
    public function execute(Bill $bill, string $reason, AdminUser $admin): Bill
    {
        return DB::transaction(function () use ($bill, $reason, $admin): Bill {
            $lockedBill = Bill::query()->lockForUpdate()->findOrFail($bill->getKey());
            $attempt = PaymentAttempt::query()->where('bill_id', $lockedBill->getKey())
                ->where('provider', 'bank_transfer')->lockForUpdate()->latest('id')->first();
            if ($lockedBill->status !== BillStatus::PENDING_VERIFICATION || ! $attempt || $attempt->status !== 'pending_verification') {
                throw new RuntimeException('لا توجد حوالة بنكية معلقة قابلة للرفض.');
            }

            $metadata = $lockedBill->metadata ?? [];
            $targetStatus = $lockedBill->tenant_id ? BillStatus::UNPAID : BillStatus::CANCELLED;
            $metadata['history'][] = [
                'from_status' => BillStatus::PENDING_VERIFICATION->value,
                'to_status' => $targetStatus->value,
                'notes' => $reason,
                'admin_id' => $admin->getKey(),
                'admin_name' => $admin->name,
                'changed_at' => now()->toIso8601String(),
            ];
            $lockedBill->update(['status' => $targetStatus, 'metadata' => $metadata]);
            $attempt->update(['status' => 'rejected', 'failure_reason' => $reason]);

            return $lockedBill->refresh();
        });
    }
}
