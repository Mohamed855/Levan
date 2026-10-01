<?php

namespace App\Http\Controllers\Admin\Settings\Bank;

use App\DataTables\Admin\Setting\Bank\BankDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\Bank\Bank;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SettingsBankController extends Controller
{
    private $page  = "admin.settings.banks";
    private $route = "admin.settings.banks";


    public function __construct()
    {
        $this->middleware('can:bank_settings.view');
    }

    public function index(BankDataTable $dataTable)
    {
        try {
            $filterOptions = [
                'statuses' => [
                    ['value' => 'active', 'label' => 'نشط'],
                    ['value' => 'inactive', 'label' => 'غير نشط'],
                ],
            ];

            return $dataTable->render($this->page . '.index', compact('filterOptions'));
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function create()
    {
        return view($this->page . '.create');
    }

    // التحقق يجب ان يكون في ملف منفصل بنفس المسار الكنترول
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'icon' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
            'account_name' => 'required|string|max:255',
            'iban' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
        ], [
            'bank_name.required' => 'اسم البنك مطلوب',
            'icon.required' => 'أيقونة البنك مطلوبة',
            'icon.image' => 'يجب أن يكون الملف صورة',
            'icon.mimes' => 'الصيغ المسموحة: jpeg, png, jpg, svg',
            'icon.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
            'account_name.required' => 'اسم الحساب مطلوب',
            'iban.required' => 'رقم الآيبان مطلوب',
            'account_number.required' => 'رقم الحساب مطلوب',
        ]);

        // رفع الأيقونة
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('banks/icons', 'public');
            $validated['icon'] = $iconPath;
        }

        Bank::create($validated);

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم إضافة البنك بنجاح');
    }

    /**
     * عرض صفحة تعديل بنك
     */
    public function edit(Bank $bank)
    {
        return view($this->page . '.edit', compact('bank'));
    }

    /**
     * تحديث بيانات بنك
     */
    public function update(Request $request, Bank $bank)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'account_name' => 'required|string|max:255',
            'iban' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
        ], [
            'bank_name.required' => 'اسم البنك مطلوب',
            'icon.image' => 'يجب أن يكون الملف صورة',
            'icon.mimes' => 'الصيغ المسموحة: jpeg, png, jpg, svg',
            'icon.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
            'account_name.required' => 'اسم الحساب مطلوب',
            'iban.required' => 'رقم الآيبان مطلوب',
            'account_number.required' => 'رقم الحساب مطلوب',
        ]);

        // رفع الأيقونة الجديدة
        if ($request->hasFile('icon')) {
            // حذف الأيقونة القديمة
            if ($bank->icon && Storage::disk('public')->exists($bank->icon)) {
                Storage::disk('public')->delete($bank->icon);
            }

            $iconPath = $request->file('icon')->store('banks/icons', 'public');
            $validated['icon'] = $iconPath;
        }

        $bank->update($validated);

        return redirect()->route($this->route . '.index')
            ->with('success', 'تم تحديث البنك بنجاح');
    }

    public function toggleStatus(Bank $bank): JsonResponse
    {
        $bank->update(['is_active' => ! $bank->is_active]);

        return response()->json([
            'success' => true,
            'message' => $bank->is_active ? 'تم تفعيل البنك بنجاح' : 'تم تعطيل البنك بنجاح',
        ]);
    }

    /**
     * حذف بنك
     */
    public function destroy(Bank $bank)
    {
        try {
            // حذف الأيقونة
            if ($bank->icon && Storage::disk('public')->exists($bank->icon)) {
                Storage::disk('public')->delete($bank->icon);
            }

            $bank->delete();

            return redirect()->route($this->route . '.index')
                ->with('success', 'تم حذف البنك بنجاح');
        } catch (\Exception $e) {
            return redirect()->route($this->route . '.index')
                ->with('error', 'حدث خطأ أثناء الحذف: ' . $e->getMessage());
        }
    }
}
