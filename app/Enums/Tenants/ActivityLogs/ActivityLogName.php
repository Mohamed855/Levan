<?php

declare(strict_types=1);

namespace App\Enums\Tenants\ActivityLogs;

enum ActivityLogName: string
{
    case COMPANIES = 'companies';
    case CONTRACTS = 'contracts';
    case CUSTOMERS = 'customers';
    case CUSTOMER_FOLLOW = 'customer_follow';
    case CUSTOMER_PAYMENT_PLAN = 'customer_payment_plan';
    case CUSTOMER_PHONE = 'customer_phone';
    case CUSTOMER_SETTLEMENT = 'customer_settlement';
    case USERS = 'users';
    case PORTFOLIOS = 'portfolios';
    case COLLECTIONS = 'collections';
    case AUTH = 'auth';

    public function label(): string
    {
        return match ($this) {
            self::COMPANIES => 'الشركات',
            self::CONTRACTS => 'العقود',
            self::CUSTOMERS => 'العملاء',
            self::CUSTOMER_FOLLOW => 'المتابعات',
            self::CUSTOMER_PAYMENT_PLAN => 'خطط السداد',
            self::CUSTOMER_PHONE => 'أرقام الاتصال',
            self::CUSTOMER_SETTLEMENT => 'طلبات التسوية',
            self::USERS => 'المستخدمين',
            self::PORTFOLIOS => 'المحافظ',
            self::COLLECTIONS => 'التحصيلات',
            self::AUTH => 'تسجيل الدخول',
        };
    }
}
