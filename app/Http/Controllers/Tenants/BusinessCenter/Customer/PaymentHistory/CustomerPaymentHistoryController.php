<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\PaymentHistory;

use App\Http\Controllers\Controller;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Services\Tenants\BusinessCenter\Customer\PaymentHistory\CustomerPaymentHistoryService;
use Illuminate\Http\JsonResponse;

class CustomerPaymentHistoryController extends Controller
{
    protected CustomerPaymentHistoryService $paymentHistoryService;

    public function __construct(CustomerPaymentHistoryService $paymentHistoryService)
    {
        $this->paymentHistoryService = $paymentHistoryService;
        $this->middleware('auth');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Payment History List
    |--------------------------------------------------------------------------
    | جلب قائمة سجل السدادات للعميل (لتحديثها عبر Ajax)
    */
    public function getPaymentHistoryList(Customer $customer): JsonResponse
    {
        try {
            // التحقق من الصلاحيات
            if (
                !auth()->user()->can('customers.view') &&
                $customer->relationship_manager_id !== auth()->id()
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لعرض سجل سدادات هذا العميل'
                ], 403);
            }

            $payments = $this->paymentHistoryService->getCustomerPayments($customer->id);

            $html = view('tenants.business_center.customers.payment_history.list', [
                'payments' => $payments,
                'customer' => $customer
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'count' => $payments->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات: ' . $e->getMessage()
            ], 500);
        }
    }
}
