<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Tenants\Archive\Company\CompanyArchiveController;
use App\Http\Controllers\Tenants\Archive\Contract\ContractArchiveController;
use App\Http\Controllers\Tenants\Archive\Customer\CustomerArchiveController;
use App\Http\Controllers\Tenants\Archive\Portfolio\PortfolioArchiveController;
use App\Http\Controllers\Tenants\Archive\User\UserArchiveController;
use App\Policies\Tenants\BusinessCenter\Company\CompanyPolicy;
use App\Policies\Tenants\BusinessCenter\Contract\ContractPolicy;
use App\Policies\Tenants\BusinessCenter\Customer\CustomerPolicy;
use App\Policies\Tenants\BusinessCenter\Portfolio\PortfolioPolicy;
use App\Policies\Tenants\UserManagement\User\UserPolicy;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class TenantArchivePolicyContractTest extends TestCase
{
    public function test_tenant_archive_policies_expose_restore_and_force_delete(): void
    {
        foreach ([
            UserPolicy::class,
            CompanyPolicy::class,
            ContractPolicy::class,
            PortfolioPolicy::class,
            CustomerPolicy::class,
        ] as $policy) {
            $reflection = new ReflectionClass($policy);

            $this->assertTrue($reflection->hasMethod('restore'), "{$policy} must define restore().");
            $this->assertTrue($reflection->hasMethod('forceDelete'), "{$policy} must define forceDelete().");
        }
    }

    public function test_tenant_archive_single_record_mutations_authorize_the_record(): void
    {
        foreach ([
            UserArchiveController::class,
            CompanyArchiveController::class,
            ContractArchiveController::class,
            PortfolioArchiveController::class,
            CustomerArchiveController::class,
        ] as $controller) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertStringContainsString("authorize('restore'", $content);
            $this->assertStringContainsString("authorize('forceDelete'", $content);
        }
    }

    public function test_tenant_archive_bulk_mutations_authorize_each_target_record(): void
    {
        foreach ([
            UserArchiveController::class,
            CompanyArchiveController::class,
            ContractArchiveController::class,
            PortfolioArchiveController::class,
            CustomerArchiveController::class,
        ] as $controller) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertStringContainsString('onlyTrashed()->whereKey(', $content);
            $this->assertStringContainsString("authorize('restore'", $content);
            $this->assertStringContainsString("authorize('forceDelete'", $content);
        }
    }
}
