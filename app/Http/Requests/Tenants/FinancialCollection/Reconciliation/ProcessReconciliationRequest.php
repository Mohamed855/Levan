<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Reconciliation;

class ProcessReconciliationRequest extends ReconciliationRequest
{
    public function rules(): array
    {
        return [
            'temp_file_path' => ['required', 'string', 'regex:/^temp_reconciliations\\/[A-Za-z0-9_.-]+$/'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'portfolio_id' => ['required', 'integer', 'exists:portfolios,id'],
            'reconciliation_month' => ['required', 'date'],
            'field_mappings' => ['required', 'array'],
            'field_mappings.account_number' => ['required', 'array'],
            'field_mappings.account_number.column' => ['required', 'string', 'max:255'],
            'field_mappings.collected_amount' => ['required', 'array'],
            'field_mappings.collected_amount.column' => ['required', 'string', 'max:255'],
            'field_mappings.collection_date' => ['required', 'array'],
            'field_mappings.collection_date.column' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $this->portfolioBelongsToCompany($validator);
        $this->ownsTemporaryFile($validator);
    }
}
