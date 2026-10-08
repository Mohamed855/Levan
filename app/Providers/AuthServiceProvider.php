<?php

namespace App\Providers;

use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Bank\Bank;
use App\Models\Admin\ControlDomain\ControlDomain;
use App\Models\Admin\ContentManagement\Client;
use App\Models\Admin\ContentManagement\Portfolio as ContentPortfolio;
use App\Models\Admin\Version\Version;
use App\Models\Admin\Version\VersionTester;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Contract\Contract;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\FinancialCollection\Target\CollectionTarget;
use App\Models\Tenants\FinancialCollection\Reconciliation\PaymentReconciliation;
use App\Models\Tenants\Communication\MessageLog\MessageLog;
use App\Models\Tenants\Communication\MessageLog\MessageIndividualLog;
use App\Models\Tenants\UserMangment\Team\Team;
use App\Models\Tenants\UserMangment\User\User;
use App\Policies\Admin\Plans\PlanPolicy;
use App\Policies\Admin\Tenants\TenantPolicy;
use App\Policies\Admin\Coupons\CouponPolicy;
use App\Policies\Admin\Users\AdminUserPolicy;
use App\Policies\Admin\Settings\BankPolicy;
use App\Policies\Admin\Settings\ControlDomainPolicy;
use App\Policies\Admin\ContentManagement\ClientPolicy;
use App\Policies\Admin\ContentManagement\ContentPortfolioPolicy;
use App\Policies\Admin\Versions\VersionPolicy;
use App\Policies\Admin\Versions\VersionTesterPolicy;
use App\Policies\Tenants\Assignments\AssignmentPolicy;
use App\Policies\Tenants\Auctions\AuctionPolicy;
use App\Policies\Tenants\BusinessCenter\Company\CompanyPolicy;
use App\Policies\Tenants\BusinessCenter\Contract\ContractPolicy;
use App\Policies\Tenants\BusinessCenter\Customer\CustomerPolicy;
use App\Policies\Tenants\BusinessCenter\Portfolio\PortfolioPolicy;
use App\Policies\Tenants\Properties\PropertyPolicy;
use App\Policies\Tenants\FinancialCollection\Collection\CollectionPolicy;
use App\Policies\Tenants\FinancialCollection\Target\CollectionTargetPolicy;
use App\Policies\Tenants\FinancialCollection\Reconciliation\PaymentReconciliationPolicy;
use App\Policies\Tenants\Communication\MessageLog\MessageLogPolicy;
use App\Policies\Tenants\Communication\MessageLog\MessageIndividualLogPolicy;
use App\Policies\Tenants\UserManagement\Team\TeamPolicy;
use App\Policies\Tenants\UserManagement\User\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Plan::class => PlanPolicy::class,
        Tenant::class => TenantPolicy::class,
        AdminUser::class => AdminUserPolicy::class,
        Coupon::class => CouponPolicy::class,
        Bank::class => BankPolicy::class,
        ControlDomain::class => ControlDomainPolicy::class,
        Client::class => ClientPolicy::class,
        ContentPortfolio::class => ContentPortfolioPolicy::class,
        Version::class => VersionPolicy::class,
        VersionTester::class => VersionTesterPolicy::class,
        Assignment::class => AssignmentPolicy::class,
        Property::class => PropertyPolicy::class,
        Auction::class => AuctionPolicy::class,
        Company::class => CompanyPolicy::class,
        Contract::class => ContractPolicy::class,
        Portfolio::class => PortfolioPolicy::class,
        Customer::class => CustomerPolicy::class,
        Team::class => TeamPolicy::class,
        User::class => UserPolicy::class,
        Collection::class => CollectionPolicy::class,
        CollectionTarget::class => CollectionTargetPolicy::class,
        PaymentReconciliation::class => PaymentReconciliationPolicy::class,
        MessageLog::class => MessageLogPolicy::class,
        MessageIndividualLog::class => MessageIndividualLogPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
