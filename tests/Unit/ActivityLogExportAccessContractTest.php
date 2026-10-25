<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Tenants\activity_log\ActivityLogController;
use App\Http\Requests\Tenants\ActivityLog\DownloadActivityLogExportRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ActivityLogExportAccessContractTest extends TestCase
{
    public function test_export_file_download_requires_the_export_owner(): void
    {
        $controller = file_get_contents((new ReflectionClass(ActivityLogController::class))->getFileName());

        $this->assertStringContainsString('DownloadActivityLogExportRequest $request', $controller);
        $this->assertStringContainsString('DownloadActivityLogExportRequest::ownerKey($fileName)', $controller);
        $this->assertStringContainsString("activity_log_export_owner:", file_get_contents((new ReflectionClass(DownloadActivityLogExportRequest::class))->getFileName()));
    }
}
