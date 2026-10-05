<?php

 
return [
    'User' => App\Models\Tenants\UserMangment\user\user::class,
    'SettingsCountry' => App\Models\Tenants\GeneralSetting\Country\SettingsCountry::class,
    'Company' => App\Models\Tenants\BusinessCenter\Company\Company::class,
    'Portfolio' => App\Models\Tenants\BusinessCenter\Portfolio\Portfolio::class,
    'Customer' => App\Models\Tenants\BusinessCenter\Customers\Customer::class,
    'MessageLog' => App\Models\Tenants\Communication\MessageLog\MessageLog::class,
    'Activity' => Spatie\Activitylog\Models\Activity::class,
    'SettingsPaymentMethod' => App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod::class,
    'SettingsCustomerStatus' => App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus::class,
    'SettingsContactMethod' => App\Models\Tenants\GeneralSetting\ContactMethod\SettingsContactMethod::class,
    'ContactResult' => App\Models\Tenants\GeneralSetting\ContactResult\ContactResult::class,
    // 'Collection' => App\Models\Tenants\FinancialCollection\Collection::class,
    'CollectionTarget' => App\Models\Tenants\FinancialCollection\Target\CollectionTarget::class,
    'SettingsDebtType' => App\Models\Tenants\GeneralSetting\DebtType\SettingsDebtType::class,
    'CustomerDistribution' => App\Models\Tenants\BusinessCenter\Customers\Distribution\CustomerDistribution::class,
    'MessageIndividualLog' => App\Models\Tenants\Communication\MessageLog\MessageIndividualLog::class,
    'Contract' =>             App\Models\Tenants\BusinessCenter\Contract\Contract::class,

    // super admin models
    'Plan' => App\Models\Admin\Plans\Plan::class,
    'AdminUser' => App\Models\Admin\AdminMangment\Users\AdminUser::class,
    'Coupon' => App\Models\Admin\Coupons\Coupon::class,
    'Tenant' =>  App\Models\Admin\Tenants\Tenant::class,
    'Subscription' => App\Models\Admin\Subscription\Subscription::class,
    'Bill' => App\Models\Admin\Bills\Bill::class,
    'Ticket' => App\Models\Admin\Tickets\Ticket::class,
];