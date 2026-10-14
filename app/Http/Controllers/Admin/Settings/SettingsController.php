<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Actions\Admin\Settings\UpdatePlatformSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\AdminMainSettingsRequest;
use App\Models\Admin\Settings\Settings;
use App\Models\Admin\Version\Version;

class SettingsController extends Controller
{
    private $page = 'admin.settings';

    public function __construct(private readonly UpdatePlatformSettingsAction $updateSettings)
    {
        $this->middleware('can:system.settings')->only(['index', 'update']);
    }

    public function index()
    {
        $settings = Settings::first();

        $versions = Version::select('id', 'version')->where('is_active', 1)->get();

        return view($this->page.'.settings', compact('settings', 'versions'));
    }

    //
    public function update(AdminMainSettingsRequest $request)
    {
        $validated = $request->validated();

        $this->updateSettings->execute(
            $validated,
            [
                'maintenance_mode' => $request->boolean('maintenance_mode'),
                'trial_days_mode' => $request->boolean('trial_days_mode'),
                'etmam_enabled' => $request->boolean('etmam_enabled'),
                'two_factor_settings_submit' => $request->has('two_factor_settings_submit'),
                'force_two_factor_auth' => $request->boolean('force_two_factor_auth'),
            ],
            $request->file('image'),
        );

        return redirect()->route('admin.settings.index')->withFragment($request->input('active_tab', null))->with('success', 'تم تحديث الإعدادات بنجاح');
    }
}
