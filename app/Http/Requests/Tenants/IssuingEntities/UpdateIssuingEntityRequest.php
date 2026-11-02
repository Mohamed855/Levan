<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\IssuingEntities;

use App\Http\Requests\Concerns\WizardFormRequest;
use App\Models\Tenants\Assignments\IssuingEntity;
use Illuminate\Validation\Rule;

class UpdateIssuingEntityRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('issuing_entities.edit') ?? false;
    }

    public function rules(): array
    {
        $entityId = $this->route('issuing_entity') instanceof IssuingEntity
            ? $this->route('issuing_entity')->id
            : $this->route('issuing_entity');

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('issuing_entities', 'name')
                    ->ignore($entityId)
                    ->whereNull('deleted_at'),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الجهة المسندة مطلوب.',
            'name.string' => 'يجب أن يكون اسم الجهة المسندة نصًا.',
            'name.min' => 'اسم الجهة المسندة يجب ألا يقل عن حرفين.',
            'name.max' => 'اسم الجهة المسندة يجب ألا يتجاوز 255 حرفًا.',
            'name.unique' => 'اسم الجهة المسندة مسجل مسبقًا.',
            'is_active.boolean' => 'حالة التفعيل غير صالحة.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم الجهة المسندة',
            'is_active' => 'الحالة',
        ];
    }
}
