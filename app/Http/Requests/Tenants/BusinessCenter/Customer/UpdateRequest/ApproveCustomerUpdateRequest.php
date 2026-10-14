<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\UpdateRequest;

use Illuminate\Foundation\Http\FormRequest;

class ApproveCustomerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customer_update_requests.approve') ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }
}
