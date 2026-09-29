<?php

namespace App\Http\Controllers\Tenants\Archive\Company;

use App\Http\Controllers\Controller;
use App\DataTables\Tenants\Archive\Company\ArchivedCompaniesDataTable;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Services\Tenants\Archive\Company\CompanyArchiveService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class CompanyArchiveController extends Controller
{
    protected CompanyArchiveService $companyArchiveService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Inject dependencies and apply authorization middleware.
    */
    public function __construct(CompanyArchiveService $companyArchiveService)
    {
        $this->companyArchiveService = $companyArchiveService;
        $this->middleware('auth');
        $this->middleware('can:companies_archive.view')->only(['index']);
        $this->middleware('can:companies_archive.restore')->only(['restore', 'massRestore']);
        $this->middleware('can:companies_archive.force_delete')->only(['forceDelete', 'massForceDelete']);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    | Render archived companies list with statistics.
    */
    public function index(ArchivedCompaniesDataTable $dataTable)
    {
        try {
            $statistics = $this->companyArchiveService->getArchivedCompanyStatistics();

            return $dataTable->render('tenants.archive.companies.index', [
                'totalArchivedCompanies'    => $statistics['total'],
                'archivedActiveCompanies'   => $statistics['active'],
                'archivedInactiveCompanies' => $statistics['inactive'],
                'recentlyArchivedCompanies' => $statistics['recently_archived'],
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Restore Company
    |--------------------------------------------------------------------------
    | Restore the specified archived company.
    */
    public function restore(int $companyId): RedirectResponse
    {
        try {
            $company = Company::onlyTrashed()->findOrFail($companyId);
            $this->companyArchiveService->restoreCompany($company);

            return redirect()
                ->back()
                ->with('success', 'تم استرجاع الشركة بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء استرجاع الشركة');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Force Delete Company
    |--------------------------------------------------------------------------
    | Permanently delete the specified archived company.
    */
    public function forceDelete(int $companyId): RedirectResponse
    {
        try {
            $company = Company::onlyTrashed()->findOrFail($companyId);
            $this->companyArchiveService->forceDeleteCompany($company);

            return redirect()
                ->back()
                ->with('success', 'تم حذف الشركة نهائياً');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء الحذف النهائي');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Mass Restore
    |--------------------------------------------------------------------------
    | Restore multiple archived companies at once.
    */
    public function massRestore(Request $request): JsonResponse
    {
        try {
            $companyIds = $request->input('ids', []);

            if (empty($companyIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم تحديد أي شركة'
                ], 400);
            }

            $restoredCount = $this->companyArchiveService->massRestoreCompanies($companyIds);

            return response()->json([
                'success' => true,
                'message' => "تم استرجاع {$restoredCount} شركة بنجاح"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الاستعادة الجماعية'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Mass Force Delete
    |--------------------------------------------------------------------------
    | Permanently delete multiple archived companies at once.
    */
    public function massForceDelete(Request $request): JsonResponse
    {
        try {
            $companyIds = $request->input('ids', []);

            if (empty($companyIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم تحديد أي شركة'
                ], 400);
            }

            $deletedCount = $this->companyArchiveService->massForceDeleteCompanies($companyIds);

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deletedCount} شركة نهائياً"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحذف النهائي الجماعي'
            ], 500);
        }
    }
}
