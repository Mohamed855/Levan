<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Tenants\Assignments\AssignmentsController;
use App\Http\Controllers\Tenants\Auctions\AuctionsController;
use App\Http\Controllers\Tenants\Properties\PropertiesController;
use App\Http\Controllers\Tenants\UserMangment\User\UserController;
use App\Policies\Tenants\Assignments\AssignmentPolicy;
use App\Policies\Tenants\Auctions\AuctionPolicy;
use App\Policies\Tenants\Properties\PropertyPolicy;
use App\Policies\Tenants\UserManagement\User\UserPolicy;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TenantAssetPolicyContractTest extends TestCase
{
    public function test_asset_policies_expose_the_resource_lifecycle_abilities(): void
    {
        foreach ([
            AssignmentPolicy::class,
            PropertyPolicy::class,
            AuctionPolicy::class,
            UserPolicy::class,
        ] as $policy) {
            $reflection = new ReflectionClass($policy);

            foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $ability) {
                $this->assertTrue($reflection->hasMethod($ability), "{$policy} must define {$ability}().");
            }
        }
    }

    public function test_asset_controllers_apply_resource_authorization(): void
    {
        foreach ([
            AssignmentsController::class,
            PropertiesController::class,
            AuctionsController::class,
            UserController::class,
        ] as $controller) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertStringContainsString('authorizeResource(', $content);
        }
    }
}
