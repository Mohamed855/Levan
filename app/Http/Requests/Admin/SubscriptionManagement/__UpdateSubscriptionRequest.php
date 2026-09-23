<?php

namespace App\Http\Requests\Admin\SubscriptionManagement;

use App\Enums\Admin\Billing\PaymentMethod;
use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class __UpdateSubscriptionRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // معلومات العميل والباقة
            'licenses_quota'    => ['required', 'integer', 'min:0', 'max:10000'], // التراخيص
            'status'            => ['required'],
        ];
    }


    public function attributes(): array
    {
        return [
            'licenses_quota'    => 'عدد التراخيص الإضافية',
            'status'            => 'حالة الاشتراك',
        ];
    }


    public function messages(): array
    {
        return [

            'licenses_quota.required'       => 'يجب إدخال عدد التراخيص',
            'licenses_quota.integer'        => 'عدد التراخيص يجب أن يكون رقماً صحيحاً',
            'licenses_quota.min'            => 'عدد التراخيص لا يمكن أن يكون أقل من 0',
            'licenses_quota.max'            => 'عدد التراخيص لا يمكن أن يتجاوز 10000',

            'status.required'               => 'يجب اختيار حالة الاشتراك ',
            'status.in'                     => 'حالة الاشتراك  المختارة غير صحيحة',
        ];
    }
}
