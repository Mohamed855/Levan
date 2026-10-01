<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Plans;

use Illuminate\Foundation\Http\FormRequest;

class PlanReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('plans.reorder') ?? false;
    }

    public function rules(): array
    {
        return [
            'order'              => ['required', 'array', 'min:1'],
            'order.*.id'         => ['required', 'integer', 'exists:plans,id'],
            'order.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'order.required'              => 'بيانات الترتيب مطلوبة.',
            'order.array'                 => 'تنسيق بيانات الترتيب غير صالح.',
            'order.*.id.exists'           => 'إحدى الباقات المحددة غير موجودة.',
            'order.*.sort_order.required' => 'حقل الترتيب مطلوب لكل باقة.',
            'order.*.sort_order.integer'  => 'قيمة الترتيب يجب أن تكون رقماً صحيحاً.',
        ];
    }
}
