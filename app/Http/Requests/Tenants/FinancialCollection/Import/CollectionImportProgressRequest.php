<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Import;

class CollectionImportProgressRequest extends CollectionImportRequest
{
    public function rules(): array
    {
        return ['job_id' => ['required', 'string', 'regex:/^import_[A-Za-z0-9.]+$/']];
    }
}
