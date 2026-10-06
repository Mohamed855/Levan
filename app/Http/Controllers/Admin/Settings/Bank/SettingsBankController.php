<?php

namespace App\Http\Controllers\Admin\Settings\Bank;

use App\DataTables\Admin\Setting\Bank\BankDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\Bank\StoreBankRequest;
use App\Http\Requests\Admin\Settings\Bank\UpdateBankRequest;
use App\Models\Admin\Bank\Bank;
use Illuminate\Http\JsonResponse;
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

    public function store(StoreBankRequest $request)
    {
        $validated = $request->validated();

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
    public function update(UpdateBankRequest $request, Bank $bank)
    {
        $validated = $request->validated();

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
