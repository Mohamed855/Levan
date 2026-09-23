<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Sms;

use App\Http\Controllers\Controller;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Sms\CustomerSms;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use App\Services\Tenants\BusinessCenter\Customer\Sms\CustomerSmsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerSmsController extends Controller
{
    protected CustomerSmsService $customerSmsService;

    public function __construct(CustomerSmsService $customerSmsService)
    {
        $this->customerSmsService = $customerSmsService;
    }

    /**
     * عرض نموذج إرسال الرسالة
     */
    public function create(Customer $customer): JsonResponse
    {
        try {
            $phoneNumbers = $this->customerSmsService->getCustomerPhoneNumbers($customer);
            $templates = $this->customerSmsService->getAvailableTemplates();

            if (empty($phoneNumbers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد أرقام هواتف مسجلة لهذا العميل'
                ], 400);
            }

            $html = view('tenants.business_center.customers.customer_sms.create', [
                'customer' => $customer,
                'phoneNumbers' => $phoneNumbers,
                'templates' => $templates
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'message' => 'تم تحميل النموذج بنجاح'
            ]);
        } catch (\Exception $e) {
            \Log::error('خطأ في تحميل نموذج إرسال الرسالة: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل النموذج: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * معاينة القالب مع بيانات العميل
     */
    public function previewTemplate(Request $request, Customer $customer): JsonResponse
    {
        try {
            $templateId = $request->input('template_id');

            if (!$templateId) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب اختيار قالب أولاً'
                ], 400);
            }

            $template = MessageTemplate::find($templateId);

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'القالب غير موجود'
                ], 404);
            }

            $previewContent = $this->customerSmsService->previewTemplate($template, $customer);

            return response()->json([
                'success' => true,
                'preview_content' => $previewContent,
                'template_name' => $template->name
            ]);
        } catch (\Exception $e) {
            \Log::error('خطأ في معاينة القالب: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معاينة القالب: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال الرسالة النصية
     */
    public function send(Request $request, Customer $customer): JsonResponse
    {
        try {
            // التحقق من صحة البيانات
            $validated = $request->validate([
                'phone_number' => 'required|string',
                'phone_type' => 'required|string',
                'template_id' => 'required|exists:message_templates,id',
            ], [
                'phone_number.required' => 'رقم الهاتف مطلوب',
                'phone_type.required' => 'نوع الهاتف مطلوب',
                'template_id.required' => 'القالب مطلوب',
                'template_id.exists' => 'القالب المحدد غير موجود',
            ]);

            $sms = $this->customerSmsService->sendSms($customer, $validated);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الرسالة بنجاح',
                'data' => $sms
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('خطأ في إرسال الرسالة: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الرسالة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف رسالة
     */
    public function destroy(Customer $customer, CustomerSms $customerSms): JsonResponse
    {
        try {
            // التحقق من أن الرسالة تخص هذا العميل
            if ($customerSms->customer_id !== $customer->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بحذف هذه الرسالة'
                ], 403);
            }

            $this->customerSmsService->deleteSms($customerSms);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الرسالة بنجاح'
            ]);
        } catch (\Exception $e) {
            \Log::error('خطأ في حذف الرسالة: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الرسالة: ' . $e->getMessage()
            ], 500);
        }
    }
}
