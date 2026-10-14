<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Collection;

use Illuminate\Foundation\Http\FormRequest;

class RejectCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('collections.reject') ?? false;
    }

    public function rules(): array
    {
        return ['rejection_reason' => ['required', 'string', 'max:500']];
    }

    public function messages(): array
    {
        return ['rejection_reason.required' => 'يرجى إدخال سبب الرفض.'];
    }

    public function attributes(): array
    {
        return ['rejection_reason' => 'سبب الرفض'];
    }
}
