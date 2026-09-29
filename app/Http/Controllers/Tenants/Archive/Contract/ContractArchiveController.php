<?php

namespace App\Http\Controllers\Tenants\Archive\Contract;

use App\Http\Controllers\Controller;
use App\DataTables\Tenants\Archive\Contract\ArchivedContractsDataTable;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Contract\Contract;
use App\Services\Tenants\Archive\Contract\ContractArchiveService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ContractArchiveController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | الإعدادات الأساسية والتحقق من الصلاحيات
    |--------------------------------------------------------------------------
    */
    public function __construct(
        protected ContractArchiveService $contractArchiveService
    ) {
        $this->middleware('auth');
        $this->middleware('can:contracts_archive.view')->only(['index']);
        $this->middleware('can:contracts_archive.restore')->only(['restore', 'massRestore']);
        $this->middleware('can:contracts_archive.force_delete')->only(['forceDelete', 'massForceDelete']);
    }

    /*
    |--------------------------------------------------------------------------
    | عرض صفحة أرشيف العقود مع الإحصائيات
    |--------------------------------------------------------------------------
    */
    public function index(ArchivedContractsDataTable $dataTable)
    {
        try {
            $statistics = $this->contractArchiveService->getArchivedContractStatistics();

            $companies = Company::select('id', 'name')
                ->orderBy('name')
                ->get();

            return $dataTable->render('tenants.archive.contracts.index', [
                'totalArchivedContracts'    => $statistics['total'],
                'archivedActiveContracts'   => $statistics['active'],
                'archivedExpiredContracts'  => $statistics['expired'],
                'archivedInactiveContracts' => $statistics['inactive'],
                'recentlyArchivedContracts' => $statistics['recently_archived'],
                'commissionTypeStats'       => $statistics['by_commission_type'],
                'companies'                 => $companies,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب بيانات العقود المؤرشفة');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | استرجاع عقد من الأرشيف
    |--------------------------------------------------------------------------
    */
    public function restore(int $contractId): RedirectResponse
    {
        try {
            $contract = Contract::onlyTrashed()->findOrFail($contractId);
            $this->contractArchiveService->restoreContract($contract);

            return redirect()
                ->back()
                ->with('success', 'تم استرجاع العقد بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء استرجاع العقد');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | حذف عقد نهائياً من الأرشيف
    |--------------------------------------------------------------------------
    */
    public function forceDelete(int $contractId): RedirectResponse
    {
        try {
            $contract = Contract::onlyTrashed()->findOrFail($contractId);
            $this->contractArchiveService->forceDeleteContract($contract);

            return redirect()
                ->back()
                ->with('success', 'تم حذف العقد نهائياً');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء الحذف النهائي');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | استرجاع جماعي للعقود
    |--------------------------------------------------------------------------
    */
    public function massRestore(Request $request): JsonResponse
    {
        try {
            $contractIds = $request->input('ids', []);

            if (empty($contractIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم تحديد أي عقد'
                ], 400);
            }

            $restoredCount = $this->contractArchiveService->massRestoreContracts($contractIds);

            return response()->json([
                'success' => true,
                'message' => "تم استرجاع {$restoredCount} عقد بنجاح"
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
    | حذف نهائي جماعي للعقود
    |--------------------------------------------------------------------------
    */
    public function massForceDelete(Request $request): JsonResponse
    {
        try {
            $contractIds = $request->input('ids', []);

            if (empty($contractIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم تحديد أي عقد'
                ], 400);
            }

            $deletedCount = $this->contractArchiveService->massForceDeleteContracts($contractIds);

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deletedCount} عقد نهائياً"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحذف النهائي الجماعي'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | جلب جميع معرفات العقود حسب الفلاتر
    |--------------------------------------------------------------------------
    */
    public function getAllIds(Request $request): JsonResponse
    {
        try {
            $query = $this->contractArchiveService->getArchivedContractsQuery();

            $filters = $request->only([
                'status',
                'company_id',
                'commission_type',
                'auto_renewal',
                'search'
            ]);

            $query = $this->contractArchiveService->filterArchivedContractsQuery($query, $filters);
            $ids = $query->pluck('contracts.id');

            return response()->json([
                'success' => true,
                'contract_ids' => $ids,
                'count' => $ids->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب البيانات'
            ], 500);
        }
    }
}
