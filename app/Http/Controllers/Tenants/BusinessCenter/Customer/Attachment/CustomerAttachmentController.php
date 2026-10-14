<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Attachment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Attachment\StoreCustomerAttachmentRequest;
use App\Models\Tenants\BusinessCenter\Customers\Attachment\CustomerAttachment;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Services\Tenants\BusinessCenter\Customer\Attachment\CustomerAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CustomerAttachmentController extends Controller
{
    protected CustomerAttachmentService $customerAttachmentService;

    public function __construct(CustomerAttachmentService $customerAttachmentService)
    {
        $this->customerAttachmentService = $customerAttachmentService;
    }

    /*
    |--------------------------------------------------------------------------
    | جلب قائمة المرفقات للعميل (لتحديثها عبر Ajax)
    |--------------------------------------------------------------------------
    */
    public function getAttachmentsList(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        try {
            $customerAttachments = $customer->attachments()->with('createdBy:id,name')->latest()->get();

            $html = view('tenants.business_center.customers.customer_attachments.list', [
                'customerAttachments' => $customerAttachments,
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
    | حفظ المرفقات
    |--------------------------------------------------------------------------
    */
    public function store(StoreCustomerAttachmentRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        try {
            $attachments = $this->customerAttachmentService->createMultipleAttachments(
                $customer,
                $request->validated()['attachments']
            );

            return response()->json([
                'success' => true,
                'message' => 'تم رفع المرفقات بنجاح ('.count($attachments).' مرفق)',
                'data' => $attachments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفع المرفقات: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | حذف مرفق
    |--------------------------------------------------------------------------
    */
    public function destroy(Customer $customer, CustomerAttachment $customerAttachment): JsonResponse
    {
        $this->authorize('delete', $customer);

        if ($customerAttachment->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بحذف هذا المرفق',
            ], 403);
        }

        try {
            $this->customerAttachmentService->deleteAttachment($customerAttachment);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المرفق بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المرفق: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تحميل المرفق
    |--------------------------------------------------------------------------
    */
    public function download(Customer $customer, CustomerAttachment $customerAttachment)
    {
        $this->authorize('view', $customer);

        try {
            // التحقق من أن المرفق يخص هذا العميل
            if ($customerAttachment->customer_id !== $customer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتحميل هذا المرفق',
                ], 403);
            }

            // طرق متعددة للتحقق من وجود الملف
            $filePath = $customerAttachment->file_path;

            // 1. التحقق الأساسي
            if (! Storage::disk('tenant_public')->exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'الملف غير موجود في التخزين',
                ], 404);
            }

            // 2. الحصول على المسار الكامل والتحقق من وجوده فعلياً
            $fullPath = Storage::disk('tenant_public')->path($filePath);

            if (! file_exists($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'الملف غير موجود على الخادم',
                ], 404);
            }

            // 3. تحديد اسم الملف للتحميل
            $originalName = basename($customerAttachment->file_path);

            // إذا كان هناك وصف، استخدمه كاسم للملف
            if ($customerAttachment->description) {
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                // تنظيف الوصف بطريقة بسيطة
                $cleanDescription = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $customerAttachment->description);
                $cleanDescription = trim($cleanDescription);

                if (! empty($cleanDescription)) {
                    $downloadName = $cleanDescription.'.'.$extension;
                } else {
                    $downloadName = $originalName;
                }
            } else {
                $downloadName = $originalName;
            }

            // 4. تحميل الملف
            return Storage::disk('tenant_public')->download($filePath, $downloadName);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل الملف: '.$e->getMessage(),
            ], 500);
        }
    }
}
