<?php

namespace App\Http\Requests\Admin\SubscriptionManagement;

use App\Enums\Admin\Billing\PaymentMethod;
use App\Http\Requests\Concerns\WizardFormRequest;
use App\Models\Admin\Plans\Plan;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class FinalizeCompanySubscriptionRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'min:2', 'max:255'],
            'commercial_number' => ['required', 'digits:10', Rule::unique('tenants', 'commercial_number')],
            'tax_number' => ['required', 'string', 'max:255'], 'city_id' => ['required', 'exists:cities,id'],
            'address' => ['required', 'string', 'max:1000'],
            'name' => ['required', 'string', 'min:2', 'max:255'], 'phone' => ['required', 'regex:/^05[0-9]{8}$/'],
            'email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string', 'min:8', 'confirmed'],
            'plan_id' => ['required', 'exists:plans,id'], 'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'total_users' => ['required', 'integer', 'min:1'], 'activate_immediately' => ['required', 'boolean'],
            'payment_type' => ['required', 'in:trial,paid,unpaid'],
            'payment_method' => ['nullable', 'required_if:payment_type,paid', Rule::in(PaymentMethod::values())],
            'receipt_upload' => ['nullable', 'required_if:payment_method,bank_transfer', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'has_discount' => ['nullable', 'required_unless:payment_type,trial', 'in:yes,no'],
            'discount_value' => ['nullable', 'required_if:has_discount,yes', 'numeric', 'min:0'],
            'trial_end_date' => ['nullable', 'required_if:payment_type,trial', 'date', 'after:today'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if (! $this->filled('plan_id') || ! $this->filled('start_date')) {
                return;
            }
            $plan = Plan::find($this->integer('plan_id'));
            if (! $plan) {
                return;
            }
            if ($this->integer('total_users') < $plan->included_users) {
                $validator->errors()->add('total_users', 'لا يمكن أن يقل عدد المستخدمين عن المستخدمين المشمولين في الباقة.');
            }
            if ($plan->subscription_duration_type === 'unlimited' && ! $this->filled('end_date')) {
                $validator->errors()->add('end_date', 'تاريخ النهاية مطلوب للباقة غير محددة المدة.');
            }
            if ($plan->subscription_duration_type === 'limited') {
                $expected = $plan->isTrial()
                    ? Carbon::parse($this->start_date)->addDays((int) config('trial.days', 7))->toDateString()
                    : Carbon::parse($this->start_date)->{'add'.($plan->subscription_billing_cycle === 'monthly' ? 'Month' : 'Year')}()->toDateString();
                if ($this->filled('end_date') && Carbon::parse($this->end_date)->toDateString() !== $expected) {
                    $validator->errors()->add('end_date', 'تاريخ النهاية لا يطابق مدة الباقة المختارة.');
                }
            }
        }];
    }
}
