<?php

namespace App\Http\Controllers\Tenants\GeneralSetting\CustomerStatus;

use App\DataTables\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatusDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\GeneralSetting\CustomerStatus\SaveSettingsCustomerStatusRequest;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsCustomerStatusController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | يقوم بتطبيق الصلاحيات اللازمة على دوال هذا المتحكم.
    */
    public function __construct()
    {
        $this->middleware('can:customer_statuses.view')->only([
            'index',
            'store',
            'update',
            'destroy',
            'toggleStatus'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Display Customer Statuses List
    |--------------------------------------------------------------------------
    | يعرض صفحة قائمة حالات العملاء مع المودال للإضافة والتعديل.
    */
    public function index(SettingsCustomerStatusDataTable $dataTable): View|JsonResponse
    {
        return $dataTable->render('tenants.general_setting.settings_customer_status.index');
    }

    /*
    |--------------------------------------------------------------------------
    | Store New Customer Status (AJAX)
    |--------------------------------------------------------------------------
    | يقوم بتخزين حالة عميل جديدة عبر AJAX.
    */
    public function store(SaveSettingsCustomerStatusRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            SettingsCustomerStatus::create($validatedData + ['user_id' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الحالة بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الحالة'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Customer Status (AJAX)
    |--------------------------------------------------------------------------
    | يقوم بتحديث بيانات حالة عميل موجودة عبر AJAX.
    */
    public function update(SaveSettingsCustomerStatusRequest $request, $id): JsonResponse
    {
        try {
            $customerStatus = SettingsCustomerStatus::findOrFail($id);
            $validatedData = $request->validated();
            $customerStatus->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحالة بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الحالة'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Customer Status (AJAX)
    |--------------------------------------------------------------------------
    | يقوم بحذف حالة عميل من قاعدة البيانات عبر AJAX.
    */
    public function destroy($id): RedirectResponse
    {
        try {
            $customerStatus = SettingsCustomerStatus::findOrFail($id);
            $customerStatus->delete();

            return redirect()->back()->with('success', 'تم حذف الحالة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Status (AJAX)
    |--------------------------------------------------------------------------
    | يقوم بتغيير حالة التفعيل لحالة عميل معينة عبر AJAX.
    */
    public function toggleStatus($id): JsonResponse
    {
        try {
            $customerStatus = SettingsCustomerStatus::findOrFail($id);
            $customerStatus->status = $customerStatus->status === 'active' ? 'inactive' : 'active';
            $customerStatus->save();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحالة بنجاح',
                'status' => $customerStatus->status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تغيير الحالة'
            ], 500);
        }
    }
}
