<?php
namespace App\Http\Controllers\Admin\ContentManagement\Portfolio;

use App\DataTables\Admin\ContentManagement\Portfolio\PortfolioDataTable;
use App\Http\Controllers\Controller;
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
        return $datatable->render('admin.content_management.portfolio.index', compact('stats'));
    }

    public function create()
    {
        return view('admin.content_management.portfolio.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|boolean',
        ], [
            'name.required' => 'اسم المحفظة مطلوب',
            'name.string' => 'اسم المحفظة يجب أن يكون نص',
            'name.max' => 'اسم المحفظة لا يمكن أن يتجاوز 255 حرف',
            'logo.image' => 'الشعار يجب أن يكون صورة',
            'logo.mimes' => 'الشعار يجب أن يكون من نوع: jpeg, png, jpg, gif',
            'status.required' => 'حالة المحفظة مطلوبة',
        ]);

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

    public function update(Request $request, Portfolio $portfolio)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|boolean',
        ], [
            'name.required' => 'اسم المحفظة مطلوب',
            'name.string' => 'اسم المحفظة يجب أن يكون نص',
            'name.max' => 'اسم المحفظة لا يمكن أن يتجاوز 255 حرف',
            'logo.image' => 'الشعار يجب أن يكون صورة',
            'logo.mimes' => 'الشعار يجب أن يكون من نوع: jpeg, png, jpg, gif',
            'status.required' => 'حالة المحفظة مطلوبة',
        ]);

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