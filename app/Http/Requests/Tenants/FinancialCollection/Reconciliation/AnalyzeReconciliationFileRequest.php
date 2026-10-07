<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Reconciliation;

class AnalyzeReconciliationFileRequest extends ReconciliationRequest
{
    public function rules(): array
    {
        return ['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240']];
    }
}
