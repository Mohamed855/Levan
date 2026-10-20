<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Assignments;

use App\Enums\Tenants\Assignments\AssignmentCategory;
use App\Enums\Tenants\Assignments\AssignmentStatus;
use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Validation\Rule;

class StoreAssignmentRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('assignments.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'assignment_number' => ['required', 'string', 'max:100', 'unique:assignments,assignment_number'],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'issuing_entity' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::enum(AssignmentCategory::class)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['required', 'string', 'max:1000'],
            'status' => ['required', Rule::enum(AssignmentStatus::class)],
            'property_ids' => ['required', 'array', 'min:1'],
            'property_ids.*' => ['required', 'integer', 'distinct', Rule::exists('properties', 'id')->where(
                fn ($query) => $query->whereNull('assignment_id')->whereNull('deleted_at'),
            )],
            'assignment_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'assignment_letter' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'general_documents' => ['required', 'array', 'min:1'],
            'general_documents.*' => ['required', 'file', 'max:10240'],
            'additional_attachments' => ['required', 'array', 'min:1'],
            'additional_attachments.*' => ['required', 'file', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'assignment_number.required' => 'رقم الإسناد مطلوب.',
            'assignment_number.unique' => 'رقم الإسناد مسجل مسبقًا.',
            'name.required' => 'اسم الإسناد مطلوب.',
            'issuing_entity.required' => 'الجهة المسندة مطلوبة.',
            'city.required' => 'المدينة مطلوبة.',
            'category.required' => 'تصنيف الإسناد مطلوب.',
            'status.required' => 'حالة الإسناد مطلوبة.',
            'start_date.required' => 'تاريخ البداية مطلوب.',
            'end_date.required' => 'تاريخ النهاية مطلوب.',
            'end_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون مساويًا أو بعد تاريخ البداية.',
            'notes.required' => 'وصف الإسناد مطلوب.',
            'notes.max' => 'وصف الإسناد يجب ألا يتجاوز 1000 حرف.',
            'property_ids.required' => 'يجب اختيار عقار واحد على الأقل.',
            'property_ids.min' => 'يجب اختيار عقار واحد على الأقل.',
            'property_ids.*.exists' => 'أحد العقارات المختارة غير متاح للربط.',
            'assignment_image.required' => 'صورة الإسناد مطلوبة.',
            'assignment_image.image' => 'صورة الإسناد يجب أن تكون صورة صالحة.',
            'assignment_image.mimes' => 'صيغة صورة الإسناد غير مدعومة.',
            'assignment_image.max' => 'حجم صورة الإسناد يجب ألا يتجاوز 2 ميجابايت.',
            'assignment_letter.required' => 'خطاب الإسناد مطلوب.',
            'assignment_letter.mimes' => 'خطاب الإسناد يجب أن يكون ملف PDF.',
            'assignment_letter.max' => 'حجم خطاب الإسناد يجب ألا يتجاوز 10 ميجابايت.',
            'general_documents.required' => 'يجب إرفاق مستند عام واحد على الأقل.',
            'general_documents.min' => 'يجب إرفاق مستند عام واحد على الأقل.',
            'general_documents.*.max' => 'حجم كل مستند عام يجب ألا يتجاوز 10 ميجابايت.',
            'additional_attachments.required' => 'يجب إرفاق مرفق إضافي واحد على الأقل.',
            'additional_attachments.min' => 'يجب إرفاق مرفق إضافي واحد على الأقل.',
            'additional_attachments.*.max' => 'حجم كل مرفق إضافي يجب ألا يتجاوز 10 ميجابايت.',
        ];
    }

    public function attributes(): array
    {
        return [
            'assignment_number' => 'رقم الإسناد',
            'name' => 'اسم الإسناد',
            'issuing_entity' => 'الجهة المسندة',
            'city' => 'المدينة',
            'district' => 'النطاق/الحي',
            'category' => 'تصنيف الإسناد',
            'status' => 'حالة الإسناد',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية',
            'notes' => 'وصف الإسناد',
            'property_ids' => 'العقارات المرتبطة',
            'assignment_image' => 'صورة الإسناد',
            'assignment_letter' => 'خطاب الإسناد',
            'general_documents' => 'المستندات العامة',
            'additional_attachments' => 'المرفقات الإضافية',
        ];
    }
}
