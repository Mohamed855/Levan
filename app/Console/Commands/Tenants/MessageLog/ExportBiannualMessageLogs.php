<?php

namespace App\Console\Commands\Tenants\MessageLog;

use App\Exports\Tenants\Communication\MessageIndividualLogsExport;
use App\Models\Tenants\Communication\MessageLog\MessageIndividualLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Stancl\Tenancy\Database\Models\Tenant;

class ExportBiannualMessageLogs extends Command
{
    protected $signature = 'message-logs:export-biannual';

    protected $description = 'Export message logs to Excel every 6 months and delete from database';

    /*
    |--------------------------------------------------------------------------
    | Handle Method
    |--------------------------------------------------------------------------
    */
    public function handle()
    {
        Tenant::all()->runForEach(function ($tenant) {
            $this->info("Processing tenant: {$tenant->id}");

            // جلب كل السجلات
            $logs = MessageIndividualLog::with([
                'customer:id,name,customer_type',
                'company:id,name',
                'portfolio:id,name',
                'template:id,name',
                'sentBy:id,name',
            ])->get();

            if ($logs->isEmpty()) {
                $this->info("No message logs found for tenant: {$tenant->id}");

                return;
            }

            // إنشاء اسم الملف (كل 6 أشهر)
            $fileName = 'message_logs_'.now()->format('Y_m').'.xlsx';
            $filePath = "message-logs/{$fileName}";

            // تصدير إلى Excel
            $this->exportToExcel($logs, $filePath);

            // حذف السجلات من قاعدة البيانات
            MessageIndividualLog::truncate();

            $this->info("✓ Exported {$logs->count()} logs and deleted from DB for tenant: {$tenant->id}");
        });

        $this->info('✓ Biannual message logs export completed!');
    }

    /*
    |--------------------------------------------------------------------------
    | Export To Excel Method
    |--------------------------------------------------------------------------
    */
    private function exportToExcel($logs, $filePath)
    {
        $tempPath = storage_path('app/temp_message_export.xlsx');

        Excel::store(new MessageIndividualLogsExport($logs), 'temp_message_export.xlsx', 'local');

        Storage::disk('tenant_public')->put($filePath, file_get_contents($tempPath));

        unlink($tempPath);
    }
}
