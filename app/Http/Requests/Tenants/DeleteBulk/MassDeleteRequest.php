<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\DeleteBulk;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MassDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant') !== null;
    }

    public function rules(): array
    {
        return [
            'model' => ['required', 'string', Rule::in(array_keys(config('models', [])))],
            'ids' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'model.required' => 'نوع السجل مطلوب.',
            'model.in' => 'نوع السجل غير مدعوم للحذف الجماعي.',
            'ids.required' => 'حدد سجلاً واحداً على الأقل.',
        ];
    }
}
