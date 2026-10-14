<?php

namespace App\Http\Controllers\Admin\ContentManagement\Portfolio;

use App\Actions\Admin\ContentManagement\ManagePortfolioAction;
use App\DataTables\Admin\ContentManagement\Portfolio\PortfolioDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\ContentManagement\StorePortfolioRequest;
use App\Http\Requests\Admin\ContentManagement\UpdatePortfolioRequest;
use App\Models\Admin\ContentManagement\Portfolio;

class PortfolioController extends Controller
{
    public function __construct(private readonly ManagePortfolioAction $portfolios)
    {
        $this->middleware('auth:admin');
        $this->authorizeResource(Portfolio::class, 'portfolio');
        $this->middleware('can:update,portfolio')->only(['toggleStatus']);
    }

    public function index(PortfolioDataTable $datatable)
    {
        $portfolio = Portfolio::query();

        $stats = [
            'total' => (clone $portfolio)->count(),
            'active' => (clone $portfolio)->where('status', 1)->count(),
            'inactive' => (clone $portfolio)->where('status', 0)->count(),
        ];
        $filterOptions = [
            'statuses' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'غير نشط'],
            ],
        ];

        return $datatable->render('admin.content_management.portfolio.index', compact('stats', 'filterOptions'));
    }

    public function create()
    {
        return view('admin.content_management.portfolio.create');
    }

    public function store(StorePortfolioRequest $request)
    {
        $validated = $request->validated();

        try {
            $this->portfolios->create($validated, $request->file('logo'));

            return redirect()->route('admin.content_management.portfolio.index')
                ->with('success', 'تم إضافة المحفظة بنجاح');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ: '.$e->getMessage());
        }
    }

    public function show(Portfolio $portfolio)
    {
        return view('admin.content_management.portfolio.details', compact('portfolio'));
    }

    public function edit(Portfolio $portfolio)
    {
        return view('admin.content_management.portfolio.edit', compact('portfolio'));
    }

    public function update(UpdatePortfolioRequest $request, Portfolio $portfolio)
    {
        $validated = $request->validated();

        try {
            $this->portfolios->update($portfolio, $validated, $request->file('logo'));

            return redirect()->route('admin.content_management.portfolio.index')
                ->with('success', 'تم تحديث المحفظة بنجاح');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ: '.$e->getMessage());
        }
    }

    public function destroy(AdminAuthenticatedRequest $request, Portfolio $portfolio)
    {
        try {
            $this->portfolios->delete($portfolio);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف المحفظة بنجاح']);
            }

            return redirect()->route('admin.content_management.portfolio.index')
                ->with('success', 'تم حذف المحفظة بنجاح');
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ: '.$e->getMessage());
        }
    }

    public function toggleStatus(AdminAuthenticatedRequest $request, Portfolio $portfolio)
    {
        try {
            $newStatus = $this->portfolios->toggleStatus($portfolio);

            return response()->json([
                'success' => true,
                'message' => $newStatus
                    ? 'تم تفعيل المحفظة بنجاح'
                    : 'تم إيقاف المحفظة بنجاح',
                'status' => $newStatus,
                'badge' => $newStatus
                    ? '<span class="status-badge badge bg-success">نشط</span>'
                    : '<span class="status-badge badge bg-danger">غير نشط</span>',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر تحديث حالة المحفظة.',
            ], 500);
        }
    }
}
