<?php
namespace App\Http\Controllers\Admin\ContentManagement\Portfolio;

use App\DataTables\Admin\ContentManagement\Portfolio\PortfolioDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentManagement\StorePortfolioRequest;
use App\Http\Requests\Admin\ContentManagement\UpdatePortfolioRequest;
use App\Models\Admin\ContentManagement\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends Controller
{
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
            if ($request->hasFile('logo')) {
                $validated['logo'] = $request->file('logo')->store('portfolios', 'public');
            }

            Portfolio::create($validated);

            return redirect()->route('admin.content_management.portfolio.index')
                ->with('success', 'تم إضافة المحفظة بنجاح');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ: ' . $e->getMessage());
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
            if ($request->hasFile('logo')) {
                if ($portfolio->logo) {
                    Storage::disk('public')->delete($portfolio->logo);
                }
                $validated['logo'] = $request->file('logo')->store('portfolios', 'public');
            }

            $portfolio->update($validated);

            return redirect()->route('admin.content_management.portfolio.index')
                ->with('success', 'تم تحديث المحفظة بنجاح');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    public function destroy(Portfolio $portfolio)
    {
        try {
            if ($portfolio->logo) {
                Storage::disk('public')->delete($portfolio->logo);
            }
            $portfolio->delete();

            return redirect()->route('admin.content_management.portfolio.index')
                ->with('success', 'تم حذف المحفظة بنجاح');
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    public function toggleStatus(Request $request, Portfolio $portfolio)
    {
        try {
            $newStatus = !$portfolio->status;
            $portfolio->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => $newStatus 
                    ? 'تم تفعيل المحفظة بنجاح'
                    : 'تم إيقاف المحفظة بنجاح',
                'status' => $newStatus,
                'badge' => $newStatus 
                    ? '<span class="status-badge badge bg-success">نشط</span>'
                    : '<span class="status-badge badge bg-danger">غير نشط</span>'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }
}
