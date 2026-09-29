<?php

namespace App\Http\Controllers\Tenants\GeneralSetting\Branch;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenants\Traits\CommonSettingController;
use App\Models\Tenants\GeneralSetting\Branch\SettingsBranch;
use Illuminate\Http\Request;

class SettingsBranchController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:branches_settings.view')->only([
            'index',
            'edit',
            'store',
            'update',
            'destroy',
        ]);
    }

    use CommonSettingController;

    protected $model = SettingsBranch::class;

    public function index()
    {
        return $this->index_trait($this->model, 'tenants.general_setting.branches.settings_branches', 'general-settings.settings-branches');
    }

    public function edit($id) // لتغيير حالة الفروع
    {
        return $this->toggleStatus_trait($this->model, $id);
    }

    public function store(Request $request)
    {
        return $this->store_trait($request, $this->model);
    }

    public function update(Request $request, $id)
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
            $branch = SettingsBranch::findOrFail($id);

            // Check if branch has users
            if ($branch->users()->count() > 0) {
                throw new \Exception('لا يمكن حذف هذا الفرع لأنه مرتبط بمستخدمين. يجب حذف المستخدمين أولاً.');
            }

            $branch->delete();
            return redirect()->back()->with('success', 'تم الحذف بنجاح.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
