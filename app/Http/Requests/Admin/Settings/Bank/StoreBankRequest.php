<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings\Bank;

use App\Http\Requests\Concerns\WizardFormRequest;

class StoreBankRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'image', 'mimes:jpeg,png,jpg,svg', 'max:2048'],
            'account_name' => ['required', 'string', 'max:255'],
            'iban' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_name.required' => 'اسم البنك مطلوب',
            'icon.required' => 'أيقونة البنك مطلوبة',
            'icon.image' => 'يجب أن يكون الملف صورة',
            'icon.mimes' => 'الصيغ المسموحة: jpeg, png, jpg, svg',
            'icon.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
            'account_name.required' => 'اسم الحساب مطلوب',
            'iban.required' => 'رقم الآيبان مطلوب',
            'account_number.required' => 'رقم الحساب مطلوب',
        ];
    }
}
