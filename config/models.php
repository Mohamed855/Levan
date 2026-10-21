<?php

declare(strict_types=1);

use App\Models\Access\Role;
use App\Models\Admin\AdminUser;
use App\Models\Admin\AuctionTemplate;
use App\Models\Admin\Bank;
use App\Models\Admin\Bill;
use App\Models\Admin\ControlDomain;
use App\Models\Admin\Coupon;
use App\Models\Admin\Faq;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\PublicRequests\ContactRequest;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Subscriptions\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Ticket;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\Auction;
use App\Models\Tenant\Property;
use App\Models\Tenant\User as TenantUser;
use App\Models\Admin\ActivityLog;

return [
    'User' => TenantUser::class,
    'Assignment' => Assignment::class,
    'Property' => Property::class,
    'Auction' => Auction::class,
    'Role' => Role::class,
    'Company' => \App\Models\Tenant\Company::class,
    'Activity' => ActivityLog::class,
    'ActivityLog' => ActivityLog::class,

    // Super Admin models
    'Plan' => Plan::class,
    'AdminUser' => AdminUser::class,
    'Coupon' => Coupon::class,
    'Tenant' => Tenant::class,
    'Subscription' => Subscription::class,
    'Bill' => Bill::class,
    'Ticket' => Ticket::class,
    'AuctionTemplate' => AuctionTemplate::class,
    'Bank' => Bank::class,
    'ControlDomain' => ControlDomain::class,
    'Faq' => Faq::class,
    'ContactRequest' => ContactRequest::class,
    'TrialRequest' => TrialRequest::class,
];
