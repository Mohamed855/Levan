<?php

namespace App\Http\Controllers\Tenants\Archive\Portfolio;

use App\Http\Controllers\Controller;
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
        $this->middleware('can:أرشيف المحافظ')->only(['index']);
        $this->middleware('can:إسترجاع السجلات')->only(['restore', 'massRestore']);
        $this->middleware('can:حذف نهائي للسجلات')->only(['forceDelete', 'massForceDelete']);
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
    public function restore(int $portfolioId): RedirectResponse
    {
        try {
            $portfolio = Portfolio::onlyTrashed()->findOrFail($portfolioId);
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
    public function forceDelete(int $portfolioId): RedirectResponse
    {
        try {
            $portfolio = Portfolio::onlyTrashed()->findOrFail($portfolioId);
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
    public function massRestore(Request $request): JsonResponse
    {
        try {
            $portfolioIds = $request->input('ids', []);

            if (empty($portfolioIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم تحديد أي محفظة'
                ], 400);
            }

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
    public function massForceDelete(Request $request): JsonResponse
    {
        try {
            $portfolioIds = $request->input('ids', []);

            if (empty($portfolioIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم تحديد أي محفظة'
                ], 400);
            }

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
