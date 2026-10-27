<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Clients;

use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user('admin')?->can('clients.create') || $this->user('admin')?->can('companies.create'));
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
        if ($this->has('phone')) {
            $updates['phone'] = $normalizeDigits($this->input('phone'));
        }

        $this->merge($updates);
    }

    public function rules(): array
    {
        return [
            // Step 1: Client details
            'company_name' => ['required', 'string', 'max:255', 'unique:tenants,company_name'],
            'commercial_number' => ['required', 'string', 'digits:10', 'regex:/^\d{10}$/', 'unique:tenants,commercial_number'],
            'tax_number' => ['required', 'string', 'digits:15', 'regex:/^3\d{13}3$/', 'unique:tenants,tax_number'],
            'city_id' => ['required', 'exists:cities,id'],
            'address' => ['required', 'string', 'max:1000'],
            'email_notifications_enabled' => ['nullable', 'boolean'],
            'two_factor_enabled' => ['nullable', 'boolean'],
            'external_integrations_enabled' => ['nullable', 'boolean'],

            // Step 2: First Admin User
            'name' => ['required', 'string', 'min:2', 'max:255', 'regex:/^[\pL\s\-\.]+$/u'],
            'phone' => ['required', 'string', 'size:10', 'regex:/^05[0-9]{8}$/'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // Step 3: Subscription
            'plan_id' => ['required', 'exists:plans,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'activate_immediately' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'يرجى إدخال اسم العميل / الشركة',
            'company_name.unique' => 'اسم العميل / الشركة غير متاح',
            'commercial_number.required' => 'يرجى إدخال رقم السجل التجاري',
            'commercial_number.digits' => 'رقم السجل التجاري يجب أن يتكون من 10 أرقام',
            'commercial_number.regex' => 'يجب أن يحتوي رقم السجل التجاري على 10 أرقام فقط',
            'commercial_number.unique' => 'رقم السجل التجاري غير متاح',
            'tax_number.required' => 'يرجى إدخال الرقم الضريبي',
            'tax_number.digits' => 'الرقم الضريبي يجب أن يتكون من 15 رقماً',
            'tax_number.regex' => 'الرقم الضريبي يجب أن يتكون من 15 رقمًا، يبدأ بالرقم 3 وينتهي بالرقم 3',
            'tax_number.unique' => 'الرقم الضريبي غير متاح',
            'city_id.required' => 'يرجى اختيار المدينة',
            'address.required' => 'يرجى إدخال العنوان',
            'name.required' => 'يرجى إدخال اسم المسؤول',
            'name.string' => 'اسم المسؤول يجب أن يكون نصاً',
            'name.min' => 'اسم المسؤول يجب ألا يقل عن حرفين',
            'name.max' => 'اسم المسؤول يجب ألا يزيد عن 255 حرفاً',
            'name.regex' => 'اسم المسؤول يجب أن يحتوي على أحرف فقط',
            'phone.required' => 'يرجى إدخال رقم الجوال',
            'phone.size' => 'يجب أن يتكون رقم الجوال من 10 أرقام',
            'phone.regex' => 'يجب أن يبدأ رقم الجوال بـ 05 ويتكون من 10 أرقام',
            'email.required' => 'يرجى إدخال البريد الإلكتروني',
            'email.email' => 'يرجى إدخال بريد إلكتروني صحيح',
            'email.max' => 'يجب ألا يزيد البريد الإلكتروني عن 255 حرفاً',
            'password.required' => 'يرجى إدخال كلمة المرور',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'plan_id.required' => 'يرجى اختيار الباقة',
            'start_date.required' => 'يرجى تحديد تاريخ البداية',
            'start_date.date' => 'تاريخ البداية يجب أن يكون تاريخاً صالحاً',
            'end_date.required' => 'يرجى تحديد تاريخ النهاية',
            'end_date.date' => 'تاريخ النهاية يجب أن يكون تاريخاً صالحاً',
            'end_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية أو مساوياً له',
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
            'name' => 'اسم المسؤول',
            'phone' => 'رقم الجوال',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'password_confirmation' => 'تأكيد كلمة المرور',
            'plan_id' => 'الباقة',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية',
            'activate_immediately' => 'تفعيل الاشتراك فوراً',
        ];
    }
}
