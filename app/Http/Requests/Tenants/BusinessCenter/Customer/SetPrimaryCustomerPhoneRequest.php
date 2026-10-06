<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer;

use Illuminate\Foundation\Http\FormRequest;

class SetPrimaryCustomerPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.add_phone') ?? false;
    }

    public function rules(): array
    {
        return ['phone_id' => ['required', 'integer', 'exists:customer_phones,id']];
    }
}
