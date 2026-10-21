<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Banks;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('banks.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'account_name'   => ['required', 'string', 'max:255'],
            'iban'           => ['required', 'string', 'max:34', 'unique:banks,iban'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'logo'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg,webp', 'max:2048'],
            'is_active'      => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'اسم البنك مطلوب.',
            'account_name.required' => 'اسم صاحب الحساب مطلوب.',
            'iban.required'         => 'رقم الآيبان الدولي مطلوب.',
            'iban.unique'           => 'رقم الآيبان مسجل مسبقاً لدى بنك آخر.',
            'logo.image'            => 'يجب أن يكون الشعار ملف صورة صالح.',
            'logo.max'              => 'حجم الشعار يجب ألا يتجاوز 2 ميجابايت.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'           => 'اسم البنك',
            'account_name'   => 'اسم صاحب الحساب',
            'iban'           => 'رقم الآيبان (IBAN)',
            'account_number' => 'رقم الحساب',
            'logo'           => 'شعار البنك',
            'is_active'      => 'حالة التفعيل',
        ];
    }
}
