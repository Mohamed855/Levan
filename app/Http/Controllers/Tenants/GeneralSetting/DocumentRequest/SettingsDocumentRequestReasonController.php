<?php

namespace App\Http\Controllers\Tenants\GeneralSetting\DocumentRequest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenants\Traits\CommonSettingController;
use App\Http\Requests\Tenants\GeneralSetting\SaveDocumentRequestReasonRequest;
use App\Models\Tenants\GeneralSetting\DocumentRequest\SettingsDocumentRequestReason;

class SettingsDocumentRequestReasonController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user('tenant');

            abort_unless(
                $user?->can('document_request_reasons.view') || $user?->can('system.settings'),
                403,
            );

            return $next($request);
        });
    }

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

    public function store(SaveDocumentRequestReasonRequest $request)
    {
        return $this->store_trait($request, $this->model);
    }

    public function update(SaveDocumentRequestReasonRequest $request, $id)
    {
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
