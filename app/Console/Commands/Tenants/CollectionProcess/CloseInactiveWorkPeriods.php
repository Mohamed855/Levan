<?php

namespace App\Console\Commands\Tenants\CollectionProcess;

use App\Models\Tenants\FinancialCollection\CollectionProcess\WorkPeriod;
use App\Services\Tenants\FinancialCollection\CollectionProcess\CollectionSessionService;
use Illuminate\Console\Command;
use Stancl\Tenancy\Database\Models\Tenant;

class CloseInactiveWorkPeriods extends Command
{
    protected $signature    = 'collection:close-inactive-periods';
    protected $description  = 'إغلاق فترات العمل الغير نشطة بعد 10 دقائق من آخر نشاط';

    public function handle(CollectionSessionService $collectionSessionService)
    {
        $timeoutMinutes = 10;

        Tenant::all()->runForEach(function ($tenant) use ($collectionSessionService, $timeoutMinutes) {
            $closedCount     = 0;
            $inactivePeriods = WorkPeriod::whereNull('exit_time')->where('updated_at', '<', now()->subMinutes($timeoutMinutes))->get();

            foreach ($inactivePeriods as $period) {
                $lastActivityTime = $period->updated_at;

                if ($lastActivityTime->diffInMinutes(now()) >= $timeoutMinutes) {
                    $collectionSessionService->closeWorkPeriod($period, 'timeout', $lastActivityTime);

                    $closedCount++;
                }
            }

            $this->info("[{$tenant->id}] تم إغلاق {$closedCount} فترة عمل غير نشطة");
        });

        return 0;
    }
}
