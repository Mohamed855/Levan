<?php

namespace App\Http\Requests\Admin\AdminMangment\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('users.edit') ?? false;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = is_object($user) ? $user->id : ($user ?? $this->input('user_id'));

        return [
            'name' => ['required', 'string', 'max:255', 'min:2', 'regex:/^[\pL\s\-\.]+$/u'],

            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('admin_users', 'email')->ignore($userId),
            ],

            'phone' => ['required', 'string', 'size:10', 'regex:/^05[0-9]{8}$/'],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where('guard_name', 'admin'),
            ],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240', 'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000'],
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

            // phone
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.string' => 'رقم الهاتف يجب أن يكون نص',
            'phone.size' => 'رقم الهاتف يجب أن يتكون من 10 أرقام',
            'phone.regex' => 'رقم الهاتف يجب أن يبدأ بـ 05',

            // role
            'role_id.required' => 'يجب اختيار الدور',
            'role_id.integer' => 'معرف الدور يجب أن يكون رقماً',
            'role_id.exists' => 'الدور المحدد غير موجود',

            // image
            'image.image' => 'الملف يجب أن يكون صورة',
            'image.mimes' => 'الصورة يجب أن تكون من نوع: jpeg, png, jpg, gif',
            'image.max' => 'حجم الصورة يجب ألا يزيد عن 10 ميجابايت',
            'image.dimensions' => 'أبعاد الصورة يجب أن تكون بين 100x100 و 2000x2000 بكسل',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'الاسم الكامل',
            'email' => 'البريد الإلكتروني',
            'phone' => 'رقم الهاتف',
            'role_id' => 'الدور',
            'image' => 'الصورة الشخصية',
        ];
    }
}
