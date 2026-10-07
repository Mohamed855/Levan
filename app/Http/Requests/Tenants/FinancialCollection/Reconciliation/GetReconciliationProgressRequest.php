<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Reconciliation;

class GetReconciliationProgressRequest extends ReconciliationRequest
{
    public function rules(): array
    {
        return ['job_id' => ['required', 'string', 'regex:/^reconciliation_[A-Za-z0-9]+$/']];
    }
}
