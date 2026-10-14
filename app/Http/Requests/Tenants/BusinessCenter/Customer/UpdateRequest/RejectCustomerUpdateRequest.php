<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\UpdateRequest;

use Illuminate\Foundation\Http\FormRequest;

class RejectCustomerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customer_update_requests.reject') ?? false;
    }

    public function rules(): array
    {
        return ['reason' => ['nullable', 'string', 'max:1000']];
    }

    public function messages(): array
    {
        return ['reason.max' => 'سبب الرفض لا يجب أن يتجاوز :max حرفاً.'];
    }

    public function attributes(): array
    {
        return ['reason' => 'سبب الرفض'];
    }
}
