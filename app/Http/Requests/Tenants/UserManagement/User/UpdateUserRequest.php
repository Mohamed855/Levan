<?php

namespace App\Http\Requests\Tenants\UserManagement\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    | Determine if the user is authorized to make this request.
    */
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('users.edit') ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    | Get the validation rules that apply to the request.
    */
    public function rules(): array
    {
        $user = $this->route('user');
        $userId = is_object($user) ? ($user->id ?? $user->getKey()) : ($user ?? $this->input('user_id'));

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
                'regex:/^[\pL\s\-\.]+$/u',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
            'phone' => [
                'required',
                'string',
                'size:10',
                'regex:/^05[0-9]{8}$/',
            ],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif',
                'max:2048',
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Messages
    |--------------------------------------------------------------------------
    | Get custom validation messages for the defined validation rules.
    */
    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب',
            'name.string' => 'الاسم يجب أن يكون نص',
            'name.max' => 'الاسم يجب ألا يزيد عن 255 حرف',
            'name.min' => 'الاسم يجب ألا يقل عن حرفين',
            'name.regex' => 'الاسم يجب أن يحتوي على أحرف فقط',

            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.max' => 'البريد الإلكتروني يجب ألا يزيد عن 255 حرف',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً',

            'password.string' => 'كلمة المرور يجب أن تكون نص',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',

            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.string' => 'رقم الهاتف يجب أن يكون نص',
            'phone.size' => 'رقم الهاتف يجب أن يتكون من 10 أرقام',
            'phone.regex' => 'رقم الهاتف يجب أن يبدأ بـ 05',

            'role_id.required' => 'يجب اختيار الدور',
            'role_id.integer' => 'معرف الدور يجب أن يكون رقماً',
            'role_id.exists' => 'الدور المحدد غير موجود',

            'image.image' => 'الملف يجب أن يكون صورة',
            'image.mimes' => 'الصورة يجب أن تكون من نوع: jpeg, png, jpg, gif',
            'image.max' => 'حجم الصورة يجب ألا يزيد عن 2 ميجابايت',
            'image.dimensions' => 'أبعاد الصورة يجب أن تكون بين 100x100 و 2000x2000 بكسل',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Attributes
    |--------------------------------------------------------------------------
    | Get custom attributes for validator errors.
    */
    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'password_confirmation' => 'تأكيد كلمة المرور',
            'phone' => 'رقم الهاتف',
            'image' => 'الصورة الشخصية',
            'role_id' => 'الدور',

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare Data for Validation
    |--------------------------------------------------------------------------
    | Prepare the data for validation.
    */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
            'phone' => trim((string) $this->phone),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Validated Data
    |--------------------------------------------------------------------------
    | Get the validated data with additional processing.
    */
    public function getValidatedData(): array
    {
        $validated = $this->validated();

        // Remove password if empty (for update)
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        return $validated;
    }
}
