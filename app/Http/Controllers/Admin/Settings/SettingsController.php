<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Actions\Admin\Settings\UpdatePlatformSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\AdminMainSettingsRequest;
use App\Models\Admin\Settings\Settings;

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

        return view($this->page.'.settings', compact('settings'));
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
            ],
            $request->file('image'),
        );

        return redirect()->route('admin.settings.index')->with('success', 'تم تحديث الإعدادات بنجاح');
    }
}
