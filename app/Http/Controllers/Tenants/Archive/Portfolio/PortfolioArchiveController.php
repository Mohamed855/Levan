<?php

namespace App\Http\Controllers\Tenants\Archive\Portfolio;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Archive\BulkArchiveActionRequest;
use App\Http\Requests\Tenants\Archive\ArchiveItemIdRequest;
use App\DataTables\Tenants\Archive\Portfolio\ArchivedPortfoliosDataTable;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Services\Tenants\Archive\Portfolio\PortfolioArchiveService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class PortfolioArchiveController extends Controller
{
    protected PortfolioArchiveService $portfolioArchiveService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Inject dependencies and apply authorization middleware.
    */
    public function __construct(PortfolioArchiveService $portfolioArchiveService)
    {
        $this->portfolioArchiveService = $portfolioArchiveService;
        $this->middleware('auth');
        $this->middleware('can:portfolios_archive.view')->only(['index']);
        $this->middleware('can:portfolios_archive.restore')->only(['restore', 'massRestore']);
        $this->middleware('can:portfolios_archive.force_delete')->only(['forceDelete', 'massForceDelete']);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    | Render archived portfolios list with statistics.
    */
    public function index(ArchivedPortfoliosDataTable $dataTable)
    {
        try {
            $statistics = $this->portfolioArchiveService->getArchivedPortfolioStatistics();

            $companies = Company::select('id', 'name')->orderBy('name')->get();
            return $dataTable->render('tenants.archive.portfolios.index', [
                'totalArchivedPortfolios'    => $statistics['total'],
                'archivedActivePortfolios'   => $statistics['active'],
                'archivedInactivePortfolios' => $statistics['inactive'],
                'recentlyArchivedPortfolios' => $statistics['recently_archived'],
                'companies' => $companies,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Restore Portfolio
    |--------------------------------------------------------------------------
    | Restore the specified archived portfolio.
    */
    public function restore(ArchiveItemIdRequest $request): RedirectResponse
    {
        try {
            $portfolio = Portfolio::onlyTrashed()->findOrFail($request->validated('id'));
            $this->portfolioArchiveService->restorePortfolio($portfolio);

            return redirect()
                ->back()
                ->with('success', 'تم استرجاع المحفظة بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء استرجاع المحفظة');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Force Delete Portfolio
    |--------------------------------------------------------------------------
    | Permanently delete the specified archived portfolio.
    */
    public function forceDelete(ArchiveItemIdRequest $request): RedirectResponse
    {
        try {
            $portfolio = Portfolio::onlyTrashed()->findOrFail($request->validated('id'));
            $this->portfolioArchiveService->forceDeletePortfolio($portfolio);

            return redirect()
                ->back()
                ->with('success', 'تم حذف المحفظة نهائياً');
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
    | Restore multiple archived portfolios at once.
    */
    public function massRestore(BulkArchiveActionRequest $request): JsonResponse
    {
        try {
            $portfolioIds = $request->validated('ids');

            $restoredCount = $this->portfolioArchiveService->massRestorePortfolios($portfolioIds);

            return response()->json([
                'success' => true,
                'message' => "تم استرجاع {$restoredCount} محفظة بنجاح"
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
    | Permanently delete multiple archived portfolios at once.
    */
    public function massForceDelete(BulkArchiveActionRequest $request): JsonResponse
    {
        try {
            $portfolioIds = $request->validated('ids');

            $deletedCount = $this->portfolioArchiveService->massForceDeletePortfolios($portfolioIds);

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deletedCount} محفظة نهائياً"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحذف النهائي الجماعي'
            ], 500);
        }
    }
}
