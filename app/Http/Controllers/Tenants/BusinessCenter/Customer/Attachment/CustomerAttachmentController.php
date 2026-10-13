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

    /*
    |--------------------------------------------------------------------------
    | دالة تشخيص لفحص الملفات (للتطوير فقط)
    |--------------------------------------------------------------------------
    */
    public function diagnose(Customer $customer, CustomerAttachment $customerAttachment)
    {
        $filePath = $customerAttachment->file_path;
        $fullPath = Storage::disk('tenant_public')->path($filePath);

        return response()->json([
            'attachment_id' => $customerAttachment->id,
            'file_path' => $filePath,
            'full_path' => $fullPath,
            'storage_exists' => Storage::disk('tenant_public')->exists($filePath),
            'file_exists' => file_exists($fullPath),
            'storage_url' => Storage::disk('tenant_public')->url($filePath),
            'file_size_db' => $customerAttachment->file_size,
            'file_size_actual' => file_exists($fullPath) ? filesize($fullPath) : 'غير موجود',
            'storage_path' => storage_path('app/public'),
            'public_path' => public_path('storage'),
            'symlink_exists' => is_link(public_path('storage')),
            'description' => $customerAttachment->description, // إضافة الوصف للتشخيص
            'all_attributes' => $customerAttachment->toArray(), // جميع البيانات
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | اختبار إنشاء مرفق يدوياً للتشخيص
    |--------------------------------------------------------------------------
    */
    public function testCreate(Customer $customer)
    {
        try {
            $attachment = CustomerAttachment::create([
                'customer_id' => $customer->id,
                'created_by' => auth()->id(),
                'description' => 'اختبار الوصف',
                'file_path' => 'test/test.pdf',
                'file_type' => 'pdf',
                'file_size' => 1024,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء مرفق اختبار',
                'data' => $attachment->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في إنشاء مرفق اختبار: '.$e->getMessage(),
            ]);
        }
    }
}
