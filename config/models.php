<?php

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Tickets\Ticket;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Contract\Contract;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Distribution\CustomerDistribution;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\Communication\MessageLog\MessageIndividualLog;
use App\Models\Tenants\Communication\MessageLog\MessageLog;
use App\Models\Tenants\GeneralSetting\ContactMethod\SettingsContactMethod;
use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use App\Models\Tenants\GeneralSetting\Country\SettingsCountry;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use App\Models\Tenants\GeneralSetting\DebtType\SettingsDebtType;
use App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use Spatie\Activitylog\Models\Activity;

return [
    'User' => User::class,
    'SettingsCountry' => SettingsCountry::class,
    'Company' => Company::class,
    'Portfolio' => Portfolio::class,
    'Customer' => Customer::class,
    'MessageLog' => MessageLog::class,
    'Activity' => Activity::class,
    'SettingsPaymentMethod' => SettingsPaymentMethod::class,
    'SettingsCustomerStatus' => SettingsCustomerStatus::class,
    'SettingsContactMethod' => SettingsContactMethod::class,
    'ContactResult' => ContactResult::class,
    'SettingsDebtType' => SettingsDebtType::class,
    'CustomerDistribution' => CustomerDistribution::class,
    'MessageIndividualLog' => MessageIndividualLog::class,
    'Contract' => Contract::class,
    'Assignment' => Assignment::class,
    'Property' => Property::class,
    'Auction' => Auction::class,

    // super admin models
    'Plan' => Plan::class,
    'AdminUser' => AdminUser::class,
    'Coupon' => Coupon::class,
    'Tenant' => Tenant::class,
    'Subscription' => Subscription::class,
    'Bill' => Bill::class,
    'Ticket' => Ticket::class,
];
