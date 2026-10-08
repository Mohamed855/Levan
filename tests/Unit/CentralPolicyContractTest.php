<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Admin\Companies\CompanyController;
use App\Http\Controllers\Admin\Coupons\AdminCouponController;
use App\Http\Controllers\Admin\AdminMangment\Users\AdminUserController;
use App\Http\Controllers\Admin\Plans\PlanController;
use App\Policies\Admin\Coupons\CouponPolicy;
use App\Policies\Admin\Plans\PlanPolicy;
use App\Policies\Admin\Tenants\TenantPolicy;
use App\Policies\Admin\Users\AdminUserPolicy;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CentralPolicyContractTest extends TestCase
{
    public function test_central_resource_policies_expose_the_resource_lifecycle_abilities(): void
    {
        foreach ([PlanPolicy::class, TenantPolicy::class, AdminUserPolicy::class, CouponPolicy::class] as $policy) {
            $reflection = new ReflectionClass($policy);

            foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $ability) {
                $this->assertTrue($reflection->hasMethod($ability), "{$policy} must define {$ability}().");
            }
        }
    }

    public function test_central_resource_controllers_apply_resource_authorization(): void
    {
        foreach ([PlanController::class, CompanyController::class, AdminUserController::class, AdminCouponController::class] as $controller) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertStringContainsString('authorizeResource(', $content);
        }
    }
}
