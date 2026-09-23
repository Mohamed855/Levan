<?php

namespace App\Console\Commands\Tenants\ActivityLog;

use Stancl\Tenancy\Database\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportMonthlyActivityLogs extends Command
{
    protected $signature = 'activity-logs:export-monthly';
    protected $description = 'Export activity logs to Excel and delete from database monthly';

    public function handle()
    {
        Tenant::all()->runForEach(function ($tenant) {
            $this->info("Processing tenant: {$tenant->id}");
            
            // جلب كل السجلات
            $activities = Activity::with('causer', 'subject')->get();
            
            if ($activities->isEmpty()) {
                $this->info("No activities found for tenant: {$tenant->id}");
                return;
            }

            // إنشاء اسم الملف
            $fileName = 'activity_logs_' . now()->format('Y_m') . '.xlsx';
            $filePath = "activity-logs/{$fileName}";

            // تصدير إلى Excel
            $this->exportToExcel($activities, $filePath);

            // حذف السجلات من قاعدة البيانات
            Activity::truncate();

            $this->info("Exported and deleted for tenant: {$tenant->id}");
        });

        $this->info('Monthly export completed successfully!');
    }

    private function exportToExcel($activities, $filePath)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // العناوين
        $headers = ['#', 'نوع النشاط', 'الوصف', 'المستخدم', 'الموضوع', 'التاريخ'];
        $sheet->fromArray($headers, null, 'A1');

        // البيانات
        $row = 2;
        foreach ($activities as $index => $activity) {
            $sheet->fromArray([
                $index + 1,
                $activity->log_name,
                $activity->description,
                $activity->causer ? $activity->causer->name : 'النظام',
                $activity->subject ? class_basename($activity->subject_type) . ' #' . $activity->subject_id : 'محذوف',
                $activity->created_at->format('Y-m-d H:i:s')
            ], null, "A{$row}");
            $row++;
        }

        // حفظ الملف
        $writer = new Xlsx($spreadsheet);
        $tempPath = storage_path('app/temp_export.xlsx');
        $writer->save($tempPath);

        Storage::disk('tenant_public')->put($filePath, file_get_contents($tempPath));
        unlink($tempPath);
    }
}