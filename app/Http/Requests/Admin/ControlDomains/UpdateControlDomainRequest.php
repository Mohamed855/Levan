<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ControlDomains;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateControlDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('control_domains.update') ?? false;
    }

    public function rules(): array
    {
        $domainId = $this->route('control_domain')?->id ?? $this->route('control_domain');

        return [
            'domain'    => ['required', 'string', 'max:255', Rule::unique('control_domains', 'domain')->ignore($domainId)],
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
