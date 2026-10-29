<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\DeleteBulk;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MassDeleteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('ids'))) {
            $this->merge(['ids' => array_map('trim', explode(',', $this->input('ids')))]);
        }
    }

    public function authorize(): bool
    {
        $model = $this->input('model');
        $definition = is_string($model) ? (config('tenant-mass-delete', [])[$model] ?? null) : null;

        return $definition !== null && ($this->user('tenant')?->can($definition['permission']) ?? false);
    }

    public function rules(): array
    {
        return [
            'model' => ['required', 'string', Rule::in(array_keys(config('tenant-mass-delete', [])))],
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'model.required' => 'نوع السجل مطلوب.',
            'model.in' => 'نوع السجل غير مدعوم للحذف الجماعي.',
            'ids.required' => 'حدد سجلاً واحداً على الأقل.',
            'ids.array' => 'يجب إرسال معرفات السجلات في قائمة.',
            'ids.max' => 'يمكن حذف مائة سجل كحد أقصى في العملية الواحدة.',
            'ids.*.integer' => 'أحد معرفات السجلات غير صالح.',
            'ids.*.min' => 'أحد معرفات السجلات غير صالح.',
            'ids.*.distinct' => 'لا يجوز تكرار معرف السجل في العملية الواحدة.',
        ];
    }

    public function attributes(): array
    {
        return ['model' => 'نوع السجل', 'ids' => 'السجلات المحددة', 'ids.*' => 'معرف السجل'];
    }
}
