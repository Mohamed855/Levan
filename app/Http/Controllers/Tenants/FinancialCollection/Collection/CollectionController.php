<?php

namespace App\Http\Controllers\Tenants\FinancialCollection\Collection;

use App\DataTables\Tenants\FinancialCollection\Collection\CollectionsDataTable;
use App\Data\Tenants\FinancialCollection\Collection\CollectionStoreDto;
use App\Data\Tenants\FinancialCollection\Collection\CollectionUpdateDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\FinancialCollection\Collection\StoreCollectionRequest;
use App\Http\Requests\Tenants\FinancialCollection\Collection\UpdateCollectionRequest;
use App\Http\Requests\Tenants\FinancialCollection\Collection\DeleteSelectedCollectionsRequest;
use App\Http\Requests\Tenants\FinancialCollection\Collection\RecentCollectionsRequest;
use App\Http\Requests\Tenants\FinancialCollection\Collection\RejectCollectionRequest;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Services\Tenants\FinancialCollection\Collection\CollectionService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class CollectionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor & Dependencies
    |--------------------------------------------------------------------------
    | Initialize controller with required dependencies and middleware.
    */

    protected CollectionService $collectionService;

    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
        $this->authorizeResource(Collection::class, 'collection');

        $this->middleware('can:collections.view')->only(['index','show']);
        $this->middleware('can:collections.create')->only(['create', 'store']);
        $this->middleware('can:collections.edit')->only(['edit', 'update']);
        $this->middleware('can:collections.delete')->only(['destroy', 'deleteSelected']);
        $this->middleware('can:collections.approve')->only(['approve']);
        $this->middleware('can:collections.reject')->only(['reject']);
        $this->middleware('can:approve,collection')->only(['approve']);
        $this->middleware('can:reject,collection')->only(['reject']);
    }

    /*
    |--------------------------------------------------------------------------
    | Display My Collections List
    |--------------------------------------------------------------------------
    | Display a listing of my collections with DataTables integration and statistics.
    */
    public function index(CollectionsDataTable $dataTable)
    {
        try {
            $statistics = $this->collectionService->getMyCollectionStatistics();
            $initialCustomers = $this->collectionService->getInitialCustomers();
            $paymentMethods = $this->collectionService->getActivePaymentMethods();

            return $dataTable->render('tenants.financial_collection.collections.index', [
                'totalCollections' => $statistics['total'],
                'pendingCollections' => $statistics['pending'],
                'approvedCollections' => $statistics['approved'],
                'rejectedCollections' => $statistics['rejected'],
                'totalAmount' => number_format($statistics['total_amount'], 2),
                'todayCollections' => $statistics['today_collections'],
                'collectionsByType' => $statistics['by_collection_type'],
                'collectionsByPaymentType' => $statistics['by_payment_type'],
                'initialCustomers' => $initialCustomers,
                'paymentMethods' => $paymentMethods,
            ]);
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['error' => 'حدث خطأ في تحميل البيانات'], 500);
            }
            return redirect()->back()->with('error', 'حدث خطأ في تحميل البيانات: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Create Form
    |--------------------------------------------------------------------------
    | Show the form for creating a new collection.
    */
    public function create(): View
    {
        $initialCustomers = $this->collectionService->getInitialCustomers();
        $paymentMethods = $this->collectionService->getActivePaymentMethods();
        $receiptNumber = $this->collectionService->generateReceiptNumber();

        return view('tenants.financial_collection.collections.create', compact('initialCustomers', 'paymentMethods', 'receiptNumber'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store New Collection
    |--------------------------------------------------------------------------
    | Store a newly created collection in storage.
    */
    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        try {
            // دمج بيانات الطلب مع الملفات
            $data = $request->validated();

            // إضافة الملف إلى البيانات
            if ($request->hasFile('attachment')) {
                $data['attachment'] = $request->file('attachment');
            }

            $this->collectionService->createMyCollection($data);

            return redirect()
                ->to(tenant_route_name('financial-collections.collections.index'))
                ->with('success', 'تم إضافة التحصيل بنجاح');
        } catch (\Exception $e) {
            Log::error('Error storing collection: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة التحصيل: ' . $e->getMessage());
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
        // التأكد من أن التحصيل يخص المستخدم الحالي
        if (!$this->collectionService->canUserViewCollection($collection, auth('tenant')->id())) {
            abort(403, 'غير مسموح لك بعرض هذا التحصيل');
        }

        $collection->load(['customer', 'paymentMethod', 'addedBy', 'approvedBy']);

        return view('tenants.financial_collection.collections.show', compact('collection'));
    }

    /*
    |--------------------------------------------------------------------------
    | Show Edit Form
    |--------------------------------------------------------------------------
    | Show the form for editing the specified collection.
    */
    public function edit(Collection $collection): View
    {
        if (!$this->collectionService->canUserEditCollection($collection, auth('tenant')->id())) {
            abort(403, 'غير مسموح لك بتعديل هذا التحصيل');
        }
        $customers = $this->collectionService->getActiveCustomers();
        $paymentMethods = $this->collectionService->getActivePaymentMethods();

        return view('tenants.financial_collection.collections.edit', compact('collection', 'customers', 'paymentMethods'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update Collection
    |--------------------------------------------------------------------------
    | Update the specified collection in storage.
    */
    public function update(UpdateCollectionRequest $request, Collection $collection): RedirectResponse
    {
        try {
            // دمج بيانات الطلب مع الملفات
            $data = $request->validated();

            // إضافة الملف إلى البيانات
            if ($request->hasFile('attachment')) {
                $data['attachment'] = $request->file('attachment');
            }

            // إضافة علامة حذف الملف إذا تم تحديدها
            if ($request->has('remove_attachment')) {
                $data['remove_attachment'] = true;
            }

            $this->collectionService->updateMyCollection($collection, $data, auth('tenant')->id());

            return redirect()
                ->to(tenant_route_name('financial-collections.collections.index'))
                ->with('success', 'تم تحديث التحصيل بنجاح');
        } catch (\Exception $e) {
            Log::error('Error updating collection: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث التحصيل: ' . $e->getMessage());
        }
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
            $this->collectionService->deleteCollection($collection, auth('tenant')->id());
            return redirect()->back()->with('success', 'تم حذف التحصيل بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Selected Collections
    |--------------------------------------------------------------------------
    | Remove multiple selected collections from storage.
    */
    public function deleteSelected(DeleteSelectedCollectionsRequest $request): JsonResponse
    {
        try {
            $deletedCount = $this->collectionService->deleteSelectedCollections(
                $request->validated('ids'),
                auth('tenant')->id(),
            );

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deletedCount} تحصيل بنجاح",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
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
            $this->collectionService->approveCollection($collection, auth('tenant')->id());

            return response()->json([
                'success' => true,
                'message' => 'تم اعتماد التحصيل بنجاح',
                'new_status' => 'approved',
                'status_text' => 'معتمد'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reject Collection
    |--------------------------------------------------------------------------
    | Reject the specified collection.
    */
    public function reject(RejectCollectionRequest $request, Collection $collection): JsonResponse
    {
        try {
            $this->collectionService->rejectCollection(
                $collection,
                $request->validated('rejection_reason'),
                auth('tenant')->id(),
            );

            return response()->json([
                'success' => true,
                'message' => 'تم رفض التحصيل بنجاح',
                'new_status' => 'rejected',
                'status_text' => 'مرفوض'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Recent Collections
    |--------------------------------------------------------------------------
    | Get collections with recent additions within specified days.
    */
    public function getRecentCollections(RecentCollectionsRequest $request): JsonResponse
    {
        $days = $request->integer('days', 7);
        $collections = $this->collectionService->getMyRecentCollections($days)->get();

        return response()->json([
            'success' => true,
            'data' => $collections,
            'count' => $collections->count()
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Receipt Number
    |--------------------------------------------------------------------------
    | Generate new receipt number for AJAX requests.
    */
    public function generateReceiptNumber(): JsonResponse
    {
        try {
            $receiptNumber = $this->collectionService->generateReceiptNumber();

            return response()->json([
                'success' => true,
                'receipt_number' => $receiptNumber
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في توليد رقم السند'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Search Customers
    |--------------------------------------------------------------------------
    */
    public function searchCustomers(Request $request): JsonResponse
    {
        $search = $request->get('q', '');
        $customers = $this->collectionService->searchCustomers($search);

        return response()->json($customers);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customer
    |--------------------------------------------------------------------------
    */
    public function getCustomer(Customer $customer): JsonResponse
    {
        $customerData = $this->collectionService->getCustomerById($customer->id);

        if (!$customerData) {
            return response()->json(null, 404);
        }

        return response()->json($customerData);
    }
}
