<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerNote;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'notes' => 'required|array|min:1|max:20',
            'notes.*' => 'required|string|min:5|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required' => 'يجب إضافة ملاحظة واحدة على الأقل',
            'notes.array' => 'تنسيق الملاحظات غير صحيح',
            'notes.min' => 'يجب إضافة ملاحظة واحدة على الأقل',
            'notes.max' => 'لا يمكن إضافة أكثر من :max ملاحظة في المرة الواحدة',

            'notes.*.required' => 'الملاحظة مطلوبة',
            'notes.*.string' => 'الملاحظة يجب أن تكون نص',
            'notes.*.min' => 'الملاحظة يجب أن تحتوي على :min أحرف على الأقل',
            'notes.*.max' => 'الملاحظة لا يجب أن تتجاوز :max حرف',
        ];
    }

    public function attributes(): array
    {
        return [
            'notes' => 'الملاحظات',
            'notes.*' => 'الملاحظة',
        ];
    }

    /**
     * تحضير البيانات للتحقق
     */
    protected function prepareForValidation(): void
    {
        $notes = $this->input('notes', []);

        // إزالة الملاحظات الفارغة وتنظيفها
        $cleanedNotes = array_filter($notes, function ($note) {
            return !empty(trim($note));
        });

        // تنظيف المحتوى
        $cleanedNotes = array_map(function ($note) {
            return trim($note);
        }, $cleanedNotes);

        // إعادة ترتيب المفاتيح
        $this->merge([
            'notes' => array_values($cleanedNotes)
        ]);
    }
}
