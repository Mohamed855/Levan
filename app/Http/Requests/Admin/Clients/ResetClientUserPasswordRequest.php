<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Clients;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class ResetClientUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user('admin')?->can('clients.edit')
            || $this->user('admin')?->can('companies.edit'));
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string', 'max:255', Rule::exists('mysql.tenants', 'id')->whereNull('deleted_at')],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'يرجى تحديد العميل.',
            'tenant_id.string' => 'معرف العميل غير صالح.',
            'tenant_id.max' => 'معرف العميل غير صالح.',
            'tenant_id.exists' => 'العميل المحدد غير موجود أو مؤرشف.',
            'email.required' => 'يرجى إدخال البريد الإلكتروني للمستخدم.',
            'email.email' => 'يرجى إدخال بريد إلكتروني صحيح.',
            'email.max' => 'يجب ألا يزيد البريد الإلكتروني عن 255 حرفًا.',
            'password.required' => 'يرجى إدخال كلمة المرور الجديدة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ];
    }

    public function attributes(): array
    {
        return [
            'tenant_id' => 'العميل',
            'email' => 'البريد الإلكتروني للمستخدم',
            'password' => 'كلمة المرور الجديدة',
            'password_confirmation' => 'تأكيد كلمة المرور',
        ];
    }
}
