<?php

namespace App\Http\Controllers\Tenants\GeneralSetting\DebtType;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenants\Traits\CommonSettingController;
use App\Http\Requests\Tenants\GeneralSetting\SaveGeneralSettingRequest;
use App\Models\Tenants\GeneralSetting\DebtType\SettingsDebtType;

class SettingsDebtTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:debt_types.view');
    }

    use CommonSettingController;

    protected $model = SettingsDebtType::class;

    public function index()
    {
        return $this->index_trait($this->model, 'tenants.general_setting.debt_types.settings_debt_types', 'general-settings.settings-debt-types');
    }

    public function edit($id)
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

    public function destroy($id)
    {
        try {
            $debtType = SettingsDebtType::findOrFail($id);

            // التحقق من وجود ارتباطات (في حالة وجود عقود أو معاملات مرتبطة)
            // if ($debtType->contracts()->count() > 0) {
            //     throw new \Exception('لا يمكن حذف نوع المديونية هذا لأنه مرتبط بعقود. يجب حذف العقود أولاً.');
            // }

            $debtType->delete();
            return redirect()->back()->with('success', 'تم الحذف بنجاح.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
