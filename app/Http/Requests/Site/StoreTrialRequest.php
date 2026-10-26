<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Validation\Rule;

class StoreTrialRequest extends SubscriptionCheckoutRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['coupon_code']);
        $rules['billing_email'][] = Rule::unique('trial_requests', 'customer_email')->where(fn ($query) => $query->where('status', 'pending')->whereNotNull('email_verified_at'));
        $rules['billing_commercial_number'][] = Rule::unique('trial_requests', 'commercial_number')->where('status', 'pending');

        return $rules;
    }
}
