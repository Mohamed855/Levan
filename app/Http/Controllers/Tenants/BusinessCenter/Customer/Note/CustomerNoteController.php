<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Note;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerNote\StoreCustomerNoteRequest;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Note\CustomerNote;
use App\Services\Tenants\BusinessCenter\Customer\Note\CustomerNoteService;
use Illuminate\Http\JsonResponse;

class CustomerNoteController extends Controller
{
    protected CustomerNoteService $customerNoteService;

    public function __construct(CustomerNoteService $customerNoteService)
    {
        $this->customerNoteService = $customerNoteService;
    }


    /*
    |--------------------------------------------------------------------------
    | جلب قائمة الملاحظات للعميل (لتحديثها عبر Ajax)
    |--------------------------------------------------------------------------
    */
    public function getNotesList(Customer $customer): JsonResponse
    {
        try {
            $customerNotes = $customer->notes()->with('createdBy:id,name')->latest()->get();

            $html = view('tenants.business_center.customers.customer_notes.list', [
                'customerNotes' => $customerNotes
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | حفظ الملاحظات
    |--------------------------------------------------------------------------
    */
    public function store(StoreCustomerNoteRequest $request, Customer $customer): JsonResponse
    {
        try {
            $notes = $this->customerNoteService->createMultipleNotes(
                $customer,
                $request->validated()['notes']
            );

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ البيانات بنجاح',
                'data' => $notes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ الملاحظات: ' . $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | حذف ملاحظة
    |--------------------------------------------------------------------------
    */
    public function destroy(Customer $customer, CustomerNote $customerNote): JsonResponse
    {
        try {
            if ($customerNote->customer_id !== $customer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بحذف هذه الملاحظة'
                ], 403);
            }

            $this->customerNoteService->deleteNote($customerNote);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الملاحظة بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الملاحظة: ' . $e->getMessage()
            ], 500);
        }
    }
}
