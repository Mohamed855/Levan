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
            'period_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['required', 'string', 'in:bank_transfer,cash,card,system_free,online'],
            'payment_status' => ['nullable', 'string', 'in:completed,pending,trial,paid,unpaid'],
            'payment_type' => ['nullable', 'string', 'in:paid,unpaid,trial'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'payment_proof' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($this->input('payment_type') ?? $this->input('payment_status'), ['paid', 'completed']) && $this->input('payment_method') === 'bank_transfer' && ! $this->hasFile('receipt_upload')),
                'file',
                'mimes:jpeg,jpg,png,pdf',
                'max:10240',
            ],
            'receipt_upload' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($this->input('payment_type') ?? $this->input('payment_status'), ['paid', 'completed']) && $this->input('payment_method') === 'bank_transfer' && ! $this->hasFile('payment_proof')),
                'file',
                'mimes:jpeg,jpg,png,pdf',
                'max:10240',
            ],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
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
            'plan_id.exists' => 'الباقة المحددة غير صالحة',
            'start_date.required' => 'يرجى تحديد تاريخ البداية',
            'end_date.after_or_equal' => 'تاريخ نهاية الاشتراك يجب أن يكون بعد أو مساوياً لتاريخ البداية',
            'payment_method.required' => 'يرجى اختيار وسيلة الدفع',
            'payment_proof.required' => 'يرجى رفع إيصال التحويل البنكي',
            'payment_proof.mimes' => 'ملف إيصال الدفع يجب أن يكون صورة أو PDF',
            'payment_proof.max' => 'حجم ملف الإيصال يجب ألا يتجاوز 10 ميجابايت',
            'receipt_upload.required' => 'يرجى رفع إيصال التحويل البنكي',
            'receipt_upload.mimes' => 'ملف الإيصال يجب أن يكون صورة أو PDF',
            'receipt_upload.max' => 'حجم ملف الإيصال يجب ألا يتجاوز 10 ميجابايت',
        ];
    }

    public function attributes(): array
    {
        return [
            'company_id' => 'الشركة المستفيدة', 'plan_id' => 'الباقة المطلوبة', 'start_date' => 'تاريخ بداية الاشتراك',
            'end_date' => 'تاريخ نهاية الاشتراك', 'period_months' => 'مدة الاشتراك (بالشهور)', 'coupon_code' => 'كود كوبون الخصم',
            'payment_method' => 'طريقة الدفع', 'payment_status' => 'حالة الدفع الآن', 'payment_type' => 'نوع الدفع',
            'transaction_id' => 'رقم العملية / الحوالة البنكية', 'payment_proof' => 'إثبات الدفع',
            'receipt_upload' => 'إيصال الدفع / الحوالة (مستند)', 'admin_notes' => 'ملاحظات إدارية داخلية',
            'has_discount' => 'تطبيق الخصم', 'discount_value' => 'قيمة الخصم', 'trial_end_date' => 'تاريخ انتهاء التجربة',
            'activate_immediately' => 'التفعيل الفوري',
        ];
    }
}
