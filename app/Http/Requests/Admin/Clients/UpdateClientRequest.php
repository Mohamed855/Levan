<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Clients;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user('admin')?->can('clients.edit') || $this->user('admin')?->can('companies.edit'));
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'commercial_number' => ['required', 'string', 'digits:10'],
            'tax_number' => ['required', 'string', 'digits:15', 'regex:/^3\d{14}$/'],
            'city_id' => ['required', 'exists:cities,id'],
            'address' => ['required', 'string', 'max:1000'],
            'primary_contact_name' => ['nullable', 'string', 'max:255'],
            'primary_contact_email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'primary_contact_phone' => ['nullable', 'string', 'size:10', 'regex:/^05[0-9]{8}$/'],
            'email_notifications_enabled' => ['nullable', 'boolean'],
            'two_factor_enabled' => ['nullable', 'boolean'],
            'external_integrations_enabled' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'يرجى إدخال اسم العميل / الشركة',
            'commercial_number.required' => 'يرجى إدخال رقم السجل التجاري',
            'commercial_number.digits' => 'رقم السجل التجاري يجب أن يتكون من 10 أرقام',
            'city_id.required' => 'يرجى اختيار المدينة',
            'address.required' => 'يرجى إدخال العنوان',
            'primary_contact_email.email' => 'يرجى إدخال بريد إلكتروني صحيح',
            'primary_contact_email.max' => 'يجب ألا يزيد البريد الإلكتروني عن 255 حرفاً',
            'primary_contact_phone.size' => 'يجب أن يتكون رقم الجوال من 10 أرقام',
            'primary_contact_phone.regex' => 'يجب أن يبدأ رقم الجوال بـ 05 ويتكون من 10 أرقام',
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name' => 'اسم العميل / الشركة',
            'commercial_number' => 'رقم السجل التجاري',
            'tax_number' => 'الرقم الضريبي',
            'city_id' => 'المدينة',
            'address' => 'العنوان',
            'primary_contact_name' => 'اسم المسؤول',
            'primary_contact_email' => 'البريد الإلكتروني',
            'primary_contact_phone' => 'رقم الجوال',
        ];
    }
}
