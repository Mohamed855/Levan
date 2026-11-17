<?php

namespace App\Http\Controllers\Tenants\Settings;

use App\Actions\Tenants\Settings\UpdateTenantSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Settings\MainSettingsRequest;
use App\Models\Tenants\Settings\Settings;
use App\Services\Tenants\Settings\EstablishmentSigningAssets;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;

class SettingsController extends Controller
{
    public function __construct(private readonly UpdateTenantSettingsAction $updateSettings)
    {
        $this->middleware('can:system.settings')->only(['index', 'update', 'signingAsset']);
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
            $imageFile instanceof UploadedFile ? $imageFile : null,
            $request->file('establishment_signature'),
            $request->file('establishment_stamp')
        );

        return redirect(tenant_route_name('settings.index'))->with('success', 'تم تحديث الإعدادات بنجاح');
    }

    public function signingAsset(string $asset, EstablishmentSigningAssets $signingAssets): Response
    {
        abort_unless(in_array($asset, ['signature', 'stamp'], true), 404);
        $contents = $signingAssets->contents(Settings::query()->first(), $asset);
        abort_if($contents === null, 404);

        return response($contents, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
