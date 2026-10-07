<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate;

class GetBulkUpdateFieldsRequest extends BulkUpdateRequest
{
    public function rules(): array
    {
        return ['portfolio_id' => ['required', 'integer', 'exists:portfolios,id']];
    }
}
