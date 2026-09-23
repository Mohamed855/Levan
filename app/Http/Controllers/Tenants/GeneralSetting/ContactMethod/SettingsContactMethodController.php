<?php

namespace App\Http\Controllers\Tenants\GeneralSetting\ContactMethod;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenants\Traits\CommonSettingController;
use App\Models\Tenants\GeneralSetting\ContactMethod\SettingsContactMethod;
use Illuminate\Http\Request;

class SettingsContactMethodController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:وسائل الاتصال بالعميل')->only([
            'index',
            'edit',
            'store',
            'update',
            'destroy',
        ]);
    }

    use CommonSettingController;

    protected $model = SettingsContactMethod::class;

    public function index()
    {
        return $this->index_trait($this->model, 'tenants.general_setting.contact_methods.settings_contact_methods', 'general-settings.settings-contact-methods');
    }

    public function edit($id) 
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

    public function destroy($id)
    {
        try {
            $contactMethod = SettingsContactMethod::findOrFail($id);

            // التحقق من وجود ارتباطات (في حالة وجود اتصالات أو عملاء مرتبطين)
            // if ($contactMethod->customerContacts()->count() > 0) {
            //     throw new \Exception('لا يمكن حذف وسيلة الاتصال هذه لأنها مرتبطة بعملاء. يجب تغيير وسيلة الاتصال للعملاء أولاً.');
            // }

            // if ($contactMethod->communications()->count() > 0) {
            //     throw new \Exception('لا يمكن حذف وسيلة الاتصال هذه لأنها مرتبطة باتصالات. يجب حذف الاتصالات أولاً.');
            // }

            $contactMethod->delete();
            return redirect()->back()->with('success', 'تم الحذف بنجاح.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
