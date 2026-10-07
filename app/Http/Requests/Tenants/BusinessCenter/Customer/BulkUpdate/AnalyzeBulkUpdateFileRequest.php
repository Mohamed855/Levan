<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate;

class AnalyzeBulkUpdateFileRequest extends BulkUpdateRequest
{
    public function rules(): array
    {
        return ['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240']];
    }
}
