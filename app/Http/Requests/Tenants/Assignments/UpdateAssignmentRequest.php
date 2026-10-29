<?php

declare(strict_types=1);

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
        $assignmentParam = $this->route('assignment');
        $assignmentId = is_object($assignmentParam) ? (int) $assignmentParam->id : (int) $assignmentParam;

        return [
            'assignment_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('assignments', 'assignment_number')->ignore($assignmentId),
            ],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'issuing_entity' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::enum(AssignmentCategory::class)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['required', 'string', 'max:1000'],
            'property_ids' => ['nullable', 'array'],
            'property_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('properties', 'id')->where(
                    fn ($query) => $query->where(function ($q) use ($assignmentId) {
                        $q->whereNull('assignment_id')->orWhere('assignment_id', $assignmentId);
                    })->whereNull('deleted_at'),
                ),
            ],
            'assignment_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
            'assignment_letter' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'general_documents' => ['nullable', 'array'],
            'general_documents.*' => ['nullable', 'file', 'max:10240'],
            'additional_attachments' => ['nullable', 'array'],
            'additional_attachments.*' => ['nullable', 'file', 'max:10240'],
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
            'city.required' => 'المدينة مطلوبة.',
            'category.required' => 'تصنيف الإسناد مطلوب.',
            'start_date.required' => 'تاريخ البداية مطلوب.',
            'start_date.date' => 'تاريخ البداية غير صالح.',
            'end_date.required' => 'تاريخ النهاية مطلوب.',
            'end_date.date' => 'تاريخ النهاية غير صالح.',
            'end_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون مساوياً أو بعد تاريخ البداية.',
            'notes.required' => 'وصف الإسناد مطلوب.',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف.',
            'assignment_image.image' => 'صورة الإسناد يجب أن تكون صورة صالحة.',
            'assignment_image.mimes' => 'صيغة صورة الإسناد غير مدعومة.',
            'assignment_image.max' => 'حجم صورة الإسناد يجب ألا يتجاوز 10 ميجابايت.',
            'assignment_letter.mimes' => 'خطاب الإسناد يجب أن يكون ملف PDF.',
            'assignment_letter.max' => 'حجم خطاب الإسناد يجب ألا يتجاوز 10 ميجابايت.',
        ];
    }
}
