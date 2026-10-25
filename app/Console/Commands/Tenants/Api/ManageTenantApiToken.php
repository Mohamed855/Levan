<?php

namespace App\Console\Commands\Tenants\Api;

use App\Models\Admin\Tenants\Tenant;
use Illuminate\Console\Command;

class ManageTenantApiToken extends Command
{
    protected $signature = 'tenants:api-token
                            {tenant : معرف التينانت (id)}
                            {--regenerate : توليد توكن جديد (مطلوب دائماً لو كان يوجد توكن مسبقاً، لأنه مُخزّن مُشفّراً ولا يمكن استرجاعه)}';

    protected $description = 'توليد توكن API جديد لتينانت معيّن (يُستخدم في هيدر Authorization: Bearer للـ API الخارجي)';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('tenant'));

        if (! $tenant) {
            $this->error('لم يتم العثور على التينانت.');

            return self::FAILURE;
        }

        if (! empty($tenant->api_token) && ! $this->option('regenerate')) {
            $this->warn('يوجد توكن مُنشأ مسبقاً لهذا التينانت، لكن لا يمكن عرضه لأنه مُخزّن بصورة مُشفّرة (hash).');
            $this->line('استخدم --regenerate لتوليد توكن جديد بدلاً منه.');

            return self::FAILURE;
        }

        $plainTextToken = $tenant->regenerateApiToken();

        $this->info('تم توليد توكن جديد للتينانت "'.$tenant->company_name.'":');
        $this->line($plainTextToken);
        $this->warn('احفظ هذا التوكن الآن، لن يظهر مرة أخرى بعد هذه اللحظة.');

        return self::SUCCESS;
    }
}
