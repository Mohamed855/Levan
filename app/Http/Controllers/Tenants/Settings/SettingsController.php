<?php

namespace App\Http\Controllers\Tenants\Settings;

use App\Actions\Tenants\Settings\UpdateTenantSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Settings\MainSettingsRequest;
use App\Models\Tenants\Settings\Settings;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
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
    public function index(CentralCityOptionsGateway $cityOptionsGateway)
    {
        $settings = Settings::firstOrNew();
        $allRegions = $cityOptionsGateway->allRegionOptionsByName();

        return view('tenant.settings.settings', compact('settings', 'allRegions'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update the settings.
    |--------------------------------------------------------------------------
    */
    public function update(MainSettingsRequest $request)
    {
        $validated = $request->validated();

        $imageFile = $request->file('image');
        $this->updateSettings->execute(
            $validated,
            $imageFile instanceof UploadedFile ? $imageFile : null
        );

        return redirect(tenant_route_name('settings.index'))->with('success', 'تم تحديث الإعدادات بنجاح');
    }
}
