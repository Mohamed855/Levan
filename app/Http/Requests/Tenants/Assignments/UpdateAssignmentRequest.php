<?php

namespace App\Http\Requests\Tenants\Assignments;

use App\Enums\Tenants\Assignments\AssignmentCategory;
use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Validation\Rule;

class UpdateAssignmentRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('assignments.edit') ?? false;
    }

    public function rules(): array
    {
        $assignmentId = $this->route('assignment')?->id ?? $this->route('assignment');

        return [
            'assignment_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('assignments', 'assignment_number')->ignore($assignmentId),
            ],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'issuing_entity' => ['required', 'string', 'max:255'],
            'assignment_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::enum(AssignmentCategory::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'assignment_number.required' => 'رقم الإسناد مطلوب.',
            'assignment_number.unique' => 'رقم الإسناد مسجل مسبقاً.',
            'assignment_number.max' => 'رقم الإسناد يجب ألا يتجاوز 100 حرف.',
            'name.required' => 'اسم الإسناد مطلوب.',
            'name.max' => 'اسم الإسناد يجب ألا يتجاوز 255 حرف.',
            'issuing_entity.required' => 'الجهة المسندة مطلوبة.',
            'issuing_entity.max' => 'اسم الجهة المسندة يجب ألا يتجاوز 255 حرف.',
            'assignment_image.image' => 'صورة الإسناد يجب أن تكون صورة صالحة.',
            'assignment_image.mimes' => 'صيغة صورة الإسناد غير مدعومة.',
            'assignment_image.max' => 'حجم صورة الإسناد يجب ألا يتجاوز 10 ميجابايت.',
            'category.required' => 'تصنيف الإسناد مطلوب.',
            'category.in' => 'التصنيف المختار غير صالح.',
            'start_date.date' => 'تاريخ الإسناد غير صالح.',
            'end_date.date' => 'تاريخ الانتهاء غير صالح.',
            'end_date.after_or_equal' => 'تاريخ الانتهاء يجب أن يكون مساوياً أو بعد تاريخ البداية.',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف.',
        ];
    }

    public function attributes(): array
    {
        return [
            'assignment_number' => 'رقم الإسناد',
            'name' => 'اسم الإسناد',
            'issuing_entity' => 'الجهة المسندة',
            'assignment_image' => 'صورة الإسناد',
            'city' => 'المدينة',
            'district' => 'النطاق/الحي',
            'category' => 'تصنيف الإسناد',
            'start_date' => 'تاريخ الإسناد',
            'end_date' => 'تاريخ الانتهاء',
            'notes' => 'الملاحظات',
        ];
    }
}
