<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Subscriptions;

use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Http\Requests\Concerns\WizardFormRequest;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('subscriptions.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'company_id' => [
                'required',
                'string',
                'exists:tenants,id',
                function ($attribute, $value, $fail) {
                    $tenant = Tenant::find($value);
                    if ($tenant && $tenant->lastSubscription && $tenant->lastSubscription->status !== SubscriptionStatus::EXPIRED) {
                        $fail('لا يمكن إضافة اشتراك جديد للشركة إلا إذا كان آخر اشتراك لها منتهياً.');
                    }
                },
            ],
            'plan_id' => ['required', 'exists:plans,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'payment_type' => ['required', 'in:paid,unpaid,trial'],
            'payment_method' => ['nullable', 'required_if:payment_type,paid', 'in:bank_transfer,online,cash'],
            'receipt_upload' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('payment_type') === 'paid' && $this->input('payment_method') === 'bank_transfer'),
                'file',
                'mimes:jpeg,jpg,png,pdf',
                'max:10240',
            ],
            'has_discount' => ['nullable', 'in:yes,no'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'trial_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'activate_immediately' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'يرجى اختيار الشركة',
            'company_id.exists' => 'الشركة المحددة غير موجودة',
            'plan_id.required' => 'يرجى اختيار الباقة',
            'start_date.required' => 'يرجى تحديد تاريخ البداية',
            'payment_type.required' => 'يرجى تحديد حالة الدفع',
            'payment_method.required_if' => 'يرجى اختيار وسيلة الدفع',
            'receipt_upload.required' => 'يرجى رفع إيصال التحويل البنكي',
            'receipt_upload.mimes' => 'ملف الإيصال يجب أن يكون صورة أو PDF',
            'receipt_upload.max' => 'حجم ملف الإيصال يجب ألا يتجاوز 10 ميجابايت',
        ];
    }
}
