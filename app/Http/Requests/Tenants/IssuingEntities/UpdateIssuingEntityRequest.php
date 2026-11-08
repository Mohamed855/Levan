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

    protected function prepareForValidation(): void
    {
        if (! $this->hasFile('image') && $this->filled('existing_image')) {
            $this->merge(['image' => $this->input('existing_image')]);
        }
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
            'image' => [
                'nullable',
                Rule::when(
                    $this->hasFile('image'),
                    ['image', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
                    ['string', 'max:1000']
                ),
            ],
            'remove_image' => ['nullable', 'boolean'],
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
            'image.image' => 'يجب أن يكون الملف المرفوع صورة صالحة.',
            'image.mimes' => 'يجب أن تكون الصورة بصيغة: JPG, JPEG, PNG, WEBP, SVG.',
            'image.max' => 'حجم الصورة لا يجب أن يتجاوز 10 ميجابايت.',
            'is_active.boolean' => 'حالة التفعيل غير صالحة.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم الجهة المسندة',
            'image' => 'صورة أو شعار الجهة المسندة',
            'is_active' => 'الحالة',
        ];
    }
}
