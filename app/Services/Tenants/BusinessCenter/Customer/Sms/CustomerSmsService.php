<?php

namespace App\Services\Tenants\BusinessCenter\Customer\Sms;

use App\Models\Tenants\BusinessCenter\CustomerSms;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CustomerSmsService
{
    /**
     * جلب جميع أرقام الهواتف للعميل
     */
    public function getCustomerPhoneNumbers(Customer $customer): array
    {
        $phoneNumbers = [];

        // الهاتف الأساسي
        if ($customer->phone) {
            $phoneNumbers[] = [
                'phone' => $customer->phone,
                'type' => 'primary',
                'type_label' => 'الهاتف الأساسي',
                'is_primary' => true,
                'notes' => null
            ];
        }

        // الهواتف الفرعية
        $additionalPhones = $customer->phones()->get();
        foreach ($additionalPhones as $phone) {
            $phoneNumbers[] = [
                'phone' => $phone->phone,
                'type' => $phone->type,
                'type_label' => $phone->type_label,
                'is_primary' => $phone->is_primary,
                'notes' => $phone->notes
            ];
        }

        return $phoneNumbers;
    }

    /**
     * جلب قوالب الرسائل النشطة لفئة العملاء
     */
    public function getAvailableTemplates(): \Illuminate\Database\Eloquent\Collection
    {
        return MessageTemplate::active()
            ->byCategory('customers')
            ->orderBy('name')
            ->get();
    }

    /**
     * معاينة القالب مع بيانات العميل الحقيقية
     */
    public function previewTemplate(MessageTemplate $template, Customer $customer): string
    {
        $content = $template->content;

        // بيانات العميل الحقيقية
        $customerData = [
            // التواريخ العامة
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i'),
            'datetime' => now()->format('Y-m-d H:i'),
            'year' => now()->format('Y'),
            'month' => now()->format('m'),
            'day' => now()->format('d'),

            // معلومات العميل الأساسية
            'customer_name' => $customer->name ?? 'غير محدد',
            'customer_type' => $customer->customer_type ?? 'غير محدد',
            'customer_type_label' => $customer->customer_type_label ?? 'غير محدد',
            'identity_number' => $customer->identity_number ?? 'غير محدد',
            'phone' => $customer->phone ?? 'غير محدد',
            'account_number' => $customer->account_number ?? 'غير محدد',
            'service_number' => $customer->service_number ?? 'غير محدد',
            'job_number' => $customer->job_number ?? 'غير محدد',

            // المعلومات المالية
            'debt_amount' => $customer->debt_amount ?? 0,
            'formatted_debt_amount' => $customer->formatted_debt_amount ?? '0.00',
            'commission_rate' => $customer->commission_rate ?? 0,
            'formatted_commission_rate' => $customer->formatted_commission_rate ?? '0%',

            // التواريخ
            'claim_date' => $customer->claim_date?->format('Y-m-d') ?? 'غير محدد',
            'formatted_claim_date' => $customer->formatted_claim_date ?? 'غير محدد',
            'contract_date' => $customer->contract_date?->format('Y-m-d') ?? 'غير محدد',
            'formatted_contract_date' => $customer->formatted_contract_date ?? 'غير محدد',
            'default_date' => $customer->default_date?->format('Y-m-d') ?? 'غير محدد',
            'formatted_default_date' => $customer->formatted_default_date ?? 'غير محدد',

            // المعلومات الإضافية
            'claims_count' => $customer->claims_count ?? 0,
            'classification' => $customer->classification ?? 'غير محدد',
            'nationality' => $customer->nationality ?? 'غير محدد',

            // معلومات الحالة والعلاقات
            'customer_status' => $customer->customerStatus?->name ?? 'غير محدد',
            'status_label' => $customer->status_label ?? 'غير محدد',
            'relationship_manager' => $customer->relationshipManager?->name ?? 'غير محدد',

            // معلومات الشركة والمحفظة
            'company_name' => $customer->company?->name ?? 'غير محدد',
            'portfolio_name' => $customer->portfolio?->name ?? 'غير محدد',

            // معلومات المستخدم الحالي
            'current_user_name' => auth()->user()->name ?? 'المستخدم الحالي',
            'current_user_email' => auth()->user()->email ?? 'غير محدد',
        ];

        // استبدال المتغيرات
        foreach ($customerData as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }

        return $content;
    }

    /**
     * إرسال رسالة نصية
     */
    public function sendSms(Customer $customer, array $data): CustomerSms
    {
        $template = MessageTemplate::find($data['template_id']);

        // معالجة محتوى الرسالة
        $messageContent = $template ? $this->previewTemplate($template, $customer) : $data['message_content'];

        $sms = CustomerSms::create([
            'customer_id' => $customer->id,
            'message_template_id' => $template?->id,
            'phone_number' => $data['phone_number'],
            'phone_type' => $data['phone_type'],
            'template_name' => $template?->name,
            'message_content' => $messageContent,
            'status' => 'pending',
            'sent_by' => Auth::id(),
        ]);

        // هنا يمكن إضافة منطق الإرسال الفعلي للرسالة
        // مثل استخدام SMS Gateway
        try {
            // محاكاة إرسال الرسالة
            $this->sendToSmsGateway($sms);

            $sms->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            $sms->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);
        }

        return $sms;
    }

    /**
     * محاكاة إرسال للـ SMS Gateway
     */
    private function sendToSmsGateway(CustomerSms $sms): bool
    {
        // هنا يتم إضافة منطق الإرسال الحقيقي
        // مثل استخدام Twilio أو أي خدمة SMS أخرى

        // محاكاة فقط
        return true;
    }

    /**
     * جلب رسائل العميل
     */
    public function getCustomerSms(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return CustomerSms::where('customer_id', $customerId)
            ->with(['messageTemplate:id,name', 'sentBy:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * حذف رسالة (حذف ناعم)
     */
    public function deleteSms(CustomerSms $sms): bool
    {
        return $sms->delete();
    }

    /**
     * إحصائيات بسيطة للرسائل
     */
    public function getSimpleStatistics(int $customerId): array
    {
        $sms = CustomerSms::where('customer_id', $customerId);

        return [
            'total_count' => $sms->count(),
            'sent_count' => $sms->whereNull('deleted_at')->where('status', 'sent')->count(),
            'pending_count' => $sms->whereNull('deleted_at')->where('status', 'pending')->count(),
            'failed_count' => $sms->whereNull('deleted_at')->where('status', 'failed')->count(),
        ];
    }
}
