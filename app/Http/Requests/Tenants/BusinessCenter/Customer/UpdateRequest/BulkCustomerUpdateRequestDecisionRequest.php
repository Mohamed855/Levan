<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\UpdateRequest;

use Illuminate\Foundation\Http\FormRequest;

class BulkCustomerUpdateRequestDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [
            'request_ids' => ['required', 'array', 'min:1', 'max:100'],
            'request_ids.*' => ['required', 'integer', 'distinct', 'exists:customer_update_requests,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_ids.required' => 'يجب تحديد طلب واحد على الأقل.',
            'request_ids.array' => 'قائمة الطلبات غير صالحة.',
            'request_ids.min' => 'يجب تحديد طلب واحد على الأقل.',
            'request_ids.max' => 'لا يمكن معالجة أكثر من :max طلبات في العملية الواحدة.',
            'request_ids.*.exists' => 'أحد طلبات التحديث المحددة غير موجود.',
            'reason.max' => 'سبب الرفض لا يجب أن يتجاوز :max حرفاً.',
        ];
    }

    public function attributes(): array
    {
        return ['request_ids' => 'طلبات التحديث', 'reason' => 'سبب الرفض'];
    }
}
