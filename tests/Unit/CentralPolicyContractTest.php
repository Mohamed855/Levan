<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Admin\AdminMangment\Users\AdminUserController;
use App\Http\Controllers\Admin\Archive\Bill\BillArchiveController;
use App\Http\Controllers\Admin\Archive\Coupon\CouponArchiveController;
use App\Http\Controllers\Admin\Bills\AdminBillsController;
use App\Http\Controllers\Admin\Companies\CompanyController;
use App\Http\Controllers\Admin\ContentManagement\Client\ClientController;
use App\Http\Controllers\Admin\ContentManagement\Portfolio\PortfolioController;
use App\Http\Controllers\Admin\Coupons\AdminCouponController;
use App\Http\Controllers\Admin\Plans\PlanController;
use App\Http\Controllers\Admin\Settings\Bank\SettingsBankController;
use App\Http\Controllers\Admin\Settings\Domain\SettingsDomainController;
use App\Http\Controllers\Admin\Subscriptions\SubscriptionController;
use App\Http\Controllers\Admin\Versions\VersionController;
use App\Http\Controllers\Admin\VersionTester\VersionTesterController;
use App\Policies\Admin\Bills\BillPolicy;
use App\Policies\Admin\ContentManagement\ClientPolicy;
use App\Policies\Admin\ContentManagement\ContentPortfolioPolicy;
use App\Policies\Admin\Coupons\CouponPolicy;
use App\Policies\Admin\Plans\PlanPolicy;
use App\Policies\Admin\Settings\BankPolicy;
use App\Policies\Admin\Settings\ControlDomainPolicy;
use App\Policies\Admin\Subscriptions\SubscriptionPolicy;
use App\Policies\Admin\Tenants\TenantPolicy;
use App\Policies\Admin\Users\AdminUserPolicy;
use App\Policies\Admin\Versions\VersionPolicy;
use App\Policies\Admin\Versions\VersionTesterPolicy;
use App\Policies\Shared\RolePolicy;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CentralPolicyContractTest extends TestCase
{
    public function test_central_resource_policies_expose_the_resource_lifecycle_abilities(): void
    {
        foreach ([
            PlanPolicy::class,
            TenantPolicy::class,
            AdminUserPolicy::class,
            CouponPolicy::class,
            BankPolicy::class,
            ControlDomainPolicy::class,
            ClientPolicy::class,
            ContentPortfolioPolicy::class,
            VersionPolicy::class,
            VersionTesterPolicy::class,
            BillPolicy::class,
            SubscriptionPolicy::class,
        ] as $policy) {
            $reflection = new ReflectionClass($policy);

            foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $ability) {
                $this->assertTrue($reflection->hasMethod($ability), "{$policy} must define {$ability}().");
            }
        }
    }

    public function test_central_resource_controllers_apply_resource_authorization(): void
    {
        foreach ([
            PlanController::class,
            CompanyController::class,
            AdminUserController::class,
            AdminCouponController::class,
            SettingsBankController::class,
            SettingsDomainController::class,
            ClientController::class,
            PortfolioController::class,
            VersionController::class,
            VersionTesterController::class,
            AdminBillsController::class,
            SubscriptionController::class,
        ] as $controller) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertStringContainsString('authorizeResource(', $content);
        }

        $versionTesterController = file_get_contents((new ReflectionClass(VersionTesterController::class))->getFileName());
        $this->assertStringContainsString("authorizeResource(VersionTester::class, 'version_tester')", $versionTesterController);
    }

    public function test_central_archive_mutations_authorize_the_target_record(): void
    {
        foreach ([
            CouponArchiveController::class => ['restore', 'forceDelete'],
            BillArchiveController::class => ['restore', 'forceDelete'],
        ] as $controller => $abilities) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            foreach ($abilities as $ability) {
                $this->assertStringContainsString("authorize('{$ability}'", $content);
            }
        }

        foreach ([CouponPolicy::class, BillPolicy::class] as $policy) {
            $reflection = new ReflectionClass($policy);

            foreach (['restore', 'forceDelete'] as $ability) {
                $this->assertTrue($reflection->hasMethod($ability), "{$policy} must define {$ability}().");
            }
        }
    }

    public function test_spatie_roles_use_the_registered_guard_aware_policy(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $provider = file_get_contents($projectRoot.'/app/Providers/AuthServiceProvider.php');
        $adminController = file_get_contents($projectRoot.'/app/Http/Controllers/Admin/AdminMangment/Roles/AdminRoleController.php');
        $tenantController = file_get_contents($projectRoot.'/app/Http/Controllers/Tenants/UserMangment/Role/RoleController.php');

        $this->assertStringContainsString('Role::class => RolePolicy::class', $provider);
        $this->assertStringContainsString("authorizeResource(Role::class, 'role')", $adminController);
        $this->assertStringContainsString("authorizeResource(Role::class, 'role')", $tenantController);

        $policy = new ReflectionClass(RolePolicy::class);
        foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $ability) {
            $this->assertTrue($policy->hasMethod($ability));
        }
    }
}
