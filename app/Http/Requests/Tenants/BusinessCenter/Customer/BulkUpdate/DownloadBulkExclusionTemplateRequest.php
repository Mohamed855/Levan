<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate;

class DownloadBulkExclusionTemplateRequest extends BulkUpdateRequest
{
    public function rules(): array
    {
        return ['exclusion_key' => ['required', 'in:account_number,identity_number']];
    }
}
