<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\PublicRequests;

use Illuminate\Foundation\Http\FormRequest;

final class RejectTrialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->hasPermissionTo('public_requests.process') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'سبب الرفض مطلوب.',
            'reason.min'      => 'يجب أن يكون سبب الرفض 5 أحرف على الأقل.',
            'reason.max'      => 'يجب ألا يتجاوز سبب الرفض 1000 حرف.',
        ];
    }

    public function attributes(): array
    {
        return [
            'reason' => 'سبب الرفض',
        ];
    }
}
