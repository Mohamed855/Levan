<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\AdminMainSettingsRequest;
use App\Models\Admin\Settings\Settings;
use App\Models\Admin\Version\Version;
use App\Services\Admin\Microsoft\ConnectionTester\MicrosoftConnectionTester;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingsController extends Controller
{
    private $page = "admin.settings";

    public function __construct(private MicrosoftConnectionTester $msTester)
    {
        $this->middleware('can:system.settings')->only(['index', 'update']);
    }


    public function index()
    {
        $settings = Settings::first();

        $versions = Version::select('id', 'version')->where('is_active', 1)->get();

        return view($this->page . '.settings', compact('settings', 'versions'));
    }

    //
    public function update(AdminMainSettingsRequest $request)
    {
        $validated = $request->validated();

        $settings = Settings::first() ?? new Settings();

        if ($request->has('office_name')) {

            $settings->office_name              = $validated['office_name'];
            $settings->logo_text                = $validated['logo_text'];
            $settings->maintenance_mode         = $request->has('maintenance_mode');
            $settings->trial_days_mode          = $request->has('trial_days_mode');

            //
            $settings->office_address       = $validated['office_address'] ?? null;
            $settings->commercial_register  = $validated['commercial_register'] ?? null;
            $settings->tax_number           = $validated['tax_number'] ?? null;
            $settings->phone_number         = $validated['phone_number'] ?? null;
            $settings->email                = $validated['email'] ?? null;

            if ($request->hasFile('image')) {
                $settings->image = $request->file('image')->store('image', 'public');
            }
        }


        if ($request->has('etmam_base_url')) {
            $settings->etmam_base_url      = $validated['etmam_base_url'];
            $settings->etmam_system_code   = $validated['etmam_system_code'];
            $settings->etmam_api_token     = $validated['etmam_api_token'];
            $settings->etmam_hmac_secret   = $validated['etmam_hmac_secret'];
            $settings->etmam_enabled       = $request->has('etmam_enabled');
        }

        if ($request->has('two_factor_settings_submit')) {
            $settings->force_two_factor_auth = $request->has('force_two_factor_auth');
        }

        if ($request->has('default_tenant_version_id') || $request->has('default_landing_version_id')) {
            $settings->default_tenant_version_id = $validated['default_tenant_version_id'] ?? null;
            $settings->default_landing_version_id   = $validated['default_landing_version_id'] ?? null;
        }

        // microsoft
        if ($request->has('microsoft_client_id')) {

            $clientId    = $validated['microsoft_client_id'];
            $redirectUri = $validated['microsoft_redirect_uri'];
            $tenantId    = $validated['microsoft_tenant_id'];
            $mainEmail   = $validated['main_email'];

            $clientSecretPlain = null;

            if (!empty($validated['microsoft_client_secret'] ?? null)) {
                $clientSecretPlain = $validated['microsoft_client_secret'];
            } elseif ($settings->microsoft_client_secret) {
                $clientSecretPlain = Crypt::decryptString($settings->microsoft_client_secret);
            }

            if (!$clientSecretPlain) {
                return back()->withInput()->withFragment('#microsoft')->withErrors([
                    'microsoft_client_secret' => 'يجب إدخال Client Secret صالح لاختبار الاتصال مع Microsoft.',
                ]);
            }

            [$ok, $errorMessage] = $this->msTester->validate($clientId, $clientSecretPlain, $tenantId, $mainEmail);

            if (!$ok) {
                return back()->withInput()->withFragment('#microsoft')->withErrors(['microsoft_client_id' => $errorMessage]);
            }

            $settings->microsoft_client_id    = $clientId;
            $settings->microsoft_redirect_uri = $redirectUri;
            $settings->microsoft_tenant_id    = $tenantId;
            $settings->main_email             = $mainEmail;

            if (!empty($validated['microsoft_client_secret'] ?? null)) {
                $settings->microsoft_client_secret = Crypt::encryptString($clientSecretPlain);
            }
        }


        $settings->save();
        Cache::forget('app_settings');

        return redirect()->route('admin.settings.index')->withFragment($request->input('active_tab', null))->with('success', 'تم تحديث الإعدادات بنجاح');
    }
}
