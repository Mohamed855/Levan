<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Faqs;

use Illuminate\Foundation\Http\FormRequest;

final class StoreFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('faq.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'question'  => ['required', 'string', 'max:500'],
            'answer'    => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' => 'حقل السؤال مطلوب.',
            'answer.required'   => 'حقل الإجابة مطلوب.',
        ];
    }

    public function attributes(): array
    {
        return [
            'question'  => 'السؤال',
            'answer'    => 'الإجابة',
            'is_active' => 'حالة التفعيل',
        ];
    }
}
