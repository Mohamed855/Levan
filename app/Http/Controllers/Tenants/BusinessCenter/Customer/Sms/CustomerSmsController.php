<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Sms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Customer\SendCustomerSmsRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Sms\PreviewCustomerSmsRequest;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Sms\CustomerSms;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use App\Services\Tenants\BusinessCenter\Customer\Sms\CustomerSmsService;
use Illuminate\Http\JsonResponse;

class CustomerSmsController extends Controller
{
    protected CustomerSmsService $customerSmsService;

    public function __construct(CustomerSmsService $customerSmsService)
    {
        $this->customerSmsService = $customerSmsService;
        $this->middleware('can:customers.edit');
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
    public function previewTemplate(PreviewCustomerSmsRequest $request, Customer $customer): JsonResponse
    {
        try {
            $template = MessageTemplate::findOrFail($request->validated('template_id'));

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
    public function send(SendCustomerSmsRequest $request, Customer $customer): JsonResponse
    {
        try {
            $sms = $this->customerSmsService->sendSms($customer, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الرسالة بنجاح',
                'data' => $sms
            ]);
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
