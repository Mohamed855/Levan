<?php

declare(strict_types=1);

namespace App\Actions\Admin\Bills;

use App\Models\Admin\AdminUser;
use App\Models\Admin\Bill;
use Illuminate\Support\Facades\DB;

final class UpdateBillStatusAction
{
    public function execute(Bill $bill, array $data, ?AdminUser $admin = null): Bill
    {
        return DB::transaction(function () use ($bill, $data, $admin): Bill {
            $oldStatus = $bill->status;
            $newStatus = $data['status'];

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
            } elseif ($newStatus === 'pending') {
                $updates['paid_at'] = null;
                $updates['settled_amount'] = null;
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
