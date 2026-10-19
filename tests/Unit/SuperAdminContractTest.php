<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Admin\Archive\AdminUser\AdminUserController;
use App\Http\Controllers\Admin\Archive\Bill\BillArchiveController;
use App\Http\Controllers\Admin\Archive\Coupon\CouponArchiveController;
use App\Http\Controllers\Admin\Archive\Plan\PlanArchiveController;
use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\Auth\AdminTwoFactorChallengeController;
use App\Http\Controllers\Admin\Auth\AdminTwoFactorSettingsController;
use App\Http\Controllers\Admin\Bills\AdminBillsController;
use App\Http\Controllers\Admin\DeleteBulk\AdminMassDeleteController;
use App\Http\Requests\Admin\Auth\AdminTwoFactorChallengeRequest;
use App\Http\Requests\Admin\Bills\RejectBillRequest;
use App\Http\Requests\Admin\Clients\StoreClientRequest;
use App\Http\Requests\Admin\Clients\UpdateClientRequest;
use App\Http\Requests\Admin\Coupons\CouponRequest;
use App\Http\Requests\Admin\DeleteBulk\MassDeleteRequest;
use App\Http\Requests\Admin\Plans\PlanRequest;
use App\Http\Requests\Admin\Subscriptions\StoreSubscriptionRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SuperAdminContractTest extends TestCase
{
    public function test_admin_mass_delete_whitelist_models_are_central_and_have_permissions(): void
    {
        $reflection = new ReflectionClass(AdminMassDeleteController::class);
        $constants = $reflection->getConstants();

        $this->assertArrayHasKey('ALLOWED_MODELS', $constants);
        $allowedModels = $constants['ALLOWED_MODELS'];

        foreach ($allowedModels as $key => $config) {
            $this->assertArrayHasKey('class', $config, "Model config {$key} must have class");
            $this->assertArrayHasKey('permission', $config, "Model config {$key} must have permission");
            $this->assertTrue(class_exists($config['class']), "Class {$config['class']} must exist");
            $this->assertMatchesRegularExpression('/^[a-z_]+(\.[a-z_]+)*$/', (string) $config['permission'], "Permission for {$key} must use English dot notation");
        }
    }

    public function test_admin_mutations_use_dedicated_form_requests(): void
    {
        $massDelete = new ReflectionClass(AdminMassDeleteController::class);
        $this->assertSame(MassDeleteRequest::class, $massDelete->getMethod('massDelete')->getParameters()[0]->getType()?->getName());

        $bills = new ReflectionClass(AdminBillsController::class);
        $this->assertSame(RejectBillRequest::class, $bills->getMethod('reject')->getParameters()[0]->getType()?->getName());

        foreach ([MassDeleteRequest::class, RejectBillRequest::class] as $requestClass) {
            $request = new ReflectionClass($requestClass);
            $this->assertTrue($request->hasMethod('authorize'));
            $this->assertTrue($request->hasMethod('rules'));
        }
    }

    public function test_admin_two_factor_challenge_uses_a_dedicated_form_request(): void
    {
        $twoFactor = new ReflectionClass(AdminTwoFactorChallengeController::class);
        $this->assertSame(
            AdminTwoFactorChallengeRequest::class,
            $twoFactor->getMethod('store')->getParameters()[0]->getType()?->getName(),
        );
    }

    public function test_admin_authentication_controllers_delegate_persistence_to_actions(): void
    {
        foreach ([
            AdminAuthController::class,
            AdminTwoFactorChallengeController::class,
            AdminTwoFactorSettingsController::class,
        ] as $controller) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertDoesNotMatchRegularExpression('/(?:AdminUser::where|->(?:update|save|forceFill))\s*\(/', $content);
        }
    }

    public function test_central_archive_controllers_delegate_restore_and_force_delete_to_an_action(): void
    {
        foreach ([
            AdminUserController::class,
            BillArchiveController::class,
            CouponArchiveController::class,
            PlanArchiveController::class,
        ] as $controller) {
            $source = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertStringContainsString('ManageCentralArchiveAction', $source);
            $this->assertDoesNotMatchRegularExpression('/\$(?:adminUser|bill|coupon|plan)->(?:restore|forceDelete)\s*\(/', $source);
        }
    }

    public function test_admin_login_uses_the_shared_validation_contract(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/admin/auth/login.blade.php');
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/admin/admin-auth.php');

        $this->assertStringContainsString('data-form-validate', $view);
        $this->assertStringContainsString("route('admin.form-validation')", $view);
        $this->assertStringContainsString('WizardDraftController::class, \'validateStep\'', $routes);
        $this->assertStringContainsString("AdminAuthController::class . '@login'", $routes);
    }

    public function test_super_admin_form_requests_have_authorization_and_rules(): void
    {
        $requests = [
            PlanRequest::class,
            StoreClientRequest::class,
            UpdateClientRequest::class,
            StoreSubscriptionRequest::class,
            CouponRequest::class,
        ];

        foreach ($requests as $requestClass) {
            $this->assertTrue(class_exists($requestClass), "{$requestClass} must exist");
            $ref = new ReflectionClass($requestClass);
            $this->assertTrue($ref->hasMethod('authorize'), "{$requestClass} must have authorize method");
            $this->assertTrue($ref->hasMethod('rules'), "{$requestClass} must have rules method");
        }
    }

    public function test_admin_bills_controller_has_authorization_middleware(): void
    {
        $ref = new ReflectionClass(AdminBillsController::class);
        $constructor = $ref->getConstructor();
        $this->assertNotNull($constructor, 'AdminBillsController must have a constructor');

        $content = file_get_contents($ref->getFileName());
        $this->assertStringContainsString('financial_transactions.view', $content);
        $this->assertStringContainsString('subscriptions.pending.approve', $content);
        $this->assertStringContainsString('subscriptions.pending.reject', $content);
    }

    public function test_admin_controllers_do_not_keep_inline_request_validation_rules(): void
    {
        $controllers = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app/Http/Controllers/Admin'),
        );

        foreach ($controllers as $controller) {
            if (! $controller->isFile() || $controller->getExtension() !== 'php' || str_ends_with($controller->getFilename(), '.orig')) {
                continue;
            }

            $content = file_get_contents($controller->getPathname());
            $this->assertDoesNotMatchRegularExpression(
                '/\\$request->validate\\s*\\(/',
                $content,
                sprintf('%s must use a dedicated FormRequest.', $controller->getPathname()),
            );
        }
    }

    public function test_subscription_management_approval_routes_and_requests_use_their_specific_permissions(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/admin/admin.php');

        $this->assertStringContainsString("Route::middleware('can:subscriptions.pending.approve')->group", $routes);
        $this->assertStringContainsString("Route::middleware('can:subscriptions.pending.reject')->group", $routes);

        $approve = file_get_contents(dirname(__DIR__, 2).'/app/Http/Requests/Admin/SubscriptionManagement/MassAcceptSubscriptionsRequest.php');
        $reject = file_get_contents(dirname(__DIR__, 2).'/app/Http/Requests/Admin/SubscriptionManagement/MassRejectSubscriptionsRequest.php');

        $this->assertStringContainsString("can('subscriptions.pending.approve')", $approve);
        $this->assertStringContainsString("can('subscriptions.pending.reject')", $reject);
    }

    public function test_ticket_service_toggle_requires_system_settings_permission(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/Admin/Tickets/AdminTicketController.php');
        $request = file_get_contents(dirname(__DIR__, 2).'/app/Http/Requests/Admin/Tickets/ToggleTicketsServiceRequest.php');

        $this->assertStringContainsString("middleware('can:system.settings')->only(['toggleService'])", $controller);
        $this->assertStringContainsString("can('system.settings')", $request);
    }

    public function test_version_and_ticket_mutation_requests_check_their_controller_capabilities(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        foreach ([
            'app/Http/Requests/Admin/Versions/VersionRequest.php' => 'versions.create',
            'app/Http/Requests/Admin/VersionTesters/VersionTesterRequest.php' => 'version_testers.create',
            'app/Http/Requests/Admin/Tickets/ReplyTicketRequest.php' => 'tickets.reply',
            'app/Http/Requests/Admin/Tickets/UpdateTicketStatusRequest.php' => 'tickets.reply',
        ] as $path => $permission) {
            $request = file_get_contents($projectRoot.'/'.$path);

            $this->assertStringContainsString("'{$permission}'", $request);
            $this->assertStringContainsString('->can(', $request);
            $this->assertStringNotContainsString('return true;', $request);
        }
    }

    public function test_core_admin_mutation_requests_enforce_their_controller_capabilities(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        foreach ([
            'app/Http/Requests/Admin/Plans/PlanRequest.php' => 'plans.create',
            'app/Http/Requests/Admin/Settings/Bank/StoreBankRequest.php' => 'bank_settings.view',
            'app/Http/Requests/Admin/Settings/Domain/StoreControlDomainRequest.php' => 'domain_extension_prices.settings',
            'app/Http/Requests/Admin/Settings/AdminMainSettingsRequest.php' => 'system.settings',
        ] as $path => $permission) {
            $request = file_get_contents($projectRoot.'/'.$path);

            $this->assertStringContainsString("'{$permission}'", $request);
            $this->assertStringContainsString('->can(', $request);
            $this->assertStringNotContainsString('return true;', $request);
        }
    }

    public function test_admin_dashboard_requires_its_permission_and_validates_activity_range(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $controller = file_get_contents($projectRoot.'/app/Http/Controllers/Admin/Dashboard/DashboardController.php');

        $this->assertStringContainsString("middleware('can:dashboard.main')", $controller);
        $this->assertStringContainsString('PlatformActivityRequest $request', $controller);
        $this->assertStringContainsString('private DashboardService $dashboardService', $controller);
    }

    public function test_admin_activity_log_uses_a_scoped_filter_request(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $controller = file_get_contents($projectRoot.'/app/Http/Controllers/Admin/ActivityLog/AdminActivityLogController.php');

        $this->assertStringContainsString('AdminActivityLogIndexRequest $request', $controller);
        $this->assertStringNotContainsString('use Illuminate\\Http\\Request;', $controller);
    }

    public function test_admin_role_mutation_requests_enforce_their_matching_capabilities(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        foreach ([
            'app/Http/Requests/Admin/AdminMangment/Role/AdminStoreRoleRequest.php' => 'roles.create',
            'app/Http/Requests/Admin/AdminMangment/Role/AdminUpdateRoleRequest.php' => 'roles.edit',
            'app/Http/Requests/Admin/AdminMangment/Role/AdminUpdateUserRoleRequest.php' => 'roles.assign',
        ] as $path => $permission) {
            $request = file_get_contents($projectRoot.'/'.$path);

            $this->assertStringContainsString("'{$permission}'", $request);
            $this->assertStringContainsString('->can(', $request);
            $this->assertStringNotContainsString('return true;', $request);
        }
    }

    public function test_admin_user_mutation_requests_enforce_their_matching_capabilities(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        foreach ([
            'app/Http/Requests/Admin/AdminMangment/Users/AdminUserStoreRequest.php' => 'users.create',
            'app/Http/Requests/Admin/AdminMangment/Users/AdminUserUpdateRequest.php' => 'users.edit',
            'app/Http/Requests/Admin/AdminMangment/Users/BulkDeleteAdminUsersRequest.php' => 'users.delete',
        ] as $path => $permission) {
            $request = file_get_contents($projectRoot.'/'.$path);

            $this->assertStringContainsString("'{$permission}'", $request);
            $this->assertStringContainsString('->can(', $request);
            $this->assertStringNotContainsString('return true;', $request);
        }
    }

    public function test_admin_unique_check_requests_require_their_resource_create_or_edit_capability(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        foreach ([
            'app/Http/Requests/Admin/AdminMangment/Users/CheckAdminUserEmailUniqueRequest.php' => 'users.create',
            'app/Http/Requests/Admin/AdminMangment/Users/CheckAdminUserNameUniqueRequest.php' => 'users.create',
            'app/Http/Requests/Admin/AdminMangment/Users/CheckAdminUserPhoneUniqueRequest.php' => 'users.create',
            'app/Http/Requests/Admin/Clients/CheckAdminEmailUniqueRequest.php' => 'clients.create',
            'app/Http/Requests/Admin/Clients/CheckAdminPhoneUniqueRequest.php' => 'clients.create',
            'app/Http/Requests/Admin/Clients/CheckCommercialNumberUniqueRequest.php' => 'clients.create',
            'app/Http/Requests/Admin/Clients/CheckCompanyNameUniqueRequest.php' => 'clients.create',
            'app/Http/Requests/Admin/Clients/CheckTaxNumberUniqueRequest.php' => 'clients.create',
        ] as $path => $permission) {
            $request = file_get_contents($projectRoot.'/'.$path);

            $this->assertStringContainsString("'{$permission}'", $request);
            $this->assertStringContainsString('->can(', $request);
            $this->assertStringNotContainsString('return true;', $request);
        }
    }
}
