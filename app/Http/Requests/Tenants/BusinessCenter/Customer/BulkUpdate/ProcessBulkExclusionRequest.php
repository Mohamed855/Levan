<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate;

class ProcessBulkExclusionRequest extends BulkUpdateRequest
{
    public function rules(): array
    {
        return [
            'temp_file_path' => ['required', 'string', 'regex:/^temp_updates\\/[A-Za-z0-9_.-]+$/'],
            'portfolio_id' => ['required', 'integer', 'exists:portfolios,id'],
            'exclusion_key' => ['required', 'in:account_number,identity_number'],
        ];
    }

    public function withValidator($validator): void
    {
        $this->ownsTemporaryFile($validator);
    }
}
