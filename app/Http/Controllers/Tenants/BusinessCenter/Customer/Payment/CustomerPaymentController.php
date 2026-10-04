<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Payment;

use App\Http\Controllers\Controller;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Http\Requests\Tenants\FinancialCollection\Collection\StoreCollectionRequest;
use App\Http\Requests\Tenants\FinancialCollection\Collection\UpdateCollectionRequest;
use App\Services\Tenants\FinancialCollection\Collection\CollectionService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class CustomerPaymentController extends Controller
{
    protected CollectionService $collectionService;

    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;

        // $this->middleware('auth');
        // $this->middleware('can:إدارة تحصيلات العملاء')->only(['index']);
        // $this->middleware('can:إضافة تحصيل عميل')->only(['create', 'store']);
        // $this->middleware('can:تعديل تحصيل عميل')->only(['edit', 'update']);
        // $this->middleware('can:حذف تحصيل عميل')->only(['destroy']);
    }

    /*
    |--------------------------------------------------------------------------
    | DataTable for Tab Integration
    |--------------------------------------------------------------------------
    | For displaying payments within customer show page
    */
    public function dataTable(Request $request, Customer $customer): JsonResponse
    {
        $query = Collection::where('customer_id', $customer->id)
            ->with(['paymentMethod', 'addedBy', 'approvedBy']);

        // Apply filters
        $filters = $request->only([
            'status',
            'payment_method_id',
            'collection_type',
            'payment_type',
            'date_from',
            'date_to',
            'search'
        ]);
        $query = $this->collectionService->filterCollectionsQuery($query, $filters);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('receipt_info', function (Collection $payment) {
                $html = '<div>';
                $html .= '<div class="fw-medium">' . e($payment->receipt_number) . '</div>';
                if ($payment->closing_code) {
                    $html .= '<div class="text-muted small">كود: ' . e($payment->closing_code) . '</div>';
                }
                $html .= '</div>';
                return $html;
            })
            ->addColumn('amount', function (Collection $payment) {
                return '<span class="fw-bold text-success">' . number_format($payment->collected_amount, 2) . ' ريال</span>';
            })
            ->addColumn('collection_type', function (Collection $payment) {
                $class = $payment->collection_type === 'cash' ? 'bg-label-info' : 'bg-label-warning';
                return '<span class="badge ' . $class . '">' . e($payment->collection_type_label) . '</span>';
            })
            ->addColumn('payment_method', function (Collection $payment) {
                return '<span>' . e($payment->paymentMethod->name ?? 'غير محدد') . '</span>';
            })
            ->addColumn('payment_type', function (Collection $payment) {
                $class = $payment->payment_type === 'settlement' ? 'bg-label-primary' : 'bg-secondary';
                return '<span class="badge ' . $class . '">' . e($payment->payment_type_label) . '</span>';
            })
            ->addColumn('collection_date', function (Collection $payment) {
                return $payment->formatted_collection_date;
            })
            ->addColumn('status', function (Collection $payment) {
                return '<span class="badge ' . $payment->status_class . '">' . e($payment->status_label) . '</span>';
            })
            ->addColumn('added_by', function (Collection $payment) {
                return '<span>' . e($payment->addedBy->name ?? 'غير محدد') . '</span>';
            })
            ->addColumn('action', function ($row) {
                $actions = '<div class="d-flex justify-content-center">';
                
                if ($row->status === 'pending') {
                    $actions .= '<button type="button" title="تعديل التحصيل" onclick="editCustomerPayment(' . $row->id . ')" class="btn btn-sm btn-icon btn-text-secondary me-1">
                            <i class="ti ti-edit"></i>
                        </button>
                        <button type="button" title="حذف التحصيل" onclick="deleteCustomerPayment(' . $row->id . ')" class="btn btn-sm btn-icon btn-text-secondary me-1">
                            <i class="ti ti-trash"></i>
                        </button>';
                }
                
                if ($row->attachment) {
                    $actions .= '<a href="' . asset('storage/' . $row->attachment) . '" target="_blank" title="عرض المرفق" class="btn btn-sm btn-icon btn-text-secondary">
                            <i class="ti ti-paperclip"></i>
                        </a>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->orderColumn('created_at', fn($query, $order) => $query->orderBy('created_at', $order))
            ->filterColumn('receipt_info', fn($query, $keyword) => $query->where('receipt_number', 'like', "%{$keyword}%"))
            ->rawColumns(['receipt_info', 'amount', 'collection_type', 'payment_method', 'payment_type', 'status', 'added_by', 'action'])
            ->make(true);
    }

    /*
    |--------------------------------------------------------------------------
    | Modal Operations for AJAX
    |--------------------------------------------------------------------------
    */

    /**
     * Get create form for modal
     */
    public function create(Customer $customer): JsonResponse
    {
        try {
            $formData = [
                'customers' => collect([$customer]),
                'paymentMethods' => $this->collectionService->getActivePaymentMethods(),
                'receiptNumber' => $this->collectionService->generateReceiptNumber()
            ];

            $html = view('tenants.business_center.customers.customer_payments.create', [
                'customer' => $customer,
                ...$formData
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'message' => 'تم تحميل النموذج بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل النموذج: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get edit form for modal
     */
    public function edit(Customer $customer, Collection $collection): JsonResponse
    {
        try {
            // التحقق من أن التحصيل يخص هذا العميل
            if ($collection->customer_id !== $customer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'التحصيل غير مرتبط بهذا العميل'
                ], 403);
            }

            $formData = [
                'customers' => collect([$customer]),
                'paymentMethods' => $this->collectionService->getActivePaymentMethods()
            ];

            $html = view('tenants.business_center.customers.customer_payments.edit', [
                'customer' => $customer,
                'collection' => $collection,
                ...$formData
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'message' => 'تم تحميل النموذج بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل النموذج: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store via AJAX
     */
    public function store(StoreCollectionRequest $request, Customer $customer): JsonResponse
    {
        try {
            
            $data = $request->validated();
            $data['customer_id'] = $customer->id;

            $collection = $this->collectionService->createMyCollection($data);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة التحصيل بنجاح',
                'data' => $collection
            ]);
        } catch (\Exception $e) {
            \Log::error('Payment creation error: ' . $e->getMessage(), [
                'customer_id' => $customer->id,
                'request_data' => $request->validated()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة التحصيل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update via AJAX
     */
    public function update(UpdateCollectionRequest $request, Customer $customer, Collection $collection): JsonResponse
    {
        try {
            // التحقق من أن التحصيل يخص هذا العميل
            if ($collection->customer_id !== $customer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'التحصيل غير مرتبط بهذا العميل'
                ], 403);
            }

            $data = $request->validated();
            $updatedCollection = $this->collectionService->updateMyCollection($collection, $data, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث التحصيل بنجاح',
                'data' => $updatedCollection
            ]);
        } catch (\Exception $e) {
            \Log::error('Payment update error: ' . $e->getMessage(), [
                'customer_id' => $customer->id,
                'collection_id' => $collection->id,
                'request_data' => $request->validated()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث التحصيل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete via AJAX
     */
    public function destroy(Customer $customer, Collection $collection): JsonResponse
    {
        try {
            // التحقق من أن التحصيل يخص هذا العميل
            if ($collection->customer_id !== $customer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'التحصيل غير مرتبط بهذا العميل'
                ], 403);
            }

            $this->collectionService->deleteMyCollection($collection, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'تم حذف التحصيل بنجاح'
            ]);
        } catch (\Exception $e) {        
            \Log::error('Payment deletion error: ' . $e->getMessage(), [
                'customer_id' => $customer->id,
                'collection_id' => $collection->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف التحصيل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate new receipt number
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
                'message' => 'حدث خطأ أثناء توليد رقم السند'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | API Methods for Statistics and Data
    |--------------------------------------------------------------------------
    */

    /**
     * Get customer payment statistics
     */
    public function statistics(Customer $customer): JsonResponse
    {
        try {
            $statistics = Collection::where('customer_id', $customer->id)
                ->selectRaw('
                    COUNT(*) as total_payments,
                    COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_payments,
                    COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_payments,
                    COUNT(CASE WHEN status = "rejected" THEN 1 END) as rejected_payments,
                    SUM(CASE WHEN status = "approved" THEN collected_amount ELSE 0 END) as total_approved_amount,
                    SUM(CASE WHEN status = "pending" THEN collected_amount ELSE 0 END) as total_pending_amount
                ')
                ->first();

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات'
            ], 500);
        }
    }

    /**
     * Get recent payments for customer
     */
    public function recentPayments(Customer $customer, Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $recentPayments = Collection::where('customer_id', $customer->id)
                ->with(['paymentMethod', 'addedBy', 'approvedBy'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $recentPayments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب التحصيلات الحديثة'
            ], 500);
        }
    }
}