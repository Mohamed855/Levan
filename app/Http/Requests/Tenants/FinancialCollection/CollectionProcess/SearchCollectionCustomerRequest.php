<?php

namespace App\Http\Requests\Tenants\FinancialCollection\CollectionProcess;

use Illuminate\Foundation\Http\FormRequest;

class SearchCollectionCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('collection_process.start') ?? false;
    }

    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'query.max' => 'عبارة البحث طويلة جداً.',
        ];
    }

    public function attributes(): array
    {
        return [
            'query' => 'عبارة البحث',
        ];
    }
}
