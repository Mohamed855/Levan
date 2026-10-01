<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Companies;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('companies.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'company_name'                  => ['required', 'string', 'max:255'],
            'commercial_number'             => ['required', 'string', 'digits:10'],
            'tax_number'                    => ['nullable', 'string', 'max:50'],
            'city_id'                       => ['required', 'exists:cities,id'],
            'address'                       => ['required', 'string', 'max:1000'],
            'primary_contact_name'          => ['nullable', 'string', 'max:255'],
            'primary_contact_email'         => ['nullable', 'email', 'max:255'],
            'primary_contact_phone'         => ['nullable', 'string', 'regex:/^05[0-9]{8}$/'],
            'email_notifications_enabled'   => ['nullable', 'boolean'],
            'two_factor_enabled'            => ['nullable', 'boolean'],
            'external_integrations_enabled' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required'        => 'يرجى إدخال اسم الشركة',
            'commercial_number.required'   => 'يرجى إدخال رقم السجل التجاري',
            'commercial_number.digits'     => 'رقم السجل التجاري يجب أن يتكون من 10 أرقام',
            'city_id.required'             => 'يرجى اختيار المدينة',
            'address.required'             => 'يرجى إدخال العنوان',
            'primary_contact_phone.regex'  => 'يجب أن يبدأ رقم الجوال بـ 05 ويتكون من 10 أرقام',
        ];
    }
}
