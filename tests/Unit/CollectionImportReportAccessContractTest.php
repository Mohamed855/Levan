<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Tenants\FinancialCollection\Import\CollectionImportController;
use App\Http\Requests\Tenants\FinancialCollection\Import\DownloadCollectionImportErrorReportRequest;
use App\Http\Requests\Tenants\FinancialCollection\Import\CollectionImportRequest;
use App\Jobs\Tenants\FinancialCollection\Collection\ProcessCollectionImport;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CollectionImportReportAccessContractTest extends TestCase
{
    public function test_error_reports_have_an_owner_contract_for_sync_and_queued_imports(): void
    {
        $request = new ReflectionClass(DownloadCollectionImportErrorReportRequest::class);
        $controller = file_get_contents((new ReflectionClass(CollectionImportController::class))->getFileName());
        $job = file_get_contents((new ReflectionClass(ProcessCollectionImport::class))->getFileName());

        $this->assertTrue($request->isSubclassOf(CollectionImportRequest::class));
        $this->assertStringContainsString('DownloadCollectionImportErrorReportRequest $request', $controller);
        $this->assertStringContainsString('errorReportOwnerKey($reportPath)', $controller);
        $this->assertStringContainsString('errorReportOwnerKey($errorReportPath)', $job);
    }
}
