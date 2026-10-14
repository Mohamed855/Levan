<?php

namespace App\Http\Controllers\Tenants\GeneralSetting\Country;

use App\Actions\Tenants\GeneralSetting\GeneralSettingAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenants\Traits\CommonSettingController;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Http\Requests\Tenants\GeneralSetting\SaveGeneralSettingRequest;
use App\Models\Tenants\GeneralSetting\Country\SettingsCountry;

class SettingsCountryController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:countries.view')->only([
            'index',
            'edit',
            'store',
            'update',
            'destroy',
        ]);
    }

    use CommonSettingController;

    protected $model = SettingsCountry::class;

    public function index()
    {
        return $this->index_trait($this->model, 'tenants.general_setting.countries.settings_country', 'general-settings.settings-country');
    }

    public function create()
    {
        return $this->create_trait('general-settings.settings-country');
    }

    public function show($id)
    {
        return $this->show_trait($this->model, $id);
    }

    public function edit(TenantAuthenticatedRequest $request, $id) // لتغيير حالة الاعدادات
    {
        return $this->toggleStatus_trait($this->model, $id);
    }

    public function store(SaveGeneralSettingRequest $request)
    {
        return $this->store_trait($request, $this->model);
    }

    public function update(SaveGeneralSettingRequest $request, $id)
    {
        return $this->update_trait($request, $this->model, $id);
    }

    public function destroy(TenantAuthenticatedRequest $request, $id)
    {
        $country = SettingsCountry::findOrFail($id);
        $this->authorize('delete', $country);

        try {
            // Check if country has customers
            if ($country->customers()->count() > 0) {
                throw new \Exception('لا يمكن حذف هذه الدولة لأنها مرتبطة بعملاء. يجب حذف العملاء أولاً.');
            }

            app(GeneralSettingAction::class)->delete($country);

            return redirect()->back()->with('success', 'تم الحذف بنجاح.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
