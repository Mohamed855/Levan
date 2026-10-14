<?php

namespace App\Http\Controllers\Tenants\Settings;

use App\Actions\Tenants\Settings\UpdateTenantSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Settings\MainSettingsRequest;
use App\Models\Tenants\Settings\Settings;
use Illuminate\Http\UploadedFile;

class SettingsController extends Controller
{
    public function __construct(private readonly UpdateTenantSettingsAction $updateSettings)
    {
        $this->middleware('can:system.settings')->only(['index', 'update']);
    }

    /*
    |--------------------------------------------------------------------------
    | Display the settings form.
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $settings = Settings::firstOrNew();

        return view('tenants.settings.settings', compact('settings'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update the settings.
    |--------------------------------------------------------------------------
    */
    public function update(MainSettingsRequest $request)
    {
        $validated = $request->validated();

        $activeTab = (string) $request->input('active_tab', '#generalSettings');
        session(['active_tab' => $activeTab]);

        $this->updateSettings->execute(
            $activeTab,
            $validated,
            [
                'maintenance_mode' => $request->boolean('maintenance_mode'),
                'breadcrumb_visibility' => $request->boolean('breadcrumb_visibility'),
                'sms_enabled' => $request->boolean('sms_enabled'),
                'sms_auto_reminders_enabled' => $request->boolean('sms_auto_reminders_enabled'),
                'force_two_factor_auth' => $request->boolean('force_two_factor_auth'),
            ],
            array_filter($request->allFiles(), fn ($file) => $file instanceof UploadedFile),
        );

        return redirect(tenant_route_name('settings.index'))->withFragment($request->input('active_tab', null))->with('success', 'تم تحديث الإعدادات بنجاح');
    }

    /*
    |--------------------------------------------------------------------------
    | عرض مركز الإعدادات التفصيلية
    |--------------------------------------------------------------------------
    */
    public function center()
    {
        return view('tenants.settings.center');
    }
}
