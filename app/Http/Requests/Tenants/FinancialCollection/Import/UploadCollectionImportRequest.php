<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Import;

class UploadCollectionImportRequest extends CollectionImportRequest
{
    public function rules(): array
    {
        return ['import_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240']];
    }
}
