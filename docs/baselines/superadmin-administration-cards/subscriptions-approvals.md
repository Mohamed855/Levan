Administration: Central Subscriptions & Tenant Approval Workflow
Boundary: central
Scope / aggregate root: Tenant subscription records, manual bank transfer verification, plan upgrades/downgrades, subscription approvals and rejections
Routes + middleware + guard: routes/admin.php (admin.subscriptions.*, admin.subscriptionManage.*) + auth:admin + permission:subscriptions.*, subscriptions.pending.*
Controller: App\Http\Controllers\Admin\Subscriptions\SubscriptionController, App\Http\Controllers\Admin\SubscriptionManagement\SubscriptionManagementController
FormRequests: App\Http\Requests\Admin\Subscriptions\StoreSubscriptionRequest, App\Http\Requests\Admin\SubscriptionManagement\StoreSubscriptionRequest, App\Http\Requests\Admin\SubscriptionManagement\UpdateCompanySubscriptionRequest, App\Http\Requests\Admin\SubscriptionManagement\FinalizeCompanySubscriptionRequest, App\Http\Requests\Admin\SubscriptionManagement\RejectSubscriptionRequest, App\Http\Requests\Admin\SubscriptionManagement\MassAcceptSubscriptionsRequest, App\Http\Requests\Admin\SubscriptionManagement\MassRejectSubscriptionsRequest
Policy registration + abilities: App\Policies\Admin\Subscriptions\SubscriptionPolicy registered in AuthServiceProvider for Subscription::class
Actions / Services / Gateways: App\Services\Admin\Subscriptions\AdminSubscriptionService, App\Services\Admin\SubscriptionManagement\SubscriptionManagementService
Models / Enums / Events / Jobs: App\Models\Admin\Subscription\Subscription, App\Models\Admin\Plans\Plan, App\Models\Admin\Tenants\Tenant
DataTable / index / filters / buttons: App\DataTables\Admin\Subscriptions\SubscriptionDataTable, App\DataTables\Admin\SubscriptionMangment\subscriptionMangmentDataTable
Create UI + draft resource key + restore/purge evidence: resources/views/admin/subscriptions/create.blade.php (multi-step wizard with draft support, resource key admin.subscription-create)
Edit UI: Modal adjustments in resources/views/admin/subscription-management/index.blade.php
Review step: step-subscription-review in resources/views/admin/subscriptions/create.blade.php
Show UI + child tables + attachments: Not applicable (subscription records inspected via data-tables and confirmation modals in resources/views/admin/subscription-management/index.blade.php)
Archive controller / DataTable / views: Not applicable (financial subscriptions are immutable audit records)
Shared components and JavaScript contracts: table-status-toggle.js, form-validation.js
Files and storage lifecycle: Bank transfer deposit receipts verified via secure attachment urls
Tests: Tests\Feature\AdminDashboardCrudFeatureTest, Tests\Unit\CentralPolicyContractTest, Tests\Unit\SuperAdminContractTest
Verification commands and passing evidence: php artisan test tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_subscriptions_and_bills_lifecycle"
Not applicable items: Show UI, archive (financial audit records are permanent)
Remaining gaps: Baseline audit pending; rebuild and revalidate this administration before certification.
Status: Gap
