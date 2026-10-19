<?php

declare(strict_types=1);

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Models\Admin\Bank\Bank;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\ControlDomain\ControlDomain;
use App\Models\Admin\Faq;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Tickets\Ticket;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\Team\Team;
use App\Models\Tenants\UserMangment\User\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

return [
    'User' => User::class,
    'Company' => Company::class,
    'Assignment' => Assignment::class,
    'Property' => Property::class,
    'Auction' => Auction::class,
    'Team' => Team::class,
    'Role' => Role::class,
    'Activity' => Activity::class,

    // Super Admin models
    'Plan' => Plan::class,
    'AdminUser' => AdminUser::class,
    'Coupon' => Coupon::class,
    'Tenant' => Tenant::class,
    'Client' => \App\Models\Admin\Client::class,
    'Subscription' => Subscription::class,
    'Bill' => Bill::class,
    'Ticket' => Ticket::class,
    'AuctionTemplate' => AuctionTemplate::class,
    'Bank' => Bank::class,
    'ControlDomain' => ControlDomain::class,
    'Faq' => Faq::class,
];
