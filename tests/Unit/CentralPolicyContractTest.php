<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Admin\Companies\CompanyController;
use App\Http\Controllers\Admin\ContentManagement\Client\ClientController;
use App\Http\Controllers\Admin\ContentManagement\Portfolio\PortfolioController;
use App\Http\Controllers\Admin\Coupons\AdminCouponController;
use App\Http\Controllers\Admin\AdminMangment\Users\AdminUserController;
use App\Http\Controllers\Admin\Plans\PlanController;
use App\Http\Controllers\Admin\Settings\Bank\SettingsBankController;
use App\Http\Controllers\Admin\Settings\Domain\SettingsDomainController;
use App\Http\Controllers\Admin\Versions\VersionController;
use App\Http\Controllers\Admin\VersionTester\VersionTesterController;
use App\Policies\Admin\Coupons\CouponPolicy;
use App\Policies\Admin\Plans\PlanPolicy;
use App\Policies\Admin\Tenants\TenantPolicy;
use App\Policies\Admin\Users\AdminUserPolicy;
use App\Policies\Admin\Settings\BankPolicy;
use App\Policies\Admin\Settings\ControlDomainPolicy;
use App\Policies\Admin\ContentManagement\ClientPolicy;
use App\Policies\Admin\ContentManagement\ContentPortfolioPolicy;
use App\Policies\Admin\Versions\VersionPolicy;
use App\Policies\Admin\Versions\VersionTesterPolicy;
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
        ] as $controller) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertStringContainsString('authorizeResource(', $content);
        }

        $versionTesterController = file_get_contents((new ReflectionClass(VersionTesterController::class))->getFileName());
        $this->assertStringContainsString("authorizeResource(VersionTester::class, 'version_tester')", $versionTesterController);
    }
}
