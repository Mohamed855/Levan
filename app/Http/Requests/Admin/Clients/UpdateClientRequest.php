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

    protected function prepareForValidation(): void
    {
        $normalizeDigits = static function (?string $val): string {
            if ($val === null) {
                return '';
            }
            $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

            return trim(str_replace($arabic, $western, $val));
        };

        $updates = [];
        if ($this->has('commercial_number')) {
            $updates['commercial_number'] = $normalizeDigits($this->input('commercial_number'));
        }
        if ($this->has('tax_number')) {
            $updates['tax_number'] = $normalizeDigits($this->input('tax_number'));
        }
        if ($this->has('primary_contact_phone')) {
            $updates['primary_contact_phone'] = $normalizeDigits($this->input('primary_contact_phone'));
        }

        $this->merge($updates);
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'commercial_number' => ['required', 'string', 'digits:10', 'regex:/^\d{10}$/'],
            'tax_number' => ['required', 'string', 'digits:15', 'regex:/^3\d{13}3$/'],
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
            'commercial_number.regex' => 'يجب أن يحتوي رقم السجل التجاري على 10 أرقام فقط',
            'tax_number.required' => 'يرجى إدخال الرقم الضريبي',
            'tax_number.digits' => 'الرقم الضريبي يجب أن يتكون من 15 رقماً',
            'tax_number.regex' => 'الرقم الضريبي يجب أن يتكون من 15 رقمًا، يبدأ بالرقم 3 وينتهي بالرقم 3',
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
