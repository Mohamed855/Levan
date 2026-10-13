<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\UpdateRequest;

use App\DataTables\Tenants\BusinessCenter\Customer\UpdateRequest\CustomerUpdateRequestsDataTable;
use App\Exports\Tenants\BusinessCenter\Customer\UpdateRequest\CustomerUpdateRequestsExport;
use App\Http\Controllers\Controller;
use App\Models\Tenants\BusinessCenter\Customers\UpdateRequest\CustomerUpdateRequest;
use App\Services\Tenants\BusinessCenter\Customer\UpdateRequest\CustomerUpdateRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class CustomerUpdateRequestController extends Controller
{
    protected CustomerUpdateRequestService $updateRequestService;

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function __construct(CustomerUpdateRequestService $updateRequestService)
    {
        $this->updateRequestService = $updateRequestService;
        $this->middleware('auth');
        $this->middleware('can:customer_update_requests.view')->only(['index', 'show']);
        $this->middleware('can:customer_update_requests.approve')->only(['approve', 'bulkApprove']);
        $this->middleware('can:customer_update_requests.reject')->only(['reject', 'bulkReject']);
    }

    /*
    |--------------------------------------------------------------------------
    | Display Update Requests List
    |--------------------------------------------------------------------------
    */
    public function index(CustomerUpdateRequestsDataTable $dataTable)
    {
        $statistics = $this->updateRequestService->getRequestsStatistics();

        return $dataTable->render('tenants.business_center.customers.update-requests.index', [
            'totalRequests' => $statistics['total'],
            'pendingRequests' => $statistics['pending'],
            'approvedRequests' => $statistics['approved'],
            'rejectedRequests' => $statistics['rejected'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Update Request Details
    |--------------------------------------------------------------------------
    */
    public function show(int $id): View
    {
        $updateRequest = $this->updateRequestService->getRequestDetails($id);

        if (! $updateRequest) {
            abort(404, 'طلب التحديث غير موجود');
        }

        $changes = $this->updateRequestService->compareData(
            $updateRequest->customer,
            $updateRequest->requested_data
        );

        return view('tenants.business_center.customers.update-requests.show', [
            'updateRequest' => $updateRequest,
            'changes' => $changes,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Update Request
    |--------------------------------------------------------------------------
    */
    public function approve(int $id): JsonResponse
    {
        try {
            $updateRequest = CustomerUpdateRequest::find($id);

            if (! $updateRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'طلب التحديث غير موجود',
                ], 404);
            }

            if ($updateRequest->status !== CustomerUpdateRequest::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب ليس في حالة معلقة',
                ], 400);
            }

            $this->updateRequestService->approveUpdateRequest($updateRequest);

            return response()->json([
                'success' => true,
                'message' => 'تمت الموافقة على طلب التحديث بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الموافقة على الطلب: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reject Update Request
    |--------------------------------------------------------------------------
    */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $updateRequest = CustomerUpdateRequest::find($id);

            if (! $updateRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'طلب التحديث غير موجود',
                ], 404);
            }

            if ($updateRequest->status !== CustomerUpdateRequest::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'الطلب ليس في حالة معلقة',
                ], 400);
            }

            $reason = $request->input('reason');
            $this->updateRequestService->rejectUpdateRequest($updateRequest, $reason);

            return response()->json([
                'success' => true,
                'message' => 'تم رفض طلب التحديث بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفض الطلب: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Approve Update Requests
    |--------------------------------------------------------------------------
    */
    public function bulkApprove(Request $request): JsonResponse
    {
        try {
            $requestIds = $request->input('request_ids', []);

            if (empty($requestIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'يرجى تحديد طلب واحد على الأقل',
                ], 400);
            }

            $results = $this->updateRequestService->bulkApproveRequests($requestIds);

            $message = "تمت الموافقة على {$results['success']} طلب بنجاح";
            if ($results['failed'] > 0) {
                $message .= "، وفشل {$results['failed']} طلب";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الموافقة على الطلبات: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Reject Update Requests
    |--------------------------------------------------------------------------
    */
    public function bulkReject(Request $request): JsonResponse
    {
        try {
            $requestIds = $request->input('request_ids', []);
            $reason = $request->input('reason');

            if (empty($requestIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'يرجى تحديد طلب واحد على الأقل',
                ], 400);
            }

            $results = $this->updateRequestService->bulkRejectRequests($requestIds, $reason);

            $message = "تم رفض {$results['success']} طلب بنجاح";
            if ($results['failed'] > 0) {
                $message .= "، وفشل {$results['failed']} طلب";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفض الطلبات: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Pending Requests Count
    |--------------------------------------------------------------------------
    */
    public function getPendingCount(): JsonResponse
    {
        try {
            $count = $this->updateRequestService->getPendingRequestsCount();

            return response()->json([
                'success' => true,
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب عدد الطلبات المعلقة',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Export Fields
    |--------------------------------------------------------------------------
    */
    public function exportFields(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status',
                'requested_by',
                'search_query',
            ]);

            $fields = [
                [
                    'key' => 'id',
                    'label' => 'رقم الطلب',
                    'selected' => true,
                ],
                [
                    'key' => 'customer_name',
                    'label' => 'اسم العميل',
                    'selected' => true,
                ],
                [
                    'key' => 'customer_identity_number',
                    'label' => 'رقم الهوية',
                    'selected' => true,
                ],
                [
                    'key' => 'requested_by_name',
                    'label' => 'مقدم الطلب',
                    'selected' => true,
                ],
                [
                    'key' => 'created_at',
                    'label' => 'تاريخ الطلب',
                    'selected' => true,
                ],
                [
                    'key' => 'status',
                    'label' => 'الحالة',
                    'selected' => true,
                ],
                [
                    'key' => 'approved_by_name',
                    'label' => 'معالج الطلب',
                    'selected' => false,
                ],
                [
                    'key' => 'approved_at',
                    'label' => 'تاريخ المعالجة',
                    'selected' => false,
                ],
                [
                    'key' => 'rejection_reason',
                    'label' => 'سبب الرفض',
                    'selected' => false,
                ],
                [
                    'key' => 'requested_data',
                    'label' => 'البيانات المطلوبة',
                    'selected' => false,
                ],
            ];

            $query = $this->updateRequestService->getFilteredRequestsQuery($filters);
            $count = $query->count();

            return response()->json([
                'success' => true,
                'fields' => $fields,
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات التصدير',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Export Excel
    |--------------------------------------------------------------------------
    */
    public function exportExcel(Request $request)
    {
        try {
            $filters = $request->only([
                'status',
                'requested_by',
                'search_query',
            ]);

            $selectedFieldsInput = $request->input('selected_fields');

            if (is_array($selectedFieldsInput)) {
                $selectedFields = $selectedFieldsInput;
            } elseif (is_string($selectedFieldsInput)) {
                $selectedFields = json_decode($selectedFieldsInput, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'success' => false,
                        'message' => 'بيانات الحقول المحددة غير صالحة',
                    ], 400);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات الحقول المحددة غير صالحة',
                ], 400);
            }

            if (! is_array($selectedFields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات الحقول المحددة غير صالحة',
                ], 400);
            }

            if (empty($selectedFields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'يرجى اختيار حقل واحد على الأقل',
                ], 400);
            }

            $query = $this->updateRequestService->getFilteredRequestsQuery($filters);
            $requests = $query->get();

            $fileName = 'customer_update_requests_'.now()->format('Y_m_d_H_i_s').'.xlsx';

            return Excel::download(new CustomerUpdateRequestsExport($requests, $selectedFields), $fileName);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تصدير البيانات: '.$e->getMessage(),
            ], 500);
        }
    }
}
