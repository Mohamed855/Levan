<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Models\Admin\Bank\Bank;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\ControlDomain\ControlDomain;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\PublicRequests\ContactRequest;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Tickets\Ticket;
use App\Models\Admin\Version\Version;
use App\Models\Admin\Version\VersionTester;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\Team\Team;
use App\Models\Tenants\UserMangment\User\User;
use App\Policies\Admin\AuctionTemplates\AuctionTemplatePolicy;
use App\Policies\Admin\Bills\BillPolicy;
use App\Policies\Admin\Coupons\CouponPolicy;
use App\Policies\Admin\Plans\PlanPolicy;
use App\Policies\Admin\PublicRequests\ContactRequestPolicy;
use App\Policies\Admin\PublicRequests\TrialRequestPolicy;
use App\Policies\Admin\Settings\BankPolicy;
use App\Policies\Admin\Settings\ControlDomainPolicy;
use App\Policies\Admin\Subscriptions\SubscriptionPolicy;
use App\Policies\Admin\Tenants\TenantPolicy;
use App\Policies\Admin\Tickets\TicketPolicy;
use App\Policies\Admin\Users\AdminUserPolicy;
use App\Policies\Admin\Versions\VersionPolicy;
use App\Policies\Admin\Versions\VersionTesterPolicy;
use App\Policies\Shared\RolePolicy;
use App\Policies\Tenants\ActivityLog\ActivityLogPolicy;
use App\Policies\Tenants\Assignments\AssignmentPolicy;
use App\Policies\Tenants\Auctions\AuctionPolicy;
use App\Policies\Tenants\BusinessCenter\Company\CompanyPolicy;
use App\Policies\Tenants\Properties\PropertyPolicy;
use App\Policies\Tenants\UserManagement\Team\TeamPolicy;
use App\Policies\Tenants\UserManagement\User\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AuctionTemplate::class => AuctionTemplatePolicy::class,
        Plan::class => PlanPolicy::class,
        Tenant::class => TenantPolicy::class,
        AdminUser::class => AdminUserPolicy::class,
        Coupon::class => CouponPolicy::class,
        Bank::class => BankPolicy::class,
        ControlDomain::class => ControlDomainPolicy::class,
        Version::class => VersionPolicy::class,
        VersionTester::class => VersionTesterPolicy::class,
        Bill::class => BillPolicy::class,
        Subscription::class => SubscriptionPolicy::class,
        Ticket::class => TicketPolicy::class,
        ContactRequest::class => ContactRequestPolicy::class,
        TrialRequest::class => TrialRequestPolicy::class,
        Assignment::class => AssignmentPolicy::class,
        Property::class => PropertyPolicy::class,
        Auction::class => AuctionPolicy::class,
        Company::class => CompanyPolicy::class,
        Team::class => TeamPolicy::class,
        User::class => UserPolicy::class,
        Activity::class => ActivityLogPolicy::class,
        Role::class => RolePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
