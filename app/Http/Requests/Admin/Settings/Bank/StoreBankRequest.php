<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings\Bank;

use App\Http\Requests\Concerns\WizardFormRequest;

class StoreBankRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('bank_settings.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'image', 'mimes:jpeg,png,jpg,svg', 'max:10240'],
            'account_name' => ['required', 'string', 'max:255'],
            'iban' => ['required', 'string', 'regex:/^SA[0-9]{22}$/D'],
            'account_number' => ['required', 'string', 'max:255', 'regex:/^[0-9]+$/D'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_name.required' => 'اسم البنك مطلوب',
            'icon.required' => 'أيقونة البنك مطلوبة',
            'icon.image' => 'يجب أن يكون الملف صورة',
            'icon.mimes' => 'الصيغ المسموحة: jpeg, png, jpg, svg',
            'icon.max' => 'حجم الصورة يجب ألا يتجاوز 10 ميجابايت',
            'account_name.required' => 'اسم الحساب مطلوب',
            'iban.required' => 'رقم الآيبان مطلوب',
            'iban.regex' => 'رقم الآيبان يجب أن يبدأ بـ SA ويتبعه 22 رقماً.',
            'account_number.required' => 'رقم الحساب مطلوب',
            'account_number.regex' => 'رقم الحساب يجب أن يحتوي على أرقام فقط.',
        ];
    }

    public function attributes(): array
    {
        return [
            'bank_name' => 'اسم البنك',
            'icon' => 'أيقونة البنك',
            'account_name' => 'اسم الحساب',
            'iban' => 'رقم الآيبان (IBAN)',
            'account_number' => 'رقم الحساب',
        ];
    }
}
