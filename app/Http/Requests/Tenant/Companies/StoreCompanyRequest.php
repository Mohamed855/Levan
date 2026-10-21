<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Companies;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('companies.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'                           => ['required', 'string', 'max:255', 'unique:companies,name'],
            'logo'                           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'status'                         => ['nullable', 'in:active,inactive'],
            'authorized_people'              => ['nullable', 'array', 'max:50'],
            'authorized_people.*.name'       => ['required_with:authorized_people', 'string', 'max:255'],
            'authorized_people.*.phone'      => ['nullable', 'string', 'max:30'],
            'authorized_people.*.email'      => ['nullable', 'email', 'max:255'],
            'authorized_people.*.position'   => ['nullable', 'string', 'max:150'],
            'authorized_people.*.is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الشركة مطلوب.',
            'name.unique'   => 'اسم الشركة موجود مسبقاً.',
            'logo.image'    => 'الملف المرفوع يجب أن يكون صورة صالحة.',
            'logo.max'      => 'حجم الشعار يجب ألا يتجاوز 2 ميجابايت.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                           => 'اسم الشركة',
            'logo'                           => 'شعار الشركة',
            'status'                         => 'حالة الشركة',
            'authorized_people'              => 'المفوضون',
            'authorized_people.*.name'       => 'اسم المفوض',
            'authorized_people.*.phone'      => 'رقم هاتف المفوض',
            'authorized_people.*.email'      => 'البريد الإلكتروني للمفوض',
            'authorized_people.*.position'   => 'منصب المفوض',
        ];
    }
}
