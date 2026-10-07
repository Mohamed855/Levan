<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Reconciliation;

class GetReconciliationPortfoliosRequest extends ReconciliationRequest
{
    public function rules(): array
    {
        return ['company_id' => ['required', 'integer', 'exists:companies,id']];
    }
}
