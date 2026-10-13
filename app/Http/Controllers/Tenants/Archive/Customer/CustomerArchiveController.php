<?php

namespace App\Http\Controllers\Tenants\Archive\Customer;

use App\DataTables\Tenants\Archive\Customer\ArchivedCustomersDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Archive\ArchiveItemIdRequest;
use App\Http\Requests\Tenants\Archive\BulkArchiveActionRequest;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Archive\Customer\CustomerArchiveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerArchiveController extends Controller
{
    protected CustomerArchiveService $customerArchiveService;

    /*
    |--------------------------------------------------------------------------
    | الإعدادات الأساسية والتحقق من الصلاحيات
    |--------------------------------------------------------------------------
    */
    public function __construct(CustomerArchiveService $customerArchiveService)
    {
        $this->customerArchiveService = $customerArchiveService;
        $this->middleware('auth');
        $this->middleware('can:customers_archive.view')->only(['index']);
        $this->middleware('can:customers_archive.restore')->only(['restore', 'massRestore']);
        $this->middleware('can:customers_archive.force_delete')->only(['forceDelete', 'massForceDelete']);
    }

    /*
    |--------------------------------------------------------------------------
    | عرض صفحة أرشيف العملاء مع الإحصائيات
    |--------------------------------------------------------------------------
    */
    public function index(ArchivedCustomersDataTable $dataTable)
    {
        try {
            $companies = Company::active()
                ->select('id', 'name', 'logo_path')
                ->orderBy('name')
                ->get();

            $portfolios = Portfolio::active()
                ->select('id', 'name', 'company_id')
                ->with('company:id,name')
                ->orderBy('name')
                ->get();

            $users = User::select('id', 'name')
                ->orderBy('name')
                ->get();

            $contactResults = ContactResult::select('id', 'name')
                ->orderBy('name')
                ->get();

            return $dataTable->render('tenants.archive.customers.index', [
                'companies' => $companies,
                'portfolios' => $portfolios,
                'users' => $users,
                'contactResults' => $contactResults,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | استرجاع عميل من الأرشيف
    |--------------------------------------------------------------------------
    */
    public function restore(ArchiveItemIdRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $customer = Customer::onlyTrashed()->findOrFail($request->validated('id'));
            $this->customerArchiveService->restoreCustomer($customer);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم استرجاع العميل بنجاح']);
            }

            return redirect()
                ->back()
                ->with('success', 'تم استرجاع العميل بنجاح');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء استرجاع العميل'], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء استرجاع العميل');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | حذف عميل نهائياً من الأرشيف
    |--------------------------------------------------------------------------
    */
    public function forceDelete(ArchiveItemIdRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $customer = Customer::onlyTrashed()->findOrFail($request->validated('id'));
            $this->customerArchiveService->forceDeleteCustomer($customer);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف العميل نهائياً']);
            }

            return redirect()
                ->back()
                ->with('success', 'تم حذف العميل نهائياً');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء الحذف النهائي'], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء الحذف النهائي');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | استرجاع جماعي للعملاء
    |--------------------------------------------------------------------------
    */
    public function massRestore(BulkArchiveActionRequest $request): JsonResponse
    {
        try {
            $customerIds = $request->validated('ids');

            $restoredCount = $this->customerArchiveService->massRestoreCustomers($customerIds);

            return response()->json([
                'success' => true,
                'message' => "تم استرجاع {$restoredCount} عميل بنجاح",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الاستعادة الجماعية',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | حذف نهائي جماعي للعملاء
    |--------------------------------------------------------------------------
    */
    public function massForceDelete(BulkArchiveActionRequest $request): JsonResponse
    {
        try {
            $customerIds = $request->validated('ids');

            $deletedCount = $this->customerArchiveService->massForceDeleteCustomers($customerIds);

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deletedCount} عميل نهائياً",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحذف النهائي الجماعي',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | جلب جميع معرفات العملاء حسب الفلاتر
    |--------------------------------------------------------------------------
    */
    public function getAllIds(Request $request): JsonResponse
    {
        try {
            $query = $this->customerArchiveService->getArchivedCustomersQuery();

            $filters = $request->only([
                'customer_type',
                'company_id',
                'portfolio_id',
                'relationship_manager_id',
                'last_contact_result_id',
                'search',
            ]);

            $query = $this->customerArchiveService->filterArchivedCustomersQuery($query, $filters);
            $ids = $query->pluck('customers.id');

            return response()->json([
                'success' => true,
                'customer_ids' => $ids,
                'count' => $ids->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب البيانات',
            ], 500);
        }
    }
}
