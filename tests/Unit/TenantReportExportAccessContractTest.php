<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Tenants\Reports\Company\CompanyReportController;
use App\Http\Controllers\Tenants\Reports\Customer\CustomerReportController;
use App\Http\Controllers\Tenants\Reports\CustomerFollow\CustomerFollowReportController;
use App\Http\Controllers\Tenants\Reports\Financial\FinancialReportController;
use App\Http\Controllers\Tenants\Reports\Performance\CompanyPerformanceReportController;
use App\Http\Controllers\Tenants\Reports\Performance\EmployeePerformanceReportController;
use App\Jobs\Tenants\Reports\Company\ExportCompanyReportJob;
use App\Jobs\Tenants\Reports\Customer\ExportCustomersJob;
use App\Jobs\Tenants\Reports\CustomerFollow\ExportCustomerFollowsPartJob;
use App\Jobs\Tenants\Reports\Financial\ExportFinancialCollectionsJob;
use App\Jobs\Tenants\Reports\Performance\ExportEmployeePerformanceJob;
use App\Jobs\Tenants\Reports\Performance\ExportPerformanceReportJob;
use App\Services\Tenants\Reports\TenantExportAccess;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class TenantReportExportAccessContractTest extends TestCase
{
    public function test_all_report_downloads_authorize_the_current_file_owner(): void
    {
        foreach ([
            CompanyReportController::class,
            CustomerReportController::class,
            CustomerFollowReportController::class,
            FinancialReportController::class,
            CompanyPerformanceReportController::class,
            EmployeePerformanceReportController::class,
        ] as $controller) {
            $source = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertStringContainsString('TenantExportAccess', $source);
            $this->assertStringContainsString('authorizeFile(', $source);
        }
    }

    public function test_all_queued_report_files_register_their_owner(): void
    {
        foreach ([
            ExportCompanyReportJob::class,
            ExportCustomersJob::class,
            ExportCustomerFollowsPartJob::class,
            ExportFinancialCollectionsJob::class,
            ExportEmployeePerformanceJob::class,
            ExportPerformanceReportJob::class,
        ] as $job) {
            $source = file_get_contents((new ReflectionClass($job))->getFileName());

            $this->assertStringContainsString('registerFile(', $source);
        }
    }

    public function test_access_keys_are_tenant_scoped_and_not_plain_file_names(): void
    {
        $source = file_get_contents((new ReflectionClass(TenantExportAccess::class))->getFileName());

        $this->assertStringContainsString("tenant('id')", $source);
        $this->assertStringContainsString("hash('sha256', \$fileName)", $source);
        $this->assertStringContainsString('hash_equals(', $source);
    }
}
