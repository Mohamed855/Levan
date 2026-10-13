<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Company;

use App\Data\Tenants\BusinessCenter\Company\CompanyCreateDTO;
use App\Http\Requests\Concerns\WizardFormRequest;

class StoreCompanyRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('companies.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:companies,name'],
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['sometimes', 'in:active,inactive'],
            'target_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'authorized_people' => ['nullable', 'array', 'max:50'],
            'authorized_people.*.name' => ['nullable', 'string', 'max:255'],
            'authorized_people.*.phone' => ['nullable', 'string', 'size:10', 'regex:/^05[0-9]{8}$/'],
            'authorized_people.*.email' => ['nullable', 'email', 'max:255'],
            'authorized_people.*.position' => ['nullable', 'string', 'max:150'],
            'authorized_people.*.is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الشركة مطلوب',
            'name.unique' => 'اسم الشركة موجود مسبقاً',
            'name.max' => 'اسم الشركة يجب ألا يتجاوز 255 حرف',
            'logo.required' => 'شعار الشركة مطلوب',
            'logo.image' => 'الملف يجب أن يكون صورة صالحة',
            'logo.mimes' => 'نوع الشعار يجب أن يكون: JPG أو JPEG أو PNG أو WebP',
            'logo.max' => 'حجم الشعار يجب ألا يتجاوز 2 ميجابايت',
            'status.in' => 'حالة الشركة يجب أن تكون نشط أو غير نشط',
            'target_percentage.required' => 'نسبة المستهدف مطلوبة',
            'target_percentage.numeric' => 'نسبة المستهدف يجب أن تكون رقماً',
            'target_percentage.min' => 'نسبة المستهدف يجب أن تكون أكبر من أو تساوي 0',
            'target_percentage.max' => 'نسبة المستهدف يجب أن تكون أقل من أو تساوي 100',
            'authorized_people.array' => 'قائمة المفوضين يجب أن تكون مصفوفة',
            'authorized_people.max' => 'لا يمكن إضافة أكثر من 50 مفوض',
            'authorized_people.*.name.max' => 'اسم المفوض يجب ألا يتجاوز 255 حرف',
            'authorized_people.*.phone.size' => 'رقم هاتف المفوض يجب أن يتكون من 10 أرقام',
            'authorized_people.*.phone.regex' => 'رقم هاتف المفوض يجب أن يبدأ بـ 05',
            'authorized_people.*.email.email' => 'يرجى إدخال بريد إلكتروني صحيح للمفوض',
            'authorized_people.*.email.max' => 'بريد المفوض يجب ألا يتجاوز 255 حرف',
            'authorized_people.*.position.max' => 'منصب المفوض يجب ألا يتجاوز 150 حرف',
        ];
    }

    public function toCreateDTO(): CompanyCreateDTO
    {
        return CompanyCreateDTO::fromArray($this->validated());
    }
}
