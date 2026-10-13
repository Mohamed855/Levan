<?php

namespace App\Http\Requests\Admin\SubscriptionManagement;

use App\Enums\Admin\Billing\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId = $this->route('tenant') ? $this->route('tenant')->id : null;

        return [
            'company_name' => 'required|string|min:2|max:255',
            'commercial_number' => ['required', 'digits:10', 'unique:tenants,commercial_number,'.$tenantId],
            'tax_number' => ['required', 'string', 'max:255'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'address' => ['required', 'string', 'max:1000'],
            'name' => 'required|string|min:2|max:255',
            'phone' => ['required', 'regex:/^05[0-9]{8}$/'],
            'email' => ['required', 'email', 'max:255'],
            'password' => $tenantId ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed',

            'trial_end_date' => 'required_if:payment_type,trial|nullable|date|after:today',

            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'payment_type' => ['required', 'string', 'in:trial,paid,unpaid'], //  حالة الدفع تم الدفع - لم يتم الدفع
            'payment_method' => ['nullable', 'required_if:payment_type,paid', 'string', Rule::in(PaymentMethod::values())],
            'receipt_upload' => ['nullable', 'required_if:payment_method,bank_transfer', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'has_discount' => ['nullable', 'string', 'in:yes,no'], // التسوية (الخصم)
            'discount_value' => ['nullable', 'required_if:has_discount,yes', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }

    public function messages()
    {
        return [
            // company_name
            'company_name.required' => 'اسم الشركة مطلوب',
            'company_name.string' => 'اسم الشركة يجب أن يكون نصاً',
            'company_name.min' => 'اسم الشركة يجب أن يكون حرفين على الأقل',
            'company_name.max' => 'اسم الشركة يجب ألا يتجاوز 255 حرفاً',

            // commercial_number
            'commercial_number.required' => 'رقم السجل التجاري مطلوب',
            'commercial_number.digits' => 'رقم السجل التجاري يجب أن يتكون من 10 أرقام',
            'commercial_number.unique' => 'رقم السجل التجاري مسجل مسبقاً',

            'tax_number.required' => 'الرقم الضريبي مطلوب',
            'city_id.required' => 'المدينة مطلوبة',
            'city_id.exists' => 'المدينة المختارة غير موجودة',
            'address.required' => 'العنوان مطلوب',

            // name
            'name.required' => 'اسم المستخدم مطلوب',
            'name.string' => 'اسم المستخدم يجب أن يكون نصاً',
            'name.min' => 'اسم المستخدم يجب أن يكون حرفين على الأقل',
            'name.max' => 'اسم المستخدم يجب ألا يتجاوز 255 حرفاً',

            // phone
            'phone.required' => 'رقم الجوال مطلوب',
            'phone.regex' => 'رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام',

            // email
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.max' => 'البريد الإلكتروني يجب ألا يتجاوز 255 حرفاً',

            // password
            'password.required' => 'كلمة المرور مطلوبة',
            'password.string' => 'كلمة المرور يجب أن تكون نصاً',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'كلمة المرور وتأكيدها غير متطابقتين',

            'plan_id.required' => 'يجب اختيار الباقة',
            'plan_id.exists' => 'الباقة المختارة غير موجودة',

            'payment_type.required' => 'يجب اختيار حالة الدفع',
            'payment_type.in' => 'حالة الدفع المختارة غير صحيحة',

            'payment_method.required' => 'يجب اختيار وسيلة الدفع',
            'payment_method.in' => 'وسيلة الدفع المختارة غير صحيحة',

            'receipt_upload.required_if' => 'يجب رفع إيصال التحويل البنكي عند اختيار الدفع بالتحويل البنكي',
            'receipt_upload.file' => 'يجب أن يكون الإيصال ملفاً صحيحاً',
            'receipt_upload.mimes' => 'يجب أن يكون الإيصال بصيغة: jpg, jpeg, png, أو pdf',
            'receipt_upload.max' => 'حجم الإيصال يجب ألا يتجاوز 5 ميجابايت',

            'has_discount.required' => 'يجب تحديد ما إذا كان هناك تسوية أم لا',
            'has_discount.in' => 'قيمة التسوية المختارة غير صحيحة',

            'discount_value.required_if' => 'يجب إدخال قيمة التسوية',
            'discount_value.numeric' => 'قيمة التسوية يجب أن تكون رقماً',
            'discount_value.min' => 'قيمة التسوية لا يمكن أن تكون أقل من 0',
            'discount_value.max' => 'قيمة التسوية لا يمكن أن تتجاوز 999,999.99',
        ];
    }

    public function attributes()
    {
        return [
            'company_name' => 'اسم الشركة',
            'commercial_number' => 'رقم السجل التجاري',
            'tax_number' => 'الرقم الضريبي',
            'city_id' => 'المدينة',
            'address' => 'العنوان',
            'name' => 'اسم المستخدم',
            'phone' => 'رقم الجوال',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'password_confirmation' => 'تأكيد كلمة المرور',

            'plan_id' => 'الباقة',
            'payment_type' => 'حالة الدفع',
            'payment_method' => 'وسيلة الدفع',
            'receipt_upload' => 'إيصال التحويل البنكي',
            'has_discount' => 'التسوية',
            'discount_value' => 'قيمة التسوية',
        ];
    }
}
