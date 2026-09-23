<?php

namespace App\Http\Controllers\Tenants\Branch;

use App\DataTables\Tenants\Branch\BranchDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Branch\StoreBranchRequest;
use App\Http\Requests\Tenants\Branch\UpdateBranchRequest;
use App\Models\Tenants\Branch\Branch;
use App\Services\Tenants\Branch\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(private BranchService $branchService)
    {
        // ✅ تأكد من استخدام guard tenant
        $this->middleware('auth:tenant');
        // $this->middleware('can:الفروع')->only(['index']);
        // $this->middleware('can:إضافة فرع')->only(['create', 'store']);
        // $this->middleware('can:تعديل فرع')->only(['edit', 'update']);
        // $this->middleware('can:حذف فرع')->only(['destroy']);

        $this->middleware('branches.limit')->only(['create', 'store']);
    }

    /*
    |--------------------------------------------------------------------------
    | عرض قائمة الفروع
    |--------------------------------------------------------------------------
    */
    public function index(BranchDataTable $dataTable)
    {
        try {
            $statistics = $this->branchService->getStatistics();

            return $dataTable->render('tenants.branches.index', [
                'totalBranches'    => $statistics['total'],
                'activeBranches'   => $statistics['active'],
                'inactiveBranches' => $statistics['inactive'],
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | عرض صفحة إضافة فرع جديد
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $generatedCode = $this->branchService->generateCode();
        return view('tenants.branches.create', compact('generatedCode'));
    }

    /*
    |--------------------------------------------------------------------------
    | حفظ الفرع الجديد
    |--------------------------------------------------------------------------
    */
    public function store(StoreBranchRequest $request)
    {
        try {
            $this->branchService->create($request->validated());

            return redirect()
                ->to(tenant_route_name('branches.index'))
                ->with('success', 'تم إضافة الفرع بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة الفرع');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | عرض صفحة تعديل الفرع
    |--------------------------------------------------------------------------
    */
    public function edit(Branch $branch)
    {
        return view('tenants.branches.edit', compact('branch'));
    }

    /*
    |--------------------------------------------------------------------------
    | تحديث الفرع
    |--------------------------------------------------------------------------
    */
    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        try {
            $this->branchService->update($branch, $request->validated());

            return redirect()
                ->to(tenant_route_name('branches.index'))
                ->with('success', 'تم تحديث الفرع بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الفرع');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | حذف الفرع
    |--------------------------------------------------------------------------
    */
    public function destroy(Branch $branch)
    {
        try {
            if ($branch->users()->count() > 0) {
                return redirect()->back()->with('error', 'لا يمكن حذف الفرع لأنه مرتبط بمستخدمين');
            }

            $branch->delete();
            return redirect()->back()->with('success', 'تم حذف الفرع بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف الفرع');
        }
    }

    /*
   |--------------------------------------------------------------------------
   |  Toggle Branch Status
   |--------------------------------------------------------------------------
   */
    public function toggleStatus(Branch $branch): JsonResponse
    {
        try {
            $newStatus = $branch->status === 'active' ? 'inactive' : 'active';
            $branch->update(['status' => $newStatus]);

            $statusText = $newStatus === 'active' ? 'نشط' : 'غير نشط';

            return response()->json([
                'success' => true,
                'message' => 'تم تغيير حالة الفرع إلى: ' . $statusText,
                'status'  => $newStatus,
                'status_text' => $statusText
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تبديل الحالة'
            ], 500);
        }
    }
}