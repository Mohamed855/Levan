<?php

namespace App\Providers;

use App\Services\Tenants\Communication\MessageLog\MessageLogService;
use App\Services\Tenants\SMS\Base\BaseSMSService;
use App\Services\Tenants\SMS\Customer\CustomerSMSService;
use Illuminate\Support\ServiceProvider;

/**
 * مقدم خدمة الرسائل النصية
 * يتولى ربط جميع خدمات الرسائل النصية
 */
class SMSServiceProvider extends ServiceProvider
{
    /**
     * تسجيل الخدمات
     */
    public function register()
    {
        // تسجيل الخدمة الأساسية
        $this->app->singleton(BaseSMSService::class, function ($app) {
            return new BaseSMSService;
        });

        // تسجيل خدمة رسائل العملاء
        $this->app->singleton(CustomerSMSService::class, function ($app) {
            return new CustomerSMSService(
                $app->make(BaseSMSService::class),
                $app->make(MessageLogService::class)
            );
        });
    }

    /**
     * تشغيل الخدمات
     */
    public function boot()
    {
        //
    }

    /**
     * الحصول على الخدمات المقدمة
     */
    public function provides()
    {
        return [
            BaseSMSService::class,
            CustomerSMSService::class,
        ];
    }
}
