<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Tenants\Assignments\AssignmentsController;
use App\Http\Controllers\Tenants\Auctions\AuctionsController;
use App\Http\Controllers\Tenants\BusinessCenter\Company\CompanyController;
use App\Http\Controllers\Tenants\BusinessCenter\Contract\ContractController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\CustomerController;
use App\Http\Controllers\Tenants\BusinessCenter\Portfolio\PortfolioController;
use App\Http\Controllers\Tenants\FinancialCollection\Collection\CollectionController;
use App\Http\Controllers\Tenants\FinancialCollection\Target\CollectionTargetController;
use App\Http\Controllers\Tenants\FinancialCollection\Reconciliation\PaymentReconciliationController;
use App\Http\Controllers\Tenants\Properties\PropertiesController;
use App\Http\Controllers\Tenants\UserMangment\Team\TeamController;
use App\Http\Controllers\Tenants\UserMangment\User\UserController;
use App\Policies\Tenants\Assignments\AssignmentPolicy;
use App\Policies\Tenants\Auctions\AuctionPolicy;
use App\Policies\Tenants\BusinessCenter\Company\CompanyPolicy;
use App\Policies\Tenants\BusinessCenter\Contract\ContractPolicy;
use App\Policies\Tenants\BusinessCenter\Customer\CustomerPolicy;
use App\Policies\Tenants\BusinessCenter\Portfolio\PortfolioPolicy;
use App\Policies\Tenants\FinancialCollection\Collection\CollectionPolicy;
use App\Policies\Tenants\FinancialCollection\Target\CollectionTargetPolicy;
use App\Policies\Tenants\FinancialCollection\Reconciliation\PaymentReconciliationPolicy;
use App\Policies\Tenants\Properties\PropertyPolicy;
use App\Policies\Tenants\UserManagement\Team\TeamPolicy;
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
            CompanyPolicy::class,
            ContractPolicy::class,
            PortfolioPolicy::class,
            CustomerPolicy::class,
            TeamPolicy::class,
            UserPolicy::class,
            CollectionPolicy::class,
            CollectionTargetPolicy::class,
            PaymentReconciliationPolicy::class,
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
            CompanyController::class,
            ContractController::class,
            PortfolioController::class,
            TeamController::class,
            UserController::class,
            CollectionController::class,
            CollectionTargetController::class,
            PaymentReconciliationController::class,
        ] as $controller) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertStringContainsString('authorizeResource(', $content);
        }

        $customerController = file_get_contents((new ReflectionClass(CustomerController::class))->getFileName());
        $this->assertStringContainsString('authorize(\'view\', $customer)', $customerController);
        $this->assertStringContainsString('authorize(\'update\', $customer)', $customerController);
        $this->assertStringContainsString('authorize(\'delete\', $customer)', $customerController);
    }
}
