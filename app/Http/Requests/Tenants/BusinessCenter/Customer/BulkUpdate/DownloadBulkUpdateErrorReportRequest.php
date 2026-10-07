<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate;

class DownloadBulkUpdateErrorReportRequest extends BulkUpdateRequest
{
    public function rules(): array
    {
        return ['path' => ['required', 'string', 'regex:/^reports\\/bulk_update_errors_\\d+\\.xlsx$/']];
    }

    public function withValidator($validator): void
    {
        $this->ownsErrorReport($validator);
    }
}
