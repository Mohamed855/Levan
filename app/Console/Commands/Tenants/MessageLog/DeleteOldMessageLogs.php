<?php

namespace App\Console\Commands\Tenants\MessageLog;
use Stancl\Tenancy\Database\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DeleteOldMessageLogs extends Command
{
    protected $signature = 'message-logs:delete-old';
    protected $description = 'Delete message log files older than 12 months';

    /*
    |--------------------------------------------------------------------------
    | Handle Method
    |--------------------------------------------------------------------------
    */
    public function handle()
    {
        Tenant::all()->runForEach(function ($tenant) {
            $disk = Storage::disk('tenant_public');
            $files = $disk->files('message-logs');

            $deletedCount = 0;

            foreach ($files as $file) {
                $lastModified = $disk->lastModified($file);
                $fileDate = Carbon::createFromTimestamp($lastModified);
                $twelveMonthsAgo = Carbon::now()->subMonths(12);

                // إذا كان الملف أقدم من 12 شهر
                if ($fileDate->lt($twelveMonthsAgo)) {
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

        $this->info('✓ Old message log files cleanup completed!');
    }
}