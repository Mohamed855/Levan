<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tenants;

use App\Models\Admin\Tenants\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->hasPermissionTo('tenants.update') ?? false;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');
        $tenantId = $tenant instanceof Tenant ? $tenant->getKey() : null;

        return [
            'company_name' => ['required', 'string', 'max:255'],
            'commercial_number' => ['required', 'regex:/^[1-9][0-9]{9}$/', Rule::unique('tenants', 'commercial_number')->ignore($tenantId)],
            'tax_number' => ['nullable', 'regex:/^3[0-9]{13}3$/'],
            'city_id' => ['nullable', Rule::exists('cities', 'id')],
            'address' => ['nullable', 'string', 'max:500'],
            'primary_contact_name' => ['required', 'string', 'max:255'],
            'primary_contact_email' => ['required', 'email', 'max:255', Rule::unique('tenants', 'primary_contact_email')->ignore($tenantId)],
            'primary_contact_phone' => ['required', 'regex:/^05[0-9]{8}$/'],
            'force_two_factor_auth' => ['required', 'boolean'],
        ];
    }
}
