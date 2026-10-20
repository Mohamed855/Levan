<?php

namespace App\Console\Commands;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Models\Admin\Bills\Bill;
use Illuminate\Console\Command;

class DeleteAbandonedCheckoutBills extends Command
{
    protected $signature = 'checkout:delete-abandoned-bills';
    protected $description = 'Permanently delete abandoned public checkout bills';

    public function handle(): int
    {
        $deleted = Bill::query()
            ->where('type', BillType::NEW_SUBSCRIPTION)
            ->whereNull('tenant_id')
            ->whereNull('subscription_id')
            ->whereIn('status', [BillStatus::PENDING, BillStatus::UNPAID])
            ->whereJsonContains('metadata->checkout_source', 'site_checkout')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->forceDelete();

        $this->info("Deleted {$deleted} abandoned checkout bill(s).");

        return self::SUCCESS;
    }
}
