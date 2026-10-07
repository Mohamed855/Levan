<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Import;

class CancelCollectionImportRequest extends CollectionImportRequest
{
    public function rules(): array
    {
        return ['temp_path' => ['required', 'string', 'regex:/^temp\\/imports\\/[A-Za-z0-9_.-]+$/']];
    }

    public function withValidator($validator): void
    {
        $this->ownsTemporaryFile($validator);
    }
}
