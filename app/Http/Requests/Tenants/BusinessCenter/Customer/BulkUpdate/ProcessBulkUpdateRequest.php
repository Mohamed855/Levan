<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate;

class ProcessBulkUpdateRequest extends BulkUpdateRequest
{
    public function rules(): array
    {
        return [
            'temp_file_path' => ['required', 'string', 'regex:/^temp_updates\\/[A-Za-z0-9_.-]+$/'],
            'portfolio_id' => ['required', 'integer', 'exists:portfolios,id'],
            'match_key' => ['required', 'in:account_number,identity_number'],
            'field_mappings' => ['required', 'array', 'min:1'],
            'field_mappings.*.column' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $this->ownsTemporaryFile($validator);
    }
}
