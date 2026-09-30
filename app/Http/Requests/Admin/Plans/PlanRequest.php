<?php

namespace App\Http\Requests\Admin\Plans;

use App\Http\Requests\Concerns\WizardFormRequest;

class PlanRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            "subscription_duration_type" => $this->input("subscription_duration_type", "limited"),
            "subscription_billing_cycle" => $this->input("subscription_billing_cycle", "yearly"),
        ]);
    }

    public function rules(): array
    {
        return [
            'name'                   => ['required', 'string', 'max:255'],
            'is_active'              => ['required', 'boolean'],
            'price'                  => ['required', 'numeric', 'min:0'],
            'extra_user_license_price'  => ['required', 'numeric', 'min:0'],
            'yearly_renewal_price'   => ['required', 'numeric', 'min:0'],
            'included_users'         => ['required', 'numeric', 'min:0'],
            'subscription_duration_type' => ['required', 'in:limited,unlimited'],
            'subscription_billing_cycle' => ['nullable', 'required_if:subscription_duration_type,limited', 'in:monthly,yearly'],
            'description'            => ['nullable', 'string', 'max:1000'],

            'discount_percentage'    => ['nullable', 'numeric', 'min:0', 'max:100'],

            // نوع حد الفروع (محدود / غير محدود)
            'unlimited_branches'     => ['required', 'boolean'],

            // عدد الفروع - مطلوب إذا كان النوع محدود
            'branches_limit'         => ['nullable', 'integer', 'min:1', 'required_if:unlimited_branches,0'],

            // ميزة إدارة الدومينات
            'domain_management_enabled' => ['required', 'boolean'],

            // المميزات
            'features'               => ['required', 'array', 'min:1'],
            'features.*'             => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'              => 'اسم الباقة مطلوب',
            'name.max'                   => 'اسم الباقة يجب ألا يتجاوز 255 حرف',

            'is_active.required'         => 'يجب تحديد حالة الباقة',
            'is_active.boolean'          => 'قيمة حالة الباقة غير صحيحة',

            'price.required'             => 'السعر  مطلوب',
            'price.numeric'              => 'السعر  يجب أن يكون رقمًا صحيحًا',
            'price.min'                  => 'السعر  يجب أن يكون أكبر من 0',


            'extra_user_license_price.required'             => 'سعر الترخيص  مطلوب',
            'extra_user_license_price.numeric'              => 'سعر الترخيص  يجب أن يكون رقمًا صحيحًا',
            'extra_user_license_price.min'                  => 'سعر الترخيص  يجب أن يكون أكبر من 0',


            'yearly_renewal_price.required' => 'سعر التجديد السنوي  مطلوب',
            'yearly_renewal_price.numeric'  => 'سعر التجديد السنوي  يجب أن يكون رقمًا صحيحًا',
            'yearly_renewal_price.min'      => 'سعر التجديد السنوي  يجب أن يكون أكبر من 0',


            'included_users.required'    => 'عدد المستخدمين مطلوب',
            'included_users.numeric'     => 'عدد المستخدمين يجب أن يكون رقمًا صحيحًا',
            'included_users.min'         => 'عدد المستخدمين يجب أن يكون أكبر من 0',

            'description.max'            => 'الوصف يجب ألا يتجاوز 1000 حرف',

            // نسبة الخصم
            'discount_percentage.numeric' => 'نسبة الخصم يجب أن تكون رقمًا صحيحًا',
            'discount_percentage.min'     => 'نسبة الخصم لا يمكن أن تكون أقل من 0',
            'discount_percentage.max'     => 'نسبة الخصم لا يمكن أن تتجاوز 100',

            // نوع حد الفروع
            'unlimited_branches.required' => 'يجب تحديد نوع حد الفروع',
            'unlimited_branches.boolean' => 'قيمة نوع حد الفروع غير صحيحة',

            // عدد الفروع
            'branches_limit.required_if' => 'عدد الفروع مطلوب عندما يكون نوع الحد محدودًا',
            'branches_limit.integer'     => 'عدد الفروع يجب أن يكون رقمًا صحيحًا',
            'branches_limit.min'         => 'عدد الفروع يجب أن يكون أكبر من 0',

            // إدارة الدومينات
            'domain_management_enabled.required' => 'يجب تحديد حالة ميزة إدارة الدومينات',
            'domain_management_enabled.boolean'  => 'قيمة ميزة إدارة الدومينات غير صحيحة',

            // المميزات
            'features.required'          => 'يجب إضافة ميزة واحدة على الأقل',
            'features.array'             => 'تنسيق المميزات غير صحيح',
            'features.min'               => 'يجب إضافة ميزة واحدة على الأقل',
            'features.*.required'        => 'كل ميزة يجب أن تحتوي على نص',
            'features.*.max'             => 'الميزة يجب ألا تتجاوز 255 حرف',
        ];
    }
}
