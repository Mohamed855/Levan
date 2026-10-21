<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ControlDomains;

use Illuminate\Foundation\Http\FormRequest;

final class StoreControlDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('control_domains.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'domain'    => ['required', 'string', 'max:255', 'unique:control_domains,domain'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'domain.required' => 'حقل النطاق مطلوب.',
            'domain.unique'   => 'هذا النطاق مضاف بالفعل.',
        ];
    }

    public function attributes(): array
    {
        return [
            'domain'    => 'النطاق / الامتداد',
            'is_active' => 'حالة التفعيل',
        ];
    }
}
