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
            'start_date' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($coupon): void {
                    if (! $value) {
                        return;
                    }

                    $parsed = parse_system_date($value);
                    if (! $parsed) {
                        return;
                    }

                    $unchanged = $coupon instanceof Coupon
                        && $coupon->start_date !== null
                        && $parsed->isSameDay($coupon->start_date);

                    if (! $unchanged && $parsed->copy()->startOfDay()->lt(now()->startOfDay())) {
                        $fail('تاريخ البداية يجب أن يكون اليوم أو في المستقبل');
                    }
                },
            ],
            'expiry_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value || ! $this->filled('start_date')) {
                        return;
                    }

                    $expiry = parse_system_date($value);
                    $start = parse_system_date($this->input('start_date'));

                    if ($expiry && $start && $expiry->copy()->startOfDay()->lt($start->copy()->startOfDay())) {
                        $fail('تاريخ الانتهاء يجب أن يكون مساوياً أو بعد تاريخ البداية');
                    }
                },
            ],
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'required|boolean',
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
            'start_date.date' => 'تاريخ البداية غير صحيح',
            'expiry_date.date' => 'تاريخ الانتهاء غير صحيح',
            'expiry_date.after_or_equal' => 'تاريخ الانتهاء يجب أن يكون اليوم أو في المستقبل',
            'usage_limit.integer' => 'حد الاستخدام يجب أن يكون رقم صحيح',
            'usage_limit.min' => 'حد الاستخدام يجب أن يكون على الأقل 1',
            'is_active.required' => 'حالة الكوبون مطلوبة',
            'is_active.boolean' => 'حالة الكوبون غير صالحة',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم الكوبون',
            'code' => 'كود الكوبون',
            'discount_type' => 'نوع الخصم',
            'discount_value' => 'قيمة الخصم',
            'max_discount' => 'الحد الأقصى للخصم',
            'start_date' => 'تاريخ البداية',
            'expiry_date' => 'تاريخ الانتهاء',
            'usage_limit' => 'حد الاستخدام',
            'is_active' => 'حالة الكوبون',
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
