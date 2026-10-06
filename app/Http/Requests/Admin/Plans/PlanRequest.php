<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Plans;

use App\Enums\Admin\Plans\PlanColor;
use App\Enums\Admin\Plans\PlanLimitsMode;
use App\Enums\Admin\Plans\PlanType;
use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Http\Requests\Concerns\WizardFormRequest;
use App\Models\Admin\Plans\Plan;
use Illuminate\Validation\Rule;

class PlanRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $plan = $this->route('plan');
        if (is_numeric($plan)) {
            $plan = Plan::find($plan);
        }

        $code = $this->input('code');
        if ($code !== null) {
            $code = strtoupper(trim((string) $code));
        }

        $limitsMode = (string) $this->input('limits_mode', 'limited');

        $mergeData = [
            'code'        => $code,
            'limits_mode' => $limitsMode,
        ];

        // If editing a plan that already has subscriptions, lock down critical contract columns
        if ($plan instanceof Plan && $plan->hasSubscriptions()) {
            $mergeData['code']                       = $plan->code;
            $mergeData['subscription_billing_cycle'] = $plan->subscription_billing_cycle instanceof SubscriptionBillingCycle
                ? $plan->subscription_billing_cycle->value
                : (string) $plan->subscription_billing_cycle;
            $mergeData['limits_mode']                = $plan->limits_mode instanceof PlanLimitsMode
                ? $plan->limits_mode->value
                : (string) $plan->limits_mode;
        }

        // When limits_mode is unlimited, force numeric limits to null.
        if ($limitsMode === PlanLimitsMode::UNLIMITED->value) {
            $mergeData['included_users']        = null;
            $mergeData['properties_limit']      = null;
            $mergeData['storage_quota_gb']      = null;
            $mergeData['active_auctions_limit'] = null;
        }

        $this->merge($mergeData);
    }

    public function rules(): array
    {
        $plan = $this->route('plan');
        $planId = $plan instanceof Plan ? $plan->id : (is_numeric($plan) ? (int) $plan : null);

        $limitsMode = (string) $this->input('limits_mode', 'limited');
        $billingCycle = (string) $this->input('subscription_billing_cycle', 'monthly');

        $rules = [
            'name'                       => ['required', 'string', 'max:255'],
            'code'                       => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z][A-Z0-9_]*$/',
                Rule::unique('plans', 'code')->ignore($planId),
            ],
            'plan_type'                  => ['required', 'string', Rule::in(PlanType::values())],
            'color'                      => ['required', 'string', Rule::in(PlanColor::values())],
            'description'                => ['nullable', 'string', 'max:1000'],
            'price'                      => ['required', 'numeric', 'min:0'],
            'discount_percentage'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'yearly_renewal_price'       => ['nullable', 'numeric', 'min:0'],
            'subscription_billing_cycle' => ['required', 'string', Rule::in(SubscriptionBillingCycle::values())],
            'available_from'             => ['nullable', 'date'],
            'admin_notes'                => ['nullable', 'string', 'max:2000'],
            'is_active'                  => ['required', 'boolean'],
            'domain_management_enabled'  => ['required', 'boolean'],
            'limits_mode'                => ['required', 'string', Rule::in(PlanLimitsMode::values())],
            'features'                   => ['required', 'array', 'min:1'],
            'features.*'                 => ['required', 'string', 'max:255'],
        ];

        if ($limitsMode === PlanLimitsMode::LIMITED->value) {
            $rules['included_users']           = ['required', 'integer', 'min:1'];
            $rules['properties_limit']         = ['required', 'integer', 'min:1'];
            $rules['storage_quota_gb']         = ['required', 'integer', 'min:1'];
            $rules['active_auctions_limit']    = ['required', 'integer', 'min:1'];
            $rules['extra_user_license_price'] = ['required', 'numeric', 'min:0'];
        } else {
            $rules['included_users']           = ['nullable'];
            $rules['properties_limit']         = ['nullable'];
            $rules['storage_quota_gb']         = ['nullable'];
            $rules['active_auctions_limit']    = ['nullable'];
            $rules['extra_user_license_price'] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'                       => 'اسم الباقة مطلوب.',
            'name.max'                            => 'اسم الباقة يجب ألا يتجاوز 255 حرفاً.',

            'code.required'                       => 'كود الباقة مطلوب.',
            'code.regex'                          => 'كود الباقة يجب أن يبدأ بحرف إنجليزي كبير ويحتوي على أحرف كبيرة وأرقام وشرطة سفلية فقط (مثال: PRO_2026).',
            'code.unique'                         => 'كود الباقة مستخدم مسبقاً، يرجى اختيار كود فريد.',
            'code.max'                            => 'كود الباقة يجب ألا يتجاوز 50 حرفاً.',

            'plan_type.required'                  => 'نوع الباقة مطلوب.',
            'plan_type.in'                        => 'نوع الباقة المحدد غير صالح.',

            'color.required'                      => 'لون الباقة مطلوب.',
            'color.in'                            => 'لون الباقة المحدد غير صالح.',

            'price.required'                      => 'سعر الدورة الأساسية للباقة مطلوب.',
            'price.numeric'                       => 'السعر يجب أن يكون رقماً.',
            'price.min'                           => 'السعر يجب ألا يقل عن 0.',

            'yearly_renewal_price.numeric'        => 'سعر العرض السنوي يجب أن يكون رقماً.',
            'yearly_renewal_price.min'            => 'سعر العرض السنوي لا يمكن أن يقل عن 0.',

            'extra_user_license_price.required'   => 'سعر ترخيص المستخدم الإضافي مطلوب للباقة المحدودة.',
            'extra_user_license_price.numeric'    => 'سعر ترخيص المستخدم الإضافي يجب أن يكون رقماً.',
            'extra_user_license_price.min'        => 'سعر ترخيص المستخدم الإضافي لا يمكن أن يقل عن 0.',

            'discount_percentage.numeric'         => 'نسبة الخصم يجب أن تكون رقماً.',
            'discount_percentage.min'             => 'نسبة الخصم لا يمكن أن تقل عن 0%.',
            'discount_percentage.max'             => 'نسبة الخصم لا يمكن أن تزيد عن 100%.',

            'subscription_billing_cycle.required' => 'دورة الفوترة مطلوبة.',
            'subscription_billing_cycle.in'       => 'دورة الفوترة المحددة غير صالحة.',

            'limits_mode.required'                => 'وضع الحدود مطلوب.',
            'limits_mode.in'                      => 'وضع الحدود المحدد غير صالح.',

            'included_users.required'             => 'عدد المستخدمين مطلوب للباقات المحدودة.',
            'included_users.integer'              => 'عدد المستخدمين يجب أن يكون رقماً صحيحاً.',
            'included_users.min'                  => 'عدد المستخدمين يجب أن يكون 1 على الأقل.',

            'properties_limit.required'           => 'حد العقارات مطلوب للباقات المحدودة.',
            'properties_limit.integer'            => 'حد العقارات يجب أن يكون رقماً صحيحاً.',
            'properties_limit.min'                => 'حد العقارات يجب أن يكون 1 على الأقل.',

            'storage_quota_gb.required'           => 'حصة التخزين مطلوبة للباقات المحدودة.',
            'storage_quota_gb.integer'            => 'حصة التخزين يجب أن تكون رقماً صحيحاً.',
            'storage_quota_gb.min'                => 'حصة التخزين يجب أن تكون 1 جيجابايت على الأقل.',

            'active_auctions_limit.required'      => 'حد المزادات النشطة مطلوب للباقات المحدودة.',
            'active_auctions_limit.integer'       => 'حد المزادات النشطة يجب أن يكون رقماً صحيحاً.',
            'active_auctions_limit.min'           => 'حد المزادات النشطة يجب أن يكون 1 على الأقل.',

            'is_active.required'                  => 'حالة تفعيل الباقة مطلوبة.',
            'domain_management_enabled.required'  => 'حالة إدارة الدومينات مطلوبة.',

            'features.required'                   => 'يجب إضافة ميزة واحدة على الأقل للباقة.',
            'features.min'                        => 'يجب إضافة ميزة واحدة على الأقل للباقة.',
            'features.*.required'                 => 'كل ميزة يجب أن تحتوي على نص صالح.',
            'features.*.max'                      => 'نص الميزة يجب ألا يتجاوز 255 حرفاً.',
        ];
    }
}
