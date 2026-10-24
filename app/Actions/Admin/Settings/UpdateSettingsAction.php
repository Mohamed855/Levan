<?php

declare(strict_types=1);

namespace App\Actions\Admin\Settings;

use App\Models\Admin\Setting;
use Illuminate\Support\Facades\DB;

final class UpdateSettingsAction
{
    public function execute(array $data): void
    {
        DB::transaction(function () use ($data): void {
            // Group by settings categories
            $generalKeys = [
                'office_name', 'logo_text', 'office_address', 'commercial_register',
                'tax_number', 'phone_number', 'email', 'maintenance_mode',
                'trial_days_mode', 'trial_days_count', 'facebook_url', 'x_url',
                'linkedin_url', 'google_maps_embed_url',
            ];
            $etmamKeys = ['etmam_base_url', 'etmam_system_code', 'etmam_enabled'];
            $microsoftKeys = [
                'microsoft_client_id', 'microsoft_redirect_uri',
                'microsoft_tenant_id', 'main_email',
            ];
            $securityKeys = [
                'force_two_factor_auth',
            ];

            $general = array_intersect_key($data, array_flip($generalKeys));
            if (!empty($general)) {
                $this->mergeGroup('general', $general);
            }

            $etmam = array_intersect_key($data, array_flip($etmamKeys));
            foreach (['etmam_api_token', 'etmam_hmac_secret'] as $secretKey) {
                if (filled($data[$secretKey] ?? null)) {
                    $etmam[$secretKey] = encrypt($data[$secretKey]);
                }
            }
            if (!empty($etmam)) {
                $this->mergeGroup('etmam', $etmam);
            }

            $microsoft = array_intersect_key($data, array_flip($microsoftKeys));
            if (!empty($microsoft)) {
                if (!empty($data['microsoft_client_secret'] ?? null)) {
                    $microsoft['microsoft_client_secret'] = encrypt($data['microsoft_client_secret']);
                }
                $this->mergeGroup('microsoft', $microsoft);
            }

            $security = array_intersect_key($data, array_flip($securityKeys));
            if (!empty($security)) {
                $this->mergeGroup('security', $security);
            }
        });
    }

    /** @param array<string, mixed> $values */
    private function mergeGroup(string $key, array $values): void
    {
        $setting = Setting::query()->lockForUpdate()->firstOrNew(['key' => $key]);
        $setting->value = array_merge($setting->value ?? [], $values);
        $setting->save();
    }
}
