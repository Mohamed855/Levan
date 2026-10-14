<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\PhoneRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Customer\PhoneRequest\CustomerPhoneRequest;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Services\Tenants\BusinessCenter\Customer\PhoneRequest\CustomerPhoneRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CustomerPhoneRequestController extends Controller
{
    protected CustomerPhoneRequestService $phoneRequestService;

    /*
    |--------------------------------------------------------------------------
    | Get Phone Requests List
    |--------------------------------------------------------------------------
    | جلب قائمة طلبات الهاتف للعميل (لتحديثها عبر Ajax)
    */
    public function getPhoneRequestsList(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        try {
            $customer->load(['phoneRequests' => function ($query) {
                $query->where('requested_by', auth()->id());
            }, 'phoneRequests.requestedBy']);

            $html = view('tenants.business_center.customers.phone-requests.list', [
                'customer' => $customer,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | تهيئة المتحكم مع حقن خدمة طلبات الهاتف
    */
    public function __construct(CustomerPhoneRequestService $phoneRequestService)
    {
        $this->phoneRequestService = $phoneRequestService;
        $this->middleware('auth');
    }

    /*
    |--------------------------------------------------------------------------
    | Create Phone Request Form
    |--------------------------------------------------------------------------
    | عرض نموذج إضافة رقم هاتف جديد للعميل
    */
    public function create(Customer $customer): View
    {
        $this->authorize('update', $customer);

        $customer->load(['phoneRequests' => function ($query) {
            $query->where('requested_by', auth()->id());
        }, 'phoneRequests.requestedBy']);

        return view('tenants.business_center.customers.phone-requests.create', [
            'customer' => $customer,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store Phone Request
    |--------------------------------------------------------------------------
    | حفظ طلب إضافة رقم هاتف جديد للعميل، إما مباشرة أو كطلب موافقة
    | مع التحقق من صحة الرقم وعدم تكراره
    */
    public function store(CustomerPhoneRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        try {
            $validated = $request->validated();

            if ($this->phoneRequestService->canUpdateDirectly()) {
                $this->phoneRequestService->addPhoneDirectly($customer->id, $validated);

                return response()->json([
                    'success' => true,
                    'message' => 'تم إضافة رقم الهاتف بنجاح',
                ]);
            } else {
                try {
                    $this->phoneRequestService->createPhoneUpdateRequest($customer->id, $validated);

                    return response()->json([
                        'success' => true,
                        'message' => 'تم إرسال طلب إضافة الهاتف للموافقة',
                    ]);
                } catch (\InvalidArgumentException $e) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                    ], 422);
                }
            }
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة رقم الهاتف',
            ], 500);
        }
    }
}
