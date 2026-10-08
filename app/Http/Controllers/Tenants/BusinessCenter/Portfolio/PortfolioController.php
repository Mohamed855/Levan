<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Portfolio;

use App\DataTables\Tenants\BusinessCenter\Portfolios\PortfoliosDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Portfolio\StorePortfolioRequest;
use App\Http\Requests\Tenants\BusinessCenter\Portfolio\UpdatePortfolioRequest;
use App\Http\Requests\Tenants\UpdateResourceStatusRequest;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\BusinessCenter\Portfolio\PortfolioField;
use App\Services\Tenants\BusinessCenter\Portfolio\PortfolioService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class PortfolioController extends Controller
{
    protected PortfolioService $portfolioService;

    /*
    |--------------------------------------------------------------------------
    | Constructor & Dependencies
    |--------------------------------------------------------------------------
    */
    public function __construct(PortfolioService $portfolioService)
    {
        $this->portfolioService = $portfolioService;
        $this->authorizeResource(Portfolio::class, 'portfolio');

        $this->middleware('auth');
        $this->middleware('can:portfolios.view')->only(['index', 'show']);
        $this->middleware('can:portfolios.create')->only(['create', 'store']);
        $this->middleware('can:portfolios.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:portfolios.delete')->only(['destroy']);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */
    public function index(PortfoliosDataTable $dataTable)
    {
        $statistics = $this->portfolioService->getPortfolioStatistics();
        $companies = $this->portfolioService->getActiveCompanies();

        return $dataTable->render('tenants.business_center.portfolios.index', [
            'totalPortfolios'     => $statistics['total'],
            'activePortfolios'    => $statistics['active'],
            'inactivePortfolios'  => $statistics['inactive'],
            'archivedPortfolios'  => $statistics['archived'],
            'recentAssignments'   => $statistics['recent_assignments'],
            'companies'           => $companies,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */
    public function create(): View
    {
        $companies = $this->portfolioService->getActiveCompanies();
        $typeOptions   = PortfolioField::getTypeOptions();

        return view('tenants.business_center.portfolios.create', compact('companies', 'typeOptions'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */
    public function store(StorePortfolioRequest $request): RedirectResponse
    {
        try {
            $dto = $request->toCreateDTO();
            $this->portfolioService->createPortfolio($dto);

            return redirect()->to(tenant_route_name('portfolios.index'))->with('success', 'تم إضافة المحفظة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'حدث خطأ أثناء إضافة المحفظة.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Portfolio
    |--------------------------------------------------------------------------
    */
    public function show(Portfolio $portfolio, PortfolioService $service): View
    {
        $data = $service->buildShowViewData($portfolio);
        return view('tenants.business_center.portfolios.show', $data);
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */
    public function edit(Portfolio $portfolio): View
    {
        $companies = $this->portfolioService->getActiveCompanies();
        $typeOptions   = PortfolioField::getTypeOptions();

        $portfolio->load(['portfolioFields']);

        return view('tenants.business_center.portfolios.edit', compact('portfolio', 'companies', 'typeOptions'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */
    public function update(UpdatePortfolioRequest $request, Portfolio $portfolio): RedirectResponse
    {
        try {
            $dto = $request->toUpdateDTO();
            $this->portfolioService->updatePortfolio($portfolio, $dto);
            return redirect()->to(tenant_route_name('portfolios.index'))->with('success', 'تم تحديث المحفظة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'حدث خطأ أثناء تحديث المحفظة.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */
    public function destroy(Portfolio $portfolio): RedirectResponse
    {
        try {
            $this->portfolioService->deletePortfolio($portfolio);
            return redirect()->back()->with('success', 'تم حذف المحفظة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Status
    |--------------------------------------------------------------------------
    */
    public function toggleStatus(UpdateResourceStatusRequest $request, Portfolio $portfolio): JsonResponse
    {
        try {
            $newStatus = $request->validated('status');
            $portfolio->update(['status' => $newStatus]);

            $statusText = $newStatus === 'active' ? 'نشط' : 'غير نشط';

            return response()->json([
                'success' => true,
                'message' => 'تم تبديل الحالة بنجاح',
                'new_status' => $newStatus,
                'status_text' => $statusText
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تبديل الحالة'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Recent Assignments
    |--------------------------------------------------------------------------
    */
    public function getRecentAssignments(Request $request): JsonResponse
    {
        $days = (int)$request->get('days', 30);
        $portfolios = $this->portfolioService->getRecentAssignments($days)->get();

        return response()->json([
            'success' => true,
            'data' => $portfolios,
            'count' => $portfolios->count()
        ]);
    }
}
