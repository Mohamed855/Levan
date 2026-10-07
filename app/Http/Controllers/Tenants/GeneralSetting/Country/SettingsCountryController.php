<?php

namespace App\Http\Controllers\Tenants\GeneralSetting\Country;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenants\Traits\CommonSettingController;
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

    public function edit($id) // لتغيير حالة الاعدادات
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

    // public function destroy($id)
    // {
    //     return $this->destroy_trait($this->model, $id);
    // }

    public function destroy($id)
    {
        try {
            $country = SettingsCountry::findOrFail($id);

            // Check if country has customers
            if ($country->customers()->count() > 0) {
                throw new \Exception('لا يمكن حذف هذه الدولة لأنها مرتبطة بعملاء. يجب حذف العملاء أولاً.');
            }

            $country->delete();
            return redirect()->back()->with('success', 'تم الحذف بنجاح.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
