<?php

namespace App\Console;

use App\Jobs\Admin\ProcessSubscriptionPeriods;
use App\Jobs\Tenants\TransferDomain\TransferDomainJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /*
    |--------------------------------------------------------------------------
    | Define the application's command schedule.
    |--------------------------------------------------------------------------
    */
    protected function schedule(Schedule $schedule): void
    {
        // for delete File Report From Storage
        $schedule->command('reports:clean')->dailyAt('12:00');
        $schedule->command('checkout:delete-abandoned-bills')->everyMinute()->withoutOverlapping();

        // for clean open session import
        // $schedule->command('import:cleanup-sessions')->dailyAt('12:00');

        // اغلاق فترات العمل الغير نشطة بعد 10 دقائق من آخر نشاط

        // For Export Monthly Activity Logs
        $schedule->command('activity-logs:export-monthly')->monthlyOn(1, '00:01');

        // For Delete Old Activity Logs
        $schedule->command('activity-logs:delete-old')->dailyAt('02:00');

        // تحديث الاشتراكات
        $schedule->job(new ProcessSubscriptionPeriods)->dailyAt('00:00')->timezone('Asia/Riyadh')->withoutOverlapping();

        // $schedule->job(new ProcessSubscriptionPeriods);
        // ============ Message Logs ============
        // تصدير وحذف كل 6 أشهر - يناير ويوليو (أول يوم الساعة 01:00)
        $schedule->command('message-logs:export-biannual')->cron('0 1 1 1,7 *');

        // حذف ملفات Message القديمة (+12 شهر) - يومياً الساعة 03:00
        $schedule->command('message-logs:delete-old')->dailyAt('03:00');

        // ============ SMS Credit Top-Up ============

        $schedule->job(new TransferDomainJob)
            ->hourly()
            ->withoutOverlapping();
    }

    /*
    |--------------------------------------------------------------------------
    | Register the commands for the application.
    |--------------------------------------------------------------------------
    */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
