<?php

declare(strict_types=1);

namespace App\Services\Tenants\Reports;

use Illuminate\Support\Facades\Cache;

class TenantExportAccess
{
    public function registerFile(string $fileName, int $userId): void
    {
        Cache::put($this->fileKey($fileName), $userId, now()->addDay());
    }

    public function authorizeFile(string $fileName, ?int $userId): void
    {
        abort_unless(
            $userId !== null
            && basename($fileName) === $fileName
            && hash_equals((string) Cache::get($this->fileKey($fileName), ''), (string) $userId),
            404
        );
    }

    public function registerExport(string $exportId, int $userId): void
    {
        Cache::put($this->exportKey($exportId), $userId, now()->addDay());
    }

    public function authorizeExport(string $exportId, ?int $userId): void
    {
        abort_unless(
            $userId !== null
            && hash_equals((string) Cache::get($this->exportKey($exportId), ''), (string) $userId),
            404
        );
    }

    private function fileKey(string $fileName): string
    {
        return sprintf('tenant:%s:export-file:%s', tenant('id'), hash('sha256', $fileName));
    }

    private function exportKey(string $exportId): string
    {
        return sprintf('tenant:%s:export-job:%s', tenant('id'), hash('sha256', $exportId));
    }
}
