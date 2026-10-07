<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate;

class DownloadBulkUpdateTemplateRequest extends BulkUpdateRequest
{
    public function rules(): array
    {
        return [
            'portfolio_id' => ['required', 'integer', 'exists:portfolios,id'],
            'match_key' => ['required', 'in:account_number,identity_number'],
            'update_fields' => ['required', 'array', 'min:1'],
            'update_fields.*' => ['required', 'string', 'max:255'],
        ];
    }
}
