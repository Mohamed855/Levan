<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Import;

class ProcessCollectionImportRequest extends CollectionImportRequest
{
    public function rules(): array
    {
        return [
            'temp_path' => ['required', 'string', 'regex:/^temp\\/imports\\/[A-Za-z0-9_.-]+$/'],
            'account_column' => ['required', 'integer', 'min:0'],
            'amount_column' => ['required', 'integer', 'min:0', 'different:account_column'],
            'date_column' => ['required', 'integer', 'min:0', 'different:account_column', 'different:amount_column'],
            'date_format' => ['required', 'in:Y-m-d,d/m/Y,m/d/Y,d-m-Y,Y/m/d'],
            'payment_method_id' => ['required', 'integer', 'exists:settings_payment_methods,id'],
            'collection_type' => ['required', 'in:cash,scheduled'],
            'payment_type' => ['required', 'in:settlement,without_settlement'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes_column' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $this->ownsTemporaryFile($validator);
    }
}
