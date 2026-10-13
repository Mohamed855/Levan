<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\PaymentPlan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerPaymentPlan\StoreCustomerPaymentPlanRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerPaymentPlan\UpdateCustomerPaymentPlanRequest;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\PaymentPlan\CustomerPaymentPlan;
use App\Services\Tenants\BusinessCenter\Customer\PaymentPlan\CustomerPaymentPlanService;
use Illuminate\Http\JsonResponse;

class CustomerPaymentPlanController extends Controller
{
    protected CustomerPaymentPlanService $customerPaymentPlanService;

    public function __construct(CustomerPaymentPlanService $customerPaymentPlanService)
    {
        $this->customerPaymentPlanService = $customerPaymentPlanService;
    }

    /*
    |--------------------------------------------------------------------------
    | جلب قائمة خطط السداد للعميل (لتحديثها عبر Ajax)
    |--------------------------------------------------------------------------
    */
    public function getPaymentPlansList(Customer $customer): JsonResponse
    {
        try {
            $customerPaymentPlans = $customer->paymentPlans()->latest()->get();

            $html = view('tenants.business_center.customers.customer_payment_plans.list', [
                'customerPaymentPlans' => $customerPaymentPlans,
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
    | عرض نموذج الإضافة
    |--------------------------------------------------------------------------
    */
    public function create(Customer $customer): JsonResponse
    {
        try {
            $html = view('tenants.business_center.customers.customer_payment_plans.create', [
                'customer' => $customer,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'message' => 'تم تحميل النموذج بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل النموذج: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | حفظ خطط السداد
    |--------------------------------------------------------------------------
    */
    public function store(StoreCustomerPaymentPlanRequest $request, Customer $customer): JsonResponse
    {
        try {
            $plans = $this->customerPaymentPlanService->createMultiplePaymentPlans(
                $customer,
                $request->validated()['payment_plans']
            );

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ خطط السداد بنجاح ('.count($plans).' خطة)',
                'data' => $plans,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ خطط السداد: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض نموذج التعديل
     */
    public function edit(Customer $customer, CustomerPaymentPlan $customerPaymentPlan): JsonResponse
    {
        try {
            $html = view('tenants.business_center.customers.customer_payment_plans.edit', [
                'customer' => $customer,
                'paymentPlan' => $customerPaymentPlan,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'message' => 'تم تحميل النموذج بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل النموذج: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تحديث خطة السداد
    |--------------------------------------------------------------------------
    */
    public function update(UpdateCustomerPaymentPlanRequest $request, Customer $customer, CustomerPaymentPlan $customerPaymentPlan): JsonResponse
    {
        try {
            if ($customerPaymentPlan->customer_id !== $customer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذه الخطة',
                ], 403);
            }

            $updatedPlan = $this->customerPaymentPlanService->updatePaymentPlan(
                $customerPaymentPlan,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث خطة السداد بنجاح',
                'data' => $updatedPlan,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث خطة السداد: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | حذف خطة سداد
    |--------------------------------------------------------------------------
    */
    public function destroy(Customer $customer, CustomerPaymentPlan $customerPaymentPlan): JsonResponse
    {
        try {
            if ($customerPaymentPlan->customer_id !== $customer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بحذف هذه الخطة',
                ], 403);
            }

            $this->customerPaymentPlanService->deletePaymentPlan($customerPaymentPlan);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف خطة السداد بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف خطة السداد: '.$e->getMessage(),
            ], 500);
        }
    }
}
