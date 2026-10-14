<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Collection;

use Illuminate\Foundation\Http\FormRequest;

class RejectSelectedAdminCollectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('admin_collections.reject_all') ?? false;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:collections,id'],
            'rejection_reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['ids.required' => 'يرجى تحديد تحصيل واحد على الأقل.', 'rejection_reason.required' => 'يرجى إدخال سبب الرفض.'];
    }

    public function attributes(): array
    {
        return ['ids' => 'التحصيلات', 'rejection_reason' => 'سبب الرفض'];
    }
}
