<?php

namespace App\Console\Commands\Tenants\Reports;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CleanReportsFiles extends Command
{
    /*
    |--------------------------------------------------------------------------
    | Signature
    |--------------------------------------------------------------------------
    */
    protected $signature = 'reports:clean';

    /*
    |--------------------------------------------------------------------------
    | Description
    |--------------------------------------------------------------------------
    */
    protected $description = 'حذف جميع ملفات التقارير في مجلد exports';

    /*
    |--------------------------------------------------------------------------
    | Handle
    |--------------------------------------------------------------------------
    */
    public function handle()
    {
        $this->info('بدء عملية تنظيف ملفات التقارير...');

        $reportDirectory = storage_path('app/public/exports');

        if (!File::exists($reportDirectory)) {
            $this->warn("المجلد غير موجود: {$reportDirectory}");
            return 0;
        }

        $deletedCount = 0;
        $totalSize = 0;

        $files = File::allFiles($reportDirectory);

        $this->info("تم العثور على " . count($files) . " ملف للمعالجة");

        foreach ($files as $file) {
            $fileSize = $file->getSize();
            $totalSize += $fileSize;

            if (File::delete($file->getPathname())) {
                $deletedCount++;
                $this->line("تم حذف الملف: {$file->getFilename()} (" . $this->formatBytes($fileSize) . ")");
            }
        }

        $this->info("اكتملت عملية التنظيف. تم حذف {$deletedCount} ملف، بإجمالي حجم: " . $this->formatBytes($totalSize));

        return 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Format Bytes
    |--------------------------------------------------------------------------
    */
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision) . ' ' . $units[$i];
    }
}
