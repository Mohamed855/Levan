<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Collection;

use Illuminate\Foundation\Http\FormRequest;

class DeleteSelectedCollectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('collections.delete') ?? false;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:collections,id'],
        ];
    }

    public function messages(): array
    {
        return ['ids.required' => 'يرجى تحديد تحصيل واحد على الأقل.'];
    }

    public function attributes(): array
    {
        return ['ids' => 'التحصيلات'];
    }
}
