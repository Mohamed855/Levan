<?php

namespace App\Console\Commands\Tenants\ActivityLog;

use Stancl\Tenancy\Database\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DeleteOldActivityLogs extends Command
{
    protected $signature = 'activity-logs:delete-old';
    protected $description = 'Delete activity log files older than 6 months';

    public function handle()
    {
        Tenant::all()->runForEach(function ($tenant) {
            $disk = Storage::disk('tenant_public');
            $files = $disk->files('activity-logs');

            $deletedCount = 0;

            foreach ($files as $file) {
                $lastModified = $disk->lastModified($file);
                $fileDate = Carbon::createFromTimestamp($lastModified);
                $sixMonthsAgo = Carbon::now()->subMonths(6);

                // إذا كان الملف أقدم من 6 أشهر
                if ($fileDate->lt($sixMonthsAgo)) {
                    $disk->delete($file);
                    $this->info("✓ Deleted: {$file} (Date: {$fileDate->format('Y-m-d')}) for tenant: {$tenant->id}");
                    $deletedCount++;
                } else {
                    $this->line("⊗ Kept: {$file} (Date: {$fileDate->format('Y-m-d')}) - Not old enough");
                }
            }

            if ($deletedCount > 0) {
                $this->info("Total deleted for tenant {$tenant->id}: {$deletedCount} file(s)");
            } else {
                $this->info("No old files to delete for tenant: {$tenant->id}");
            }
        });

        $this->info('✓ Old files cleanup completed!');
    }
}
