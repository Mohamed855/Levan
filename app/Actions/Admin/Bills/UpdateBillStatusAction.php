<?php

declare(strict_types=1);

namespace App\Actions\Admin\Bills;

use App\Models\Admin\AdminUser;
use App\Models\Admin\Bill;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class UpdateBillStatusAction
{
    public function execute(Bill $bill, array $data, ?AdminUser $admin = null): Bill
    {
        return DB::transaction(function () use ($bill, $data, $admin): Bill {
            $bill = Bill::query()->lockForUpdate()->findOrFail($bill->id);
            $oldStatus = $bill->status->value;
            $newStatus = $data['status'];

            if ($newStatus === 'paid' && (($bill->metadata['checkout_source'] ?? null) !== null)) {
                throw new RuntimeException('يجب تأكيد دفع فاتورة checkout عبر مسار الدفع أو اعتماد التحويل البنكي.');
            }

            $updates = [
                'status' => $newStatus,
            ];

            if ($newStatus === 'paid') {
                $updates['paid_at'] = $bill->paid_at ?? now();
                $updates['settled_amount'] = ! empty($data['settled_amount'])
                    ? (float) $data['settled_amount']
                    : ($bill->settled_amount ?? $bill->amount);

                if (! empty($data['payment_method'])) {
                    $updates['payment_method'] = $data['payment_method'];
                }
            } elseif ($newStatus === 'unpaid') {
                $updates['paid_at'] = null;
                $updates['settled_amount'] = null;

                if ($oldStatus === 'cancelled') {
                    $updates['due_at'] = now()->addDays(7);
                }
            }

            // Update metadata with audit history
            $metadata = $bill->metadata ?? [];
            $history = $metadata['history'] ?? [];
            $history[] = [
                'from_status' => $oldStatus,
                'to_status'   => $newStatus,
                'notes'       => $data['notes'] ?? null,
                'admin_id'    => $admin?->id,
                'admin_name'  => $admin?->name,
                'changed_at'  => now()->toIso8601String(),
            ];
            $metadata['history'] = $history;
            $updates['metadata'] = $metadata;

            $bill->update($updates);

            return $bill->refresh();
        });
    }
}
