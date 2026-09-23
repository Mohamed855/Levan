<?php

namespace App\Services\Tenants\SMS\Customer\PaymentPlan;
use App\Models\Tenants\BusinessCenter\Customers\PaymentPlan\CustomerPaymentPlan;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Services\Tenants\SMS\Base\BaseSMSService;
use Illuminate\Support\Facades\Log;

class PaymentPlanReminderService
{
    protected BaseSMSService $sms;

    public function __construct(BaseSMSService $sms)
    {
        $this->sms = $sms;
    }

    /*
    |--------------------------------------------------------------------------
    | Can Send Automatic Reminders
    |--------------------------------------------------------------------------
    */
    protected function canSendAutomaticReminders(): bool
    {
        $settings = $this->sms->getSettings();

        if (!$settings) {
            return false;
        }

        if (empty($settings->sms_enabled)) {
            return false;
        }

        if (empty($settings->sms_auto_reminders_enabled)) {
            return false;
        }

        if (!$this->sms->isReady()) {
            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Send Due Reminders For Date
    |--------------------------------------------------------------------------
    */
    public function sendDueRemindersForDate(string $date): array
    {
        if (!$this->canSendAutomaticReminders()) {
            Log::info('Payment plan auto reminders are disabled or SMS service not ready.');

            return [
                'success' => false,
                'date' => $date,
                'sent_count' => 0,
                'skipped_count' => 0,
                'message' => 'التذكيرات التلقائية غير مفعّلة أو خدمة الرسائل غير جاهزة.',
            ];
        }

        // جلب خطط السداد المستحقة في التاريخ المحدد
        $plans = CustomerPaymentPlan::with(['customer' => function ($q) {
            $q->select('id', 'name', 'phone');
        }])
            ->active()
            ->whereDate('due_date', $date)
            ->get();

        if ($plans->isEmpty()) {
            Log::info('No payment plans due at this date for reminders.', ['date' => $date]);

            return [
                'success' => true,
                'date' => $date,
                'sent_count' => 0,
                'skipped_count' => 0,
                'message' => 'لا توجد خطط سداد مستحقة في هذا التاريخ.',
            ];
        }

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($plans as $plan) {
            $customer = $plan->customer;

            if (!$customer) {
                $skippedCount++;
                continue;
            }

            if (empty($customer->phone) || !$this->sms->isValidPhoneNumber($customer->phone)) {
                Log::warning('Skipping payment plan reminder: invalid customer phone', [
                    'customer_id' => $customer->id,
                    'phone' => $customer->phone,
                ]);
                $skippedCount++;
                continue;
            }

            $formattedPhone = $this->sms->formatPhoneNumber($customer->phone);
            $message = $this->buildPaymentReminderMessage($customer, $plan);

            $success = $this->sms->send4JawalyMessage($message, [$formattedPhone]);

            Log::info('Payment plan reminder send result', [
                'customer_id' => $customer->id,
                'due_date' => $plan->due_date?->toDateString(),
                'due_amount' => $plan->due_amount,
                'phone' => $formattedPhone,
                'sent' => $success,
            ]);

            if ($success) {
                $sentCount++;
            } else {
                $skippedCount++;
            }
        }

        return [
            'success' => true,
            'date' => $date,
            'sent_count' => $sentCount,
            'skipped_count' => $skippedCount,
            'message' => "تمت محاولة إرسال تذكيرات لخطط السداد بتاريخ {$date}",
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Build Payment Reminder Message
    |--------------------------------------------------------------------------
    */
    protected function buildPaymentReminderMessage(Customer $customer, CustomerPaymentPlan $plan): string
    {
        $name = $customer->name ?: 'عميلنا الكريم';

        $amount = number_format((float) $plan->due_amount, 2);
        $dueDate = $plan->due_date?->format('Y-m-d');

        $text = "عزيزي {$name},\n";
        $text .= "نذكّرك بوجود قسط مستحق ضمن خطة السداد.\n";
        $text .= "قيمة القسط: {$amount} ريال.\n";
        $text .= "تاريخ الاستحقاق: {$dueDate}.\n";
        $text .= "نرجو السداد في الموعد المحدد. شكرًا لتعاملك معنا.";

        return $this->sms->processSystemVariables($text);
    }
}
