<?php

namespace App\Http\Controllers\Tenants\GeneralSetting\DocumentRequest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenants\Traits\CommonSettingController;
use App\Models\Tenants\GeneralSetting\DocumentRequest\SettingsDocumentRequestReason;
use Illuminate\Http\Request;

class SettingsDocumentRequestReasonController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('can:قائمة أسباب طلب المستندات')->only([
    //         'index',
    //         'edit',
    //         'store',
    //         'update',
    //         'destroy',
    //     ]);
    // }

    use CommonSettingController;

    protected $model = SettingsDocumentRequestReason::class;

    public function index()
    {
        return $this->index_trait($this->model, 'tenants.general_setting.document_request_reasons.settings_document_request_reasons', 'general-settings.settings-document-request-reasons');
    }

    public function edit($id) // لتغيير حالة أسباب طلب المستندات
    {
        return $this->toggleStatus_trait($this->model, $id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:settings_document_request_reasons,name,NULL,id,deleted_at,NULL',
        ], [
            'name.required' => 'اسم سبب طلب المستندات مطلوب',
            'name.string' => 'اسم سبب طلب المستندات يجب أن يكون نص',
            'name.max' => 'اسم سبب طلب المستندات يجب أن لا يتجاوز 255 حرف',
            'name.unique' => 'اسم سبب طلب المستندات موجود مسبقاً',
        ]);

        return $this->store_trait($request, $this->model);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:settings_document_request_reasons,name,' . $id . ',id,deleted_at,NULL',
        ], [
            'name.required' => 'اسم سبب طلب المستندات مطلوب',
            'name.string' => 'اسم سبب طلب المستندات يجب أن يكون نص',
            'name.max' => 'اسم سبب طلب المستندات يجب أن لا يتجاوز 255 حرف',
            'name.unique' => 'اسم سبب طلب المستندات موجود مسبقاً',
        ]);

        return $this->update_trait($request, $this->model, $id);
    }

    public function destroy($id)
    {
        try {
            $documentRequestReason = SettingsDocumentRequestReason::findOrFail($id);

            // يمكنك إضافة فحص للعلاقات هنا إذا كان هناك جداول مرتبطة
            // if ($documentRequestReason->relatedTable()->count() > 0) {
            //     throw new \Exception('لا يمكن حذف هذا السبب لأنه مرتبط ببيانات أخرى.');
            // }

            $documentRequestReason->delete();
            return redirect()->back()->with('success', 'تم الحذف بنجاح.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}