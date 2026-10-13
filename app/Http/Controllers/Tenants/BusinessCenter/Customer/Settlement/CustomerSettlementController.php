<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Settlement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerSettlement\StoreCustomerSettlementRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerSettlement\UpdateCustomerSettlementRequest;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Settlement\CustomerSettlement;
use App\Services\Tenants\BusinessCenter\Customer\Settlement\CustomerSettlementService;
use Illuminate\Http\JsonResponse;

class CustomerSettlementController extends Controller
{
    protected CustomerSettlementService $customerSettlementService;

    public function __construct(CustomerSettlementService $customerSettlementService)
    {
        $this->customerSettlementService = $customerSettlementService;
    }

    /**
     * جلب قائمة التسويات للعميل (لتحديثها عبر Ajax)
     */
    public function getSettlementsList(Customer $customer): JsonResponse
    {
        try {
            $customerSettlements = $customer->settlements()->with('createdBy:id,name')->latest()->get();

            $html = view('tenants.business_center.customers.customer_settlements.list', [
                'customerSettlements' => $customerSettlements,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            \Log::error('خطأ في جلب قائمة التسويات: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
            ], 500);
        }
    }

    /**
     * عرض نموذج الإضافة
     */
    public function create(Customer $customer): JsonResponse
    {
        try {
            $settlementTypes = $this->customerSettlementService->getSettlementTypes();

            $html = view('tenants.business_center.customers.customer_settlements.create', [
                'customer' => $customer,
                'settlementTypes' => $settlementTypes,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'message' => 'تم تحميل النموذج بنجاح',
            ]);
        } catch (\Exception $e) {
            \Log::error('خطأ في تحميل نموذج إضافة التسوية: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل النموذج: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * حفظ التسويات
     */
    public function store(StoreCustomerSettlementRequest $request, Customer $customer): JsonResponse
    {
        try {
            $settlements = $this->customerSettlementService->createMultipleSettlements(
                $customer,
                $request->validated()['settlements']
            );

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التسويات بنجاح ',
                'data' => $settlements,
            ]);
        } catch (\Exception $e) {
            \Log::error('خطأ في حفظ التسويات: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ التسويات: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض نموذج التعديل
     */
    public function edit(Customer $customer, CustomerSettlement $customerSettlement): JsonResponse
    {
        try {
            // التحقق من أن التسوية تخص هذا العميل
            if ($customerSettlement->customer_id !== $customer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذه التسوية',
                ], 403);
            }

            $settlementTypes = $this->customerSettlementService->getSettlementTypes();

            $html = view('tenants.business_center.customers.customer_settlements.edit', [
                'customer' => $customer,
                'settlement' => $customerSettlement,
                'settlementTypes' => $settlementTypes,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'message' => 'تم تحميل النموذج بنجاح',
            ]);
        } catch (\Exception $e) {
            \Log::error('خطأ في تحميل نموذج تعديل التسوية: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل النموذج: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحديث التسوية
     */
    public function update(UpdateCustomerSettlementRequest $request, Customer $customer, CustomerSettlement $customerSettlement): JsonResponse
    {
        try {
            if ($customerSettlement->customer_id !== $customer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذه التسوية',
                ], 403);
            }

            $updatedSettlement = $this->customerSettlementService->updateSettlement(
                $customerSettlement,
                $request->validated()
            );

            // إعادة تحميل التسوية مع جميع العلاقات للحصول على أحدث البيانات
            $updatedSettlement->load(['createdBy:id,name']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث التسوية بنجاح',
                'data' => [
                    'id' => $updatedSettlement->id,
                    'settlement_date_formatted' => $updatedSettlement->settlement_date_formatted,
                    'settlement_amount_formatted' => $updatedSettlement->settlement_amount_formatted,
                    'settlement_number' => $updatedSettlement->settlement_number,
                    'settlement_type_text' => $updatedSettlement->settlement_type_text,
                    'has_file' => ! empty($updatedSettlement->file_path),
                    'file_url' => ! empty($updatedSettlement->file_path) ? asset('storage/'.$updatedSettlement->file_path) : null,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('خطأ في تحديث التسوية: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث التسوية: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف تسوية
     */
    public function destroy(Customer $customer, CustomerSettlement $customerSettlement): JsonResponse
    {
        try {
            // التحقق من أن التسوية تخص هذا العميل
            if ($customerSettlement->customer_id !== $customer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بحذف هذه التسوية',
                ], 403);
            }

            $this->customerSettlementService->deleteSettlement($customerSettlement);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف التسوية بنجاح',
            ]);
        } catch (\Exception $e) {
            \Log::error('خطأ في حذف التسوية: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف التسوية: '.$e->getMessage(),
            ], 500);
        }
    }
}
