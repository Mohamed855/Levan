<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate;

class GetBulkUpdateProgressRequest extends BulkUpdateRequest
{
    public function rules(): array
    {
        return ['job_id' => ['required', 'string', 'regex:/^bulk_(update|exclusion)_[A-Za-z0-9]+$/']];
    }
}
