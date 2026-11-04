<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Archive\Tenant;

use App\DataTables\Admin\Archive\Tenant\TenantDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Services\Admin\Companies\CompanyService;
use Illuminate\Support\Facades\Log;

class TenantArchiveController extends Controller
{
    public function __construct(
        private readonly CompanyService $companyService
    ) {
        $this->middleware('auth:admin');
        $this->middleware('can:companies.view')->only(['index']);
        $this->middleware('can:companies.restore')->only(['restore']);
        $this->middleware('can:companies.force_delete')->only(['forceDelete']);
    }

    public function index(TenantDataTable $dataTable)
    {
        $filterOptions = [
            'statuses' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'queued', 'label' => 'في الانتظار'],
                ['value' => 'expired', 'label' => 'منتهي'],
                ['value' => 'cancelled', 'label' => 'ملغي'],
                ['value' => 'pending_payment', 'label' => 'في انتظار الدفع'],
                ['value' => 'suspended', 'label' => 'معلق'],
            ],
        ];

        return $dataTable->render('admin.archive.tenant.index', compact('filterOptions'));
    }

    public function restore(AdminAuthenticatedRequest $request, $id)
    {
        try {
            $company = $this->companyService->restoreCompany((string) $id);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => "تم استرجاع الشركة {$company->company_name} بنجاح."]);
            }

            return redirect()->route('admin.archives.tenants.index')
                ->with('success', "تم استرجاع الشركة {$company->company_name} بنجاح.");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'الشركة غير موجودة في الأرشيف.'], 404);
            }

            return redirect()->route('admin.archives.tenants.index')->with('error', 'الشركة غير موجودة في الأرشيف.');
        } catch (\Throwable $e) {
            Log::error('Error restoring company: '.$e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء استرجاع الشركة.'], 500);
            }

            return redirect()->route('admin.archives.tenants.index')
                ->with('error', 'حدث خطأ أثناء استرجاع الشركة.');
        }
    }

    public function forceDelete(AdminAuthenticatedRequest $request, $id)
    {
        try {
            $this->companyService->forceDeleteCompany((string) $id);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف الشركة نهائيًا بنجاح.']);
            }

            return redirect()->route('admin.archives.tenants.index')
                ->with('success', 'تم حذف الشركة نهائيًا بنجاح.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'الشركة غير موجودة في الأرشيف.'], 404);
            }

            return redirect()->route('admin.archives.tenants.index')->with('error', 'الشركة غير موجودة في الأرشيف.');
        } catch (\Throwable $e) {
            Log::error('Error force deleting company: '.$e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء الحذف النهائي للشركة.'], 500);
            }

            return redirect()->route('admin.archives.tenants.index')
                ->with('error', 'حدث خطأ أثناء الحذف النهائي للشركة.');
        }
    }
}
