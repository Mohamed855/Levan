<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'billing_company_name' => ['required', 'string', 'max:255'],
            'billing_commercial_number' => ['required', 'regex:/^[1-9][0-9]{9}$/'],
            'billing_tax_number' => ['required', 'regex:/^3[0-9]{13}3$/'],
            'billing_city_id' => ['required', 'integer', Rule::exists('cities', 'id')],
            'billing_address' => ['required', 'string', 'max:500'],
            'billing_name' => ['required', 'string', 'max:100'],
            'billing_email' => ['required', 'email', 'max:255'],
            'billing_phone' => ['required', 'regex:/^05\d{8}$/'],
            'billing_password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];
    }
}
