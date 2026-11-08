<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\IssuingEntities;

use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Validation\Rule;

class StoreIssuingEntityRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('issuing_entities.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('issuing_entities', 'name')->whereNull('deleted_at'),
            ],
            'image' => [
                'nullable',
                Rule::when(
                    $this->hasFile('image'),
                    ['image', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
                    ['string', 'max:1000']
                ),
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
