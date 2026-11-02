<?php

namespace App\Http\Requests\Tenants\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class SubmitTicketSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('tickets.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'solved' => ['required', 'boolean'],
            'agent_rating' => ['nullable', 'integer', 'between:1,5'],
            'system_rating' => ['nullable', 'integer', 'between:1,5'],
            'solution_rating' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'solved.required' => 'يرجى تحديد ما إذا تم حل المشكلة.',
            'solved.boolean' => 'قيمة حالة الحل غير صالحة.',
            'agent_rating.between' => 'يجب أن يكون التقييم بين نجمة واحدة وخمس نجوم.',
            'system_rating.between' => 'يجب أن يكون التقييم بين نجمة واحدة وخمس نجوم.',
            'solution_rating.between' => 'يجب أن يكون التقييم بين نجمة واحدة وخمس نجوم.',
            'comment.max' => 'لا يمكن أن تتجاوز الملاحظات 1000 حرف.',
        ];
    }

    public function attributes(): array
    {
        return [
            'solved' => 'شاركنا تجربتك! هل تم حل مشكلتك بنجاح؟',
            'agent_rating' => 'ما تقييمك لأداء موظف الدعم الفني؟',
            'system_rating' => 'ما تقييمك للنظام؟',
            'solution_rating' => 'ما تقييمك لجودة الحل المقدم؟',
            'comment' => 'ملاحظات إضافية',
        ];
    }
}
