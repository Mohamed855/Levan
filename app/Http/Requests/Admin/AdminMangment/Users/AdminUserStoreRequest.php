<?php

namespace App\Http\Requests\Admin\AdminMangment\Users;

use App\Http\Requests\Concerns\WizardFormRequest;

class AdminUserStoreRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'min:2', 'regex:/^[\pL\s\-\.]+$/u'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:admin_users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'size:10', 'regex:/^05[0-9]{8}$/'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048', 'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب',
            'name.string' => 'الاسم يجب أن يكون نص',
            'name.max' => 'الاسم يجب ألا يزيد عن 255 حرف',
            'name.min' => 'الاسم يجب ألا يقل عن حرفين',
            'name.regex' => 'الاسم يجب أن يحتوي على أحرف فقط',

            // email
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.max' => 'البريد الإلكتروني يجب ألا يزيد عن 255 حرف',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً',

            // password
            'password.required' => 'كلمة المرور مطلوبة',
            'password.string' => 'كلمة المرور يجب أن تكون نص',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',

            // phone
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.string' => 'رقم الهاتف يجب أن يكون نص',
            'phone.size' => 'رقم الهاتف يجب أن يتكون من 10 أرقام',
            'phone.regex' => 'رقم الهاتف يجب أن يبدأ بـ 05',

            // image
            'image.image' => 'الملف يجب أن يكون صورة',
            'image.mimes' => 'الصورة يجب أن تكون من نوع: jpeg, png, jpg, gif',
            'image.max' => 'حجم الصورة يجب ألا يزيد عن 2 ميجابايت',
            'image.dimensions' => 'أبعاد الصورة يجب أن تكون بين 100x100 و 2000x2000 بكسل',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'password_confirmation' => 'تأكيد كلمة المرور',
            'phone' => 'رقم الهاتف',
            'image' => 'الصورة الشخصية',
        ];
    }
}
