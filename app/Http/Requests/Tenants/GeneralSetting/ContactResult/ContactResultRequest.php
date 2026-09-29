<?php

namespace App\Http\Requests\Tenants\GeneralSetting\ContactResult;

use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use Illuminate\Foundation\Http\FormRequest;

class ContactResultRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Determine if User is Authorized
    |--------------------------------------------------------------------------
    | التحقق من صلاحية المستخدم لإرسال الطلب
    */
    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    | قواعد التحقق من صحة البيانات المرسلة
    */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:contact_results,name' . ($this->isMethod('PUT') ? ',' . $this->route('contactResult')->id : ''),
            // 'category' => 'required|string|in:' . implode(',', array_keys(ContactResult::getCategories())),
            'status' => 'sometimes|boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Messages
    |--------------------------------------------------------------------------
    | رسائل التحقق المخصصة باللغة العربية
    */
    public function messages(): array
    {
        return [
            'name.required' => 'حقل اسم النتيجة مطلوب',
            'name.string' => 'حقل اسم النتيجة يجب أن يكون نصاً',
            'name.max' => 'حقل اسم النتيجة يجب ألا يزيد عن 255 حرف',
            'name.unique' => 'اسم النتيجة موجود بالفعل في النظام',
            // 'category.required' => 'حقل تصنيف النتيجة مطلوب',
            // 'category.string' => 'حقل تصنيف النتيجة يجب أن يكون نصاً',
            // 'category.in' => 'قيمة حقل التصنيف غير صالحة',
            'status.boolean' => 'حقل الحالة يجب أن يكون قيمة منطقية (صحيح/خطأ)',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    | أسماء الحقول المخصصة باللغة العربية
    */
    public function attributes(): array
    {
        return [
            'name' => 'اسم النتيجة',
            // 'category' => 'تصنيف النتيجة',
            'status' => 'الحالة',
        ];
    }
}
