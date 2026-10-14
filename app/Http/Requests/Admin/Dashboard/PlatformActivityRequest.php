<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlatformActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('dashboard.main') ?? false;
    }

    public function rules(): array
    {
        return [
            'range' => ['nullable', 'string', Rule::in(['1month', '3months', '6months', '1year'])],
        ];
    }

    public function messages(): array
    {
        return [
            'range.in' => 'نطاق عرض النشاط غير صالح.',
        ];
    }

    public function attributes(): array
    {
        return [
            'range' => 'نطاق النشاط',
        ];
    }
}
