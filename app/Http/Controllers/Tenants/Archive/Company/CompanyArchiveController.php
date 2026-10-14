<?php

namespace App\Http\Controllers\Tenants\Archive\Company;

use App\DataTables\Tenants\Archive\Company\ArchivedCompaniesDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Archive\ArchiveItemIdRequest;
use App\Http\Requests\Tenants\Archive\BulkArchiveActionRequest;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Services\Tenants\Archive\Company\CompanyArchiveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

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
            return $dataTable->render('tenants.archive.companies.index');
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
    public function restore(ArchiveItemIdRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $company = Company::onlyTrashed()->findOrFail($request->validated('id'));
            $this->authorize('restore', $company);
            $this->companyArchiveService->restoreCompany($company);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم استرجاع الشركة بنجاح']);
            }

            return redirect()
                ->back()
                ->with('success', 'تم استرجاع الشركة بنجاح');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء استرجاع الشركة'], 500);
            }

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
    public function forceDelete(ArchiveItemIdRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $company = Company::onlyTrashed()->findOrFail($request->validated('id'));
            $this->authorize('forceDelete', $company);
            $this->companyArchiveService->forceDeleteCompany($company);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف الشركة نهائياً']);
            }

            return redirect()
                ->back()
                ->with('success', 'تم حذف الشركة نهائياً');
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
    | Mass Restore
    |--------------------------------------------------------------------------
    | Restore multiple archived companies at once.
    */
    public function massRestore(BulkArchiveActionRequest $request): JsonResponse
    {
        try {
            $companyIds = $request->validated('ids');
            Company::onlyTrashed()->whereKey($companyIds)->get()->each(
                fn (Company $company) => $this->authorize('restore', $company)
            );

            $restoredCount = $this->companyArchiveService->massRestoreCompanies($companyIds);

            return response()->json([
                'success' => true,
                'message' => "تم استرجاع {$restoredCount} شركة بنجاح",
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
    | Mass Force Delete
    |--------------------------------------------------------------------------
    | Permanently delete multiple archived companies at once.
    */
    public function massForceDelete(BulkArchiveActionRequest $request): JsonResponse
    {
        try {
            $companyIds = $request->validated('ids');
            Company::onlyTrashed()->whereKey($companyIds)->get()->each(
                fn (Company $company) => $this->authorize('forceDelete', $company)
            );

            $deletedCount = $this->companyArchiveService->massForceDeleteCompanies($companyIds);

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deletedCount} شركة نهائياً",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحذف النهائي الجماعي',
            ], 500);
        }
    }
}
