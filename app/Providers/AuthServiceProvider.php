<?php

namespace App\Providers;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bank\Bank;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\ContentManagement\Client;
use App\Models\Admin\ContentManagement\Portfolio as ContentPortfolio;
use App\Models\Admin\ControlDomain\ControlDomain;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Tickets\Ticket;
use App\Models\Admin\PublicRequests\ContactRequest;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Version\Version;
use App\Models\Admin\Version\VersionTester;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Contract\Contract;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Letter\CompanyLetter;
use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Models\Tenants\BusinessCenter\Customers\Distribution\CustomerDistribution;
use App\Models\Tenants\BusinessCenter\Customers\UpdateRequest\CustomerUpdateRequest;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\Communication\MessageLog\MessageIndividualLog;
use App\Models\Tenants\Communication\MessageLog\MessageLog;
use App\Models\Tenants\GeneralSetting\ContactMethod\SettingsContactMethod;
use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use App\Models\Tenants\GeneralSetting\Country\SettingsCountry;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use App\Models\Tenants\GeneralSetting\DebtType\SettingsDebtType;
use App\Models\Tenants\GeneralSetting\DocumentRequest\SettingsDocumentRequestReason;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\Team\Team;
use App\Models\Tenants\UserMangment\User\User;
use App\Policies\Admin\Bills\BillPolicy;
use App\Policies\Admin\ContentManagement\ClientPolicy;
use App\Policies\Admin\ContentManagement\ContentPortfolioPolicy;
use App\Policies\Admin\Coupons\CouponPolicy;
use App\Policies\Admin\Plans\PlanPolicy;
use App\Policies\Admin\Settings\BankPolicy;
use App\Policies\Admin\Settings\ControlDomainPolicy;
use App\Policies\Admin\Subscriptions\SubscriptionPolicy;
use App\Policies\Admin\Tenants\TenantPolicy;
use App\Policies\Admin\Tickets\TicketPolicy;
use App\Policies\Admin\PublicRequests\ContactRequestPolicy;
use App\Policies\Admin\PublicRequests\TrialRequestPolicy;
use App\Policies\Admin\Users\AdminUserPolicy;
use App\Policies\Admin\Versions\VersionPolicy;
use App\Policies\Admin\Versions\VersionTesterPolicy;
use App\Policies\Shared\RolePolicy;
use App\Policies\Tenants\ActivityLog\ActivityLogPolicy;
use App\Policies\Tenants\Assignments\AssignmentPolicy;
use App\Policies\Tenants\Auctions\AuctionPolicy;
use App\Policies\Tenants\BusinessCenter\Company\CompanyPolicy;
use App\Policies\Tenants\BusinessCenter\Contract\ContractPolicy;
use App\Policies\Tenants\BusinessCenter\Customer\CustomerPolicy;
use App\Policies\Tenants\BusinessCenter\Customer\CompanyLetterPolicy;
use App\Policies\Tenants\BusinessCenter\Customer\CustomerUpdateRequestPolicy;
use App\Policies\Tenants\BusinessCenter\Portfolio\PortfolioPolicy;
use App\Policies\Tenants\BusinessCenter\Task\TaskPolicy;
use App\Policies\Tenants\Communication\MessageLog\MessageIndividualLogPolicy;
use App\Policies\Tenants\Communication\MessageLog\MessageLogPolicy;
use App\Policies\Tenants\GeneralSetting\ContactMethodPolicy;
use App\Policies\Tenants\GeneralSetting\ContactResultPolicy;
use App\Policies\Tenants\GeneralSetting\CountryPolicy;
use App\Policies\Tenants\GeneralSetting\CustomerDistributionPolicy;
use App\Policies\Tenants\GeneralSetting\CustomerStatusPolicy;
use App\Policies\Tenants\GeneralSetting\DebtTypePolicy;
use App\Policies\Tenants\GeneralSetting\DocumentRequestReasonPolicy;
use App\Policies\Tenants\GeneralSetting\MessageTemplatePolicy;
use App\Policies\Tenants\GeneralSetting\PaymentMethodPolicy;
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
        Bill::class => BillPolicy::class,
        Subscription::class => SubscriptionPolicy::class,
        Ticket::class => TicketPolicy::class,
        ContactRequest::class => ContactRequestPolicy::class,
        TrialRequest::class => TrialRequestPolicy::class,
        Assignment::class => AssignmentPolicy::class,
        Property::class => PropertyPolicy::class,
        Auction::class => AuctionPolicy::class,
        Company::class => CompanyPolicy::class,
        Contract::class => ContractPolicy::class,
        Portfolio::class => PortfolioPolicy::class,
        Customer::class => CustomerPolicy::class,
        CompanyLetter::class => CompanyLetterPolicy::class,
        Task::class => TaskPolicy::class,
        CustomerUpdateRequest::class => CustomerUpdateRequestPolicy::class,
        Team::class => TeamPolicy::class,
        User::class => UserPolicy::class,
        MessageLog::class => MessageLogPolicy::class,
        MessageIndividualLog::class => MessageIndividualLogPolicy::class,
        SettingsCountry::class => CountryPolicy::class,
        SettingsPaymentMethod::class => PaymentMethodPolicy::class,
        SettingsCustomerStatus::class => CustomerStatusPolicy::class,
        SettingsContactMethod::class => ContactMethodPolicy::class,
        ContactResult::class => ContactResultPolicy::class,
        SettingsDebtType::class => DebtTypePolicy::class,
        CustomerDistribution::class => CustomerDistributionPolicy::class,
        SettingsDocumentRequestReason::class => DocumentRequestReasonPolicy::class,
        MessageTemplate::class => MessageTemplatePolicy::class,
        Activity::class => ActivityLogPolicy::class,
        Role::class => RolePolicy::class,
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
