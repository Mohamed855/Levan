<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate;

class GetBulkUpdatePortfoliosRequest extends BulkUpdateRequest
{
    public function rules(): array
    {
        return ['company_id' => ['required', 'integer', 'exists:companies,id']];
    }
}
