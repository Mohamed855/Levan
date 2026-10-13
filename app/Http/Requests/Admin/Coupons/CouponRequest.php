<?php

namespace App\Http\Requests\Admin\Coupons;

use App\Http\Requests\Concerns\WizardFormRequest;
use App\Models\Admin\Coupons\Coupon;

class CouponRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        $permission = $this->isMethod('POST') ? 'coupons.create' : 'coupons.edit';

        return $this->user('admin')?->can($permission) ?? false;
    }

    public function rules(): array
    {
        $coupon = $this->route('coupon');
        $couponId = $coupon instanceof Coupon ? $coupon->id : (is_numeric($coupon) ? (int) $coupon : null);

        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:coupons,code,'.$couponId,
            'discount_type' => 'required|in:fixed,percentage',

            'discount_value' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($this->discount_type === 'percentage' && $value > 100) {
                        $fail('النسبة المئوية يجب أن تكون بين 0 و 100');
                    }
                },
            ],

            'max_discount' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date|after_or_equal:today',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'required|boolean',
            'admin_user_id' => 'nullable|exists:admin_users,id',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'اسم الكوبون مطلوب',
            'code.required' => 'كود الكوبون مطلوب',
            'code.unique' => 'كود الكوبون مستخدم من قبل',
            'discount_type.required' => 'نوع الخصم مطلوب',
            'discount_type.in' => 'نوع الخصم غير صحيح',
            'discount_value.required' => 'قيمة الخصم مطلوبة',
            'discount_value.numeric' => 'قيمة الخصم يجب أن تكون رقم',
            'discount_value.min' => 'قيمة الخصم يجب أن تكون أكبر من أو تساوي صفر',
            'max_discount.numeric' => 'الحد الأقصى للخصم يجب أن يكون رقم',
            'max_discount.min' => 'الحد الأقصى للخصم يجب أن يكون أكبر من أو تساوي صفر',
            'expiry_date.date' => 'تاريخ الانتهاء غير صحيح',
            'expiry_date.after_or_equal' => 'تاريخ الانتهاء يجب أن يكون اليوم أو في المستقبل',
            'usage_limit.integer' => 'حد الاستخدام يجب أن يكون رقم صحيح',
            'usage_limit.min' => 'حد الاستخدام يجب أن يكون على الأقل 1',
            'is_active.required' => 'حالة الكوبون مطلوبة',
            'is_active.boolean' => 'حالة الكوبون غير صالحة',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $this->is_active,
            ]);
        }

        if ($this->discount_type === 'fixed') {
            $this->merge([
                'max_discount' => null,
            ]);
        }
    }
}
