<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ShowCustomerSmsFormRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $ids = $this->input('customer_ids');
        $ids = is_string($ids) ? explode(',', $ids) : $ids;
        $this->merge(['customer_ids' => is_array($ids) ? array_values(array_filter($ids, fn ($id) => $id !== '' && $id !== null)) : []]);
    }

    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.send_message') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_ids' => ['required', 'array', 'min:1', 'max:500'],
            'customer_ids.*' => ['required', 'integer', 'distinct', 'exists:customers,id'],
        ];
    }
}
