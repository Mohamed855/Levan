<?php

namespace App\Http\Controllers\Tenants\FinancialCollection\Admin;

use App\DataTables\Tenants\FinancialCollection\Admin\AdminCollectionsDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Services\Tenants\FinancialCollection\Collection\CollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminCollectionsController extends Controller
{
    protected CollectionService $collectionService;

    /*
    |--------------------------------------------------------------------------
    | Constructor & Dependencies
    |--------------------------------------------------------------------------
    | Initialize controller with required dependencies and middleware.
    */
    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;

        $this->middleware('can:admin_collections.view')->only(['index', 'show']);
        $this->middleware('can:collections.approve')->only(['approve']);
        $this->middleware('can:collections.reject')->only(['reject']);
        $this->middleware('can:admin_collections.approve_all')->only(['approveSelected']);
        $this->middleware('can:admin_collections.reject_all')->only(['rejectSelected']);
        $this->middleware('can:admin_collections.delete_selected')->only(['deleteSelected']);
        $this->middleware('can:admin_collections.import')->only(['import', 'importStore']);
    }

    /*
    |--------------------------------------------------------------------------
    | Display All Users Collections
    |--------------------------------------------------------------------------
    | Display a listing of all users collections with management capabilities.
    */
    public function index(AdminCollectionsDataTable $dataTable)
    {
        try {
            $statistics = $this->collectionService->getAllCollectionStatistics();
            $customers = $this->collectionService->getActiveCustomers();
            $paymentMethods = $this->collectionService->getActivePaymentMethods();
            $users = $this->collectionService->getActiveUsers();

            return $dataTable->render('tenants.financial_collection.admin_collections.index', [
                'totalCollections' => $statistics['total'],
                'pendingCollections' => $statistics['pending'],
                'approvedCollections' => $statistics['approved'],
                'rejectedCollections' => $statistics['rejected'],
                'totalAmount' => number_format($statistics['total_amount'], 2),
                'todayCollections' => $statistics['today_collections'],
                'collectionsByType' => $statistics['by_collection_type'],
                'collectionsByPaymentType' => $statistics['by_payment_type'],
                'customers' => $customers->take(10)->toArray(),
                'paymentMethods' => $paymentMethods,
                'users' => $users,
            ]);
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['error' => 'حدث خطأ في تحميل البيانات'], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ في تحميل البيانات: '.$e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Collection Details
    |--------------------------------------------------------------------------
    | Get collection details for modal or AJAX requests.
    */
    public function show(Collection $collection): View
    {
        $collection->load(['customer', 'paymentMethod', 'addedBy', 'approvedBy']);

        return view('tenants.financial_collection.admin_collections.show', compact('collection'));
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Collection
    |--------------------------------------------------------------------------
    | Remove the specified collection from storage.
    */
    public function destroy(Collection $collection): RedirectResponse
    {
        try {
            $this->collectionService->deleteCollection($collection);

            return redirect()->back()->with('success', 'تم حذف التحصيل بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Collection
    |--------------------------------------------------------------------------
    | Approve the specified collection.
    */
    public function approve(Collection $collection): JsonResponse
    {
        try {
            $this->collectionService->approveCollection($collection, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'تم اعتماد التحصيل بنجاح',
                'new_status' => 'approved',
                'status_text' => 'معتمد',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reject Collection
    |--------------------------------------------------------------------------
    | Reject the specified collection.
    */
    public function reject(Request $request, Collection $collection): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ], [
            'rejection_reason.required' => 'يرجى إدخال سبب الرفض',
            'rejection_reason.max' => 'سبب الرفض طويل جداً',
        ]);

        try {
            $this->collectionService->rejectCollection(
                $collection,
                $request->rejection_reason,
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'تم رفض التحصيل بنجاح',
                'new_status' => 'rejected',
                'status_text' => 'مرفوض',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Selected Collections
    |--------------------------------------------------------------------------
    | Approve multiple selected collections.
    */
    public function approveSelected(Request $request): JsonResponse
    {
        if ($request->isJson()) {
            $request->merge($request->json()->all());
        }

        try {
            $validated = $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|numeric|exists:collections,id',
            ]);

            $result = $this->collectionService->approveSelectedCollections(
                $validated['ids'],
                Auth::id()
            );

            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['general' => [$e->getMessage()]],
            ], 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reject Selected Collections
    |--------------------------------------------------------------------------
    | Reject multiple selected collections.
    */
    public function rejectSelected(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:collections,id',
            'rejection_reason' => 'required|string|max:500',
        ], [
            'ids.required' => 'يرجى تحديد تحصيل واحد على الأقل',
            'rejection_reason.required' => 'يرجى إدخال سبب الرفض',
            'rejection_reason.max' => 'سبب الرفض طويل جداً',
        ]);

        try {
            $rejectedCount = $this->collectionService->rejectSelectedCollections(
                $request->ids,
                $request->rejection_reason,
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => "تم رفض {$rejectedCount} تحصيل بنجاح",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Quick Statistics for Admin
    |--------------------------------------------------------------------------
    | Get quick statistics for admin dashboard.
    */
    public function getQuickStats(): JsonResponse
    {
        try {
            $stats = $this->collectionService->getQuickStatsForAdmin();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Selected Collections
    |--------------------------------------------------------------------------
    | Remove multiple selected collections from storage.
    */
    public function deleteSelected(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:collections,id',
        ], [
            'ids.required' => 'يرجى تحديد تحصيل واحد على الأقل',
            'ids.array' => 'البيانات المرسلة غير صحيحة',
            'ids.min' => 'يرجى تحديد تحصيل واحد على الأقل',
            'ids.*.exists' => 'أحد التحصيلات المحددة غير موجود',
        ]);

        try {
            $deletedCount = $this->collectionService->deleteSelectedAdminCollections($request->ids);

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deletedCount} تحصيل بنجاح",
                'deleted_count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Search Customers
    |--------------------------------------------------------------------------
    | البحث في العملاء بـ AJAX
    */
    public function searchCustomers(Request $request): JsonResponse
    {
        $search = $request->get('q', '');
        $customers = $this->collectionService->searchCustomers($search);

        return response()->json($customers);
    }

    /*
    |--------------------------------------------------------------------------
    | Get All Collection IDs with Filters Support
    |--------------------------------------------------------------------------
    | Get collection IDs based on current filters for bulk operations.
    */
    public function getAllCollectionIds(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status',
                'customer_id',
                'payment_method_id',
                'collection_type',
                'payment_type',
                'added_by',
                'date_from',
                'date_to',
            ]);

            $query = $this->collectionService->getAllCollectionsQuery();
            $query = $this->collectionService->filterCollectionsQuery($query, $filters);

            $collectionIds = $query->pluck('collections.id')->toArray();

            return response()->json([
                'success' => true,
                'collection_ids' => $collectionIds,
                'count' => count($collectionIds),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
            ], 500);
        }
    }
}
