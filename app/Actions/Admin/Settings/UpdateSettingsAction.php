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
            $etmamKeys = [
                'etmam_base_url', 'etmam_system_code', 'etmam_api_token',
                'etmam_hmac_secret', 'etmam_enabled',
            ];
            $microsoftKeys = [
                'microsoft_client_id', 'microsoft_redirect_uri',
                'microsoft_tenant_id', 'main_email',
            ];
            $securityKeys = [
                'force_two_factor_auth',
            ];

            $general = array_intersect_key($data, array_flip($generalKeys));
            if (!empty($general)) {
                $currentGeneral = Setting::get('general', []);
                Setting::set('general', array_merge($currentGeneral, $general));
            }

            $etmam = array_intersect_key($data, array_flip($etmamKeys));
            if (!empty($etmam)) {
                $currentEtmam = Setting::get('etmam', []);
                Setting::set('etmam', array_merge($currentEtmam, $etmam));
            }

            $microsoft = array_intersect_key($data, array_flip($microsoftKeys));
            if (!empty($microsoft)) {
                if (!empty($data['microsoft_client_secret'] ?? null)) {
                    $microsoft['microsoft_client_secret'] = encrypt($data['microsoft_client_secret']);
                }
                $currentMs = Setting::get('microsoft', []);
                Setting::set('microsoft', array_merge($currentMs, $microsoft));
            }

            $security = array_intersect_key($data, array_flip($securityKeys));
            if (!empty($security)) {
                $currentSecurity = Setting::get('security', []);
                Setting::set('security', array_merge($currentSecurity, $security));
            }
        });
    }
}
