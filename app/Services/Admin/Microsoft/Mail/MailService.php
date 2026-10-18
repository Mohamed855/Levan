<?php

namespace App\Services\Admin\Microsoft\Mail;

use App\Models\Admin\Settings\Settings;

class MailService
{
    private string $office_name;

    public function __construct(private MicrosoftMailService $mailService)
    {
        $this->office_name = config('app.name', 'مسند');
    }

    // New subscription email
    public function NewSubscribtionMail($email, $subscription): void
    {
        if (! $email) {
            return;
        }

        $previewText = "تم الاشتراك في  {$subscription->plan->name} بنجاح";
        $html = view(
            'emails.new_subscription',
            $this->officeData(),
            [
                'preview_text' => $previewText,
                'office_name' => $this->office_name,
                'customer_name' => $subscription->tenant->company_name,
                'operation_type' => " الاشتراك في نظام مسند في    {$subscription->plan->name}  ",
                'plan_name' => $subscription->plan->name,
                'next_payment_date' => $subscription->next_payment_date,
                'url' => 'https://levan.com/'.$subscription->tenant_id.'/login',
            ]
        )->render();

        $this->mailService->sendEmail($email, "تم الاشتراك في  {$subscription->plan->name} ", $html);
    }

    public function RenewSubscriptionMail($email, $subscription): void
    {
        if (! $email) {
            return;
        }

        $previewText = "تم تجديد الاشتراك في  {$subscription->plan->name} بنجاح";
        $html = view(
            'emails.renew_subscription',
            $this->officeData(),
            [
                'preview_text' => $previewText,
                'office_name' => $this->office_name,
                'customer_name' => $subscription->tenant->company_name,
                'operation_type' => " تجديد الاشتراك في نظام مسند في    {$subscription->plan->name}  ",
                'plan_name' => $subscription->plan->name,
                'next_payment_date' => $subscription->next_payment_date,
                'url' => 'https://levan.com/'.$subscription->tenant_id.'/login',
            ]
        )->render();

        $this->mailService->sendEmail($email, "تم تجديد الاشتراك في  {$subscription->plan->name} ", $html);
    }

    public function UpgradeSubscriptionMail($email, $subscription): void
    {
        if (! $email) {
            return;
        }

        $previewText = "تم شراء التراخيص الجديدة للاشتراك الخاص بكم رقم {$subscription->subscription_number}   بنجاح";
        $html = view(
            'emails.upgrade_subscription',
            $this->officeData(),
            [
                'preview_text' => $previewText,
                'office_name' => $this->office_name,
                'customer_name' => $subscription->tenant->company_name,
                'operation_type' => "  شراء تراخيص إضافية للاشتراك رقم {$subscription->subscription_number}  ",
                'plan_name' => $subscription->plan->name,
                'total_users' => $subscription->total_users,
                'url' => 'https://levan.com/'.$subscription->tenant_id.'/login',
            ]
        )->render();

        $this->mailService->sendEmail($email, "تم شراء التراخيص الاضافية  للاشتراك رقم {$subscription->subscription_number} ", $html);
    }

    public function NewClientMail($email, $subscription): void
    {
        if (! $email) {
            return;
        }

        $previewText = 'تم اضافتك كعميل جديد في نظام مسند';
        $html = view(
            'emails.new_client',
            $this->officeData(),
            [
                'preview_text' => $previewText,
                'office_name' => $this->office_name,
                'customer_name' => $subscription['company_name'],
                'operation_type' => '  إضافة عميل جديد في نظام مسند  ',
                // 'plan_name'             => $subscription['plan']['name'],
                // 'total_users'           => $subscription['total_users'],
                'url' => 'https://levan.com',
            ]
        )->render();

        $this->mailService->sendEmail($email, 'تم اضافتك كعميل جديد في نظام مسند', $html);
    }

    // Trial subscription email
    public function TrialRequestMail($email, $name): void
    {
        if (! $email) {
            return;
        }

        $previewText = 'طلب تجربة مجانية (Demo)';
        $html = view(
            'emails.trial_request',
            $this->officeData(),
            [
                'preview_text' => $previewText,
                'office_name' => $this->office_name,
                'customer_name' => $name,
                'request_date' => now()->toDateString(),
                'url' => 'https://levan.com/',
            ]
        )->render();

        $this->mailService->sendEmail($email, $previewText, $html);
    }

    public function ContactRequestMail($email, $name): void
    {
        if (! $email) {
            return;
        }

        $previewText = 'الدعم الفني لنظام مسند';
        $html = view(
            'emails.contact_request',
            $this->officeData(),
            [
                'preview_text' => $previewText,
                'office_name' => $this->office_name,
                'customer_name' => $name,
                'request_date' => now()->toDateString(),
                'url' => 'https://levan.com/',
            ]
        )->render();

        $this->mailService->sendEmail($email, $previewText, $html);
    }

    public function sendContactReplyMail(string $email, string $name, string $message, string $reply): void
    {
        $html = view('emails.contact_reply', [
            'office_name' => $this->office_name,
            'customer_name' => $name,
            'message' => $message,
            'reply' => $reply,
        ])->render();

        $this->mailService->sendEmail($email, 'رد على رسالتك', $html);
    }

    /*
    |============================================================================
    |============================================================================
    |                         Private methods
    |============================================================================
    |============================================================================
    */
    private function officeData()
    {
        $setting = Settings::first();

        return [
            'office_name' => $setting->office_name ?? config('app.name'),
            'office_address' => $setting->office_address ?? '',
            'commercial_register' => $setting->commercial_register ?? '',
            'tax_number' => $setting->tax_number ?? '',
            'phone_number' => $setting->phone_number ?? '',
            'email' => $setting->email ?? '',
        ];
    }
}
