<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

abstract class FaqRequest extends FormRequest
{
    abstract public function authorize(): bool;

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' => 'يرجى إدخال السؤال.',
            'question.string' => 'يجب أن يكون السؤال نصًا.',
            'question.max' => 'يجب ألا يزيد السؤال عن 255 حرفًا.',
            'answer.required' => 'يرجى إدخال الإجابة.',
            'answer.string' => 'يجب أن تكون الإجابة نصًا.',
            'answer.max' => 'يجب ألا تزيد الإجابة عن 5000 حرف.',
            'is_active.boolean' => 'حالة التفعيل غير صالحة.',
        ];
    }

    public function attributes(): array
    {
        return ['question' => 'السؤال', 'answer' => 'الإجابة', 'is_active' => 'حالة التفعيل'];
    }
}
