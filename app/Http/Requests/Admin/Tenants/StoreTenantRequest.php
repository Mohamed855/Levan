<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tenants;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin') !== null; }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'commercial_number' => ['required', 'regex:/^[1-9][0-9]{9}$/', Rule::unique('tenants', 'commercial_number')],
            'tax_number' => ['nullable', 'regex:/^3[0-9]{13}3$/'],
            'city_id' => ['nullable', Rule::exists('cities', 'id')],
            'address' => ['nullable', 'string', 'max:500'],
            'primary_contact_name' => ['required', 'string', 'max:255'],
            'primary_contact_email' => ['required', 'email', 'max:255', Rule::unique('tenants', 'primary_contact_email')],
            'primary_contact_phone' => ['required', 'regex:/^05[0-9]{8}$/'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'plan_id'             => ['required', Rule::exists('plans', 'id')],
            'price'               => ['required', 'numeric', 'min:0'],
            'subscription_status' => ['required', Rule::in(['active', 'pending', 'trial'])],
            'trial_request_id'    => ['nullable', 'integer'],
        ];
    }
}
