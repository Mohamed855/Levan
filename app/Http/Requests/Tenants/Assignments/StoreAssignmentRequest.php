<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Assignments;

use App\Enums\Tenants\Assignments\AssignmentCategory;
use App\Http\Requests\Concerns\WizardFormRequest;
use App\Models\Tenants\Properties\Property;
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
            'property_ids' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail): void {
                    $categoryValue = $this->input('category');
                    $category = AssignmentCategory::tryFrom((string) $categoryValue);
                    if (! $category) {
                        return;
                    }

                    $allowedTypes = $category->allowedPropertyTypes();
                    $invalidProperties = Property::query()
                        ->whereIn('id', (array) $value)
                        ->whereNotIn('type', $allowedTypes)
                        ->get(['id', 'name', 'type', 'property_number']);

                    if ($invalidProperties->isNotEmpty()) {
                        $invalidDetails = $invalidProperties
                            ->map(fn (Property $p) => $p->name.' ('.$p->type_label.')')
                            ->join('، ');
                        $fail("العقارات التالية: [{$invalidDetails}] لا تتطابق مع تصنيف الإسناد المختار ({$category->label()}). يرجى اختيار عقارات متوافقة مع التصنيف فقط.");
                    }
                },
            ],
            'property_ids.*' => ['required', 'integer', 'distinct', Rule::exists('properties', 'id')->where(
                fn ($query) => $query->whereNull('assignment_id')->whereNull('deleted_at'),
            )],
            'assignment_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
            'assignment_letter' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'general_documents' => ['required', 'array', 'min:1'],
            'general_documents.*' => ['required', 'file', 'max:10240'],
            'additional_attachments' => ['nullable', 'array'],
            'additional_attachments.*' => ['nullable', 'file', 'max:10240'],
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
            'assignment_image.max' => 'حجم صورة الإسناد يجب ألا يتجاوز 10 ميجابايت.',
            'assignment_letter.required' => 'خطاب الإسناد مطلوب.',
            'assignment_letter.mimes' => 'خطاب الإسناد يجب أن يكون ملف PDF.',
            'assignment_letter.max' => 'حجم خطاب الإسناد يجب ألا يتجاوز 10 ميجابايت.',
            'general_documents.required' => 'يجب إرفاق مستند عام واحد على الأقل.',
            'general_documents.min' => 'يجب إرفاق مستند عام واحد على الأقل.',
            'general_documents.*.max' => 'حجم كل مستند عام يجب ألا يتجاوز 10 ميجابايت.',
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
