<?php

namespace App\Console\Commands\Tenants\Migration;

use App\Models\Admin\Tenants\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class MigrateAllTenants extends Command
{
    protected $signature = 'tenants:migrate-all
                            {--path= : مسار ملف الـ migration}';

    protected $description = 'تنفيذ migration على كل الـ Tenants النشطين';

    /*
    |--------------------------------------------------------------------------
    | Handle
    |--------------------------------------------------------------------------
    | نقطة الدخول الرئيسية للـ Command.
    */
    public function handle(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('لا يوجد أي Tenant نشط.');

            return;
        }

        $this->info("إجمالي الـ Tenants النشطين: {$tenants->count()}");

        $success = [];
        $nothing = [];
        $failed = [];
        $noDB = [];

        foreach ($tenants as $index => $tenant) {

            $this->line("\n[{$index}] معالجة Tenant: {$tenant->id}");

            // STEP 1: التأكد من العودة للـ Central قبل أي شيء
            try {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
                DB::purge('tenant');
                DB::setDefaultConnection('mysql');
            } catch (\Throwable $e) {
            }

            // STEP 2: التحقق من وجود قاعدة البيانات
            if (! $this->checkDatabaseExists($tenant)) {
                $this->warn('  ⚠️ قاعدة البيانات غير موجودة — تم التخطي');
                $noDB[] = $tenant->id;

                continue;
            }

            // STEP 3: تهيئة الـ Tenant
            try {
                tenancy()->initialize($tenant);
            } catch (\Throwable $e) {
                $this->error('  ❌ فشل initialize(): '.$e->getMessage());
                $failed[] = $tenant->id;

                try {
                    DB::purge('tenant');
                    DB::setDefaultConnection('mysql');
                } catch (\Throwable $ignored) {
                }

                continue;
            }

            // STEP 4: تنفيذ الـ Migration
            try {
                $options = ['--force' => true];

                if ($this->option('path')) {
                    $options['--path'] = $this->option('path');
                    $options['--realpath'] = true;
                }

                Artisan::call('migrate', $options);
                $output = trim(Artisan::output());

                if (str_contains(strtolower($output), 'nothing')) {
                    $this->line('  ⏭️  Nothing to migrate');
                    $nothing[] = $tenant->id;
                } else {
                    $this->info('  ✅ تم التنفيذ بنجاح');
                    $this->line('     '.$output);
                    $success[] = $tenant->id;
                }

            } catch (\Throwable $e) {
                $this->error('  ❌ فشل: '.$e->getMessage());
                $failed[] = $tenant->id;

            } finally {
                // STEP 5: إنهاء الـ Tenant وإعادة ضبط الـ Connection
                try {
                    if (tenancy()->initialized) {
                        tenancy()->end();
                    }
                    DB::purge('tenant');
                    DB::setDefaultConnection('mysql');
                } catch (\Throwable $e) {
                }
            }
        }

        $this->newLine();
        $this->info('✅ نُفذ بنجاح : '.count($success).' → '.implode(', ', $success ?: ['لا شيء']));
        $this->line('⏭️  Nothing    : '.count($nothing).' → '.implode(', ', $nothing ?: ['لا شيء']));
        $this->warn('⚠️  بدون DB    : '.count($noDB).' → '.implode(', ', $noDB ?: ['لا شيء']));
        $this->error('❌ فشل        : '.count($failed).' → '.implode(', ', $failed ?: ['لا شيء']));
    }

    /*
    |--------------------------------------------------------------------------
    | Check Database Exists
    |--------------------------------------------------------------------------
    | تتحقق من وجود قاعدة بيانات الـ Tenant في الـ MySQL عبر Central connection.
    */
    private function checkDatabaseExists(Tenant $tenant): bool
    {
        try {
            if (empty($tenant->db_database)) {
                return false;
            }

            $result = DB::connection('mysql')->select(
                'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
                [$tenant->db_database]
            );

            return ! empty($result);

        } catch (\Throwable $e) {
            return false;
        }
    }
}
