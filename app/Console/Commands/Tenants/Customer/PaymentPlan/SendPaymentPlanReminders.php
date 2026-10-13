<?php

namespace App\Console\Commands\Tenants\Customer\PaymentPlan;

use App\Services\Tenants\SMS\Customer\PaymentPlan\PaymentPlanReminderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPaymentPlanReminders extends Command
{
    /*
    |--------------------------------------------------------------------------
    | Signature
    |--------------------------------------------------------------------------
    */
    protected $signature = 'payment-plan:reminders {date? : تاريخ الاستحقاق بصيغة Y-m-d}';

    /*
    |--------------------------------------------------------------------------
    | Description
    |--------------------------------------------------------------------------
    */
    protected $description = 'إرسال تذكيرات SMS لخطط السداد المستحقة في تاريخ محدد (الافتراضي: اليوم)';

    /*
    |--------------------------------------------------------------------------
    | Dependencies
    |--------------------------------------------------------------------------
    */
    protected PaymentPlanReminderService $reminderService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(PaymentPlanReminderService $reminderService)
    {
        parent::__construct();
        $this->reminderService = $reminderService;
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Command
    |--------------------------------------------------------------------------
    */
    public function handle(): int
    {
        $dateInput = $this->argument('date');

        try {
            $date = $dateInput
                ? Carbon::parse($dateInput)->toDateString()
                : now()->toDateString();
        } catch (\Throwable $e) {
            $this->error('تنسيق التاريخ غير صحيح. استخدم الصيغة: Y-m-d مثل 2025-11-22');

            return self::FAILURE;
        }

        $this->info("بدء إرسال تذكيرات خطط السداد لتاريخ: {$date} ...");

        $result = $this->reminderService->sendDueRemindersForDate($date);

        if (! $result['success']) {
            $this->warn($result['message'] ?? 'لم يتم الإرسال.');

            return self::FAILURE;
        }

        $this->line('----------------------------------------');
        $this->info('ملخص العملية:');
        $this->info("التاريخ: {$result['date']}");
        $this->info("تم الإرسال لعدد: {$result['sent_count']} خطة");
        $this->info("تم تجاهل لعدد: {$result['skipped_count']} خطة");
        $this->line('----------------------------------------');

        $this->info('انتهت عملية إرسال التذكيرات لخطط السداد بنجاح.');

        return self::SUCCESS;
    }
}
