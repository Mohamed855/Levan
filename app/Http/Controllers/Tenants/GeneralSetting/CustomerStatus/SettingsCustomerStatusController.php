<?php

namespace App\Http\Controllers\Tenants\GeneralSetting\CustomerStatus;

use App\Actions\Tenants\GeneralSetting\GeneralSettingAction;
use App\DataTables\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatusDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Http\Requests\Tenants\GeneralSetting\CustomerStatus\SaveSettingsCustomerStatusRequest;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
            'toggleStatus',
            'create',
            'show',
            'edit',
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
        $this->authorize('viewAny', SettingsCustomerStatus::class);

        return $dataTable->render('tenants.general_setting.settings_customer_status.index');
    }

    public function create(): RedirectResponse
    {
        return redirect(tenant_route_name('general-settings.settings-customer-status.index'));
    }

    public function show($id): JsonResponse
    {
        $customerStatus = SettingsCustomerStatus::query()->findOrFail($id);
        $this->authorize('view', $customerStatus);

        return response()->json([
            'success' => true,
            'data' => $customerStatus,
        ]);
    }

    public function edit($id): JsonResponse
    {
        return $this->show($id);
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
            $this->authorize('create', SettingsCustomerStatus::class);
            app(GeneralSettingAction::class)->create(SettingsCustomerStatus::class, $validatedData, auth('tenant')->id());

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الحالة بنجاح',
            ]);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الحالة',
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
            $this->authorize('update', $customerStatus);
            $validatedData = $request->validated();
            app(GeneralSettingAction::class)->update($customerStatus, $validatedData, auth('tenant')->id());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحالة بنجاح',
            ]);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الحالة',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Customer Status (AJAX)
    |--------------------------------------------------------------------------
    | يقوم بحذف حالة عميل من قاعدة البيانات عبر AJAX.
    */
    public function destroy(TenantAuthenticatedRequest $request, $id): RedirectResponse
    {
        try {
            $customerStatus = SettingsCustomerStatus::findOrFail($id);
            $this->authorize('delete', $customerStatus);
            app(GeneralSettingAction::class)->delete($customerStatus);

            return redirect()->back()->with('success', 'تم حذف الحالة بنجاح');
        } catch (AuthorizationException $e) {
            throw $e;
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
    public function toggleStatus(TenantAuthenticatedRequest $request, $id): JsonResponse
    {
        try {
            $customerStatus = SettingsCustomerStatus::findOrFail($id);
            $this->authorize('toggleStatus', $customerStatus);
            $status = app(GeneralSettingAction::class)->toggleStatus($customerStatus);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحالة بنجاح',
                'status' => $status,
            ]);
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تغيير الحالة',
            ], 500);
        }
    }
}
