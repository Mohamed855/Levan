<?php

namespace App\Http\Requests\Admin\VersionTesters;


use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Admin\Version\VersionTesterType;
use Illuminate\Validation\Rule;

class VersionTesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $testerId = $this->route('version_tester') ? $this->route('version_tester')->id : null;

        return [
            'tenant_id'  => ['required', 'exists:tenants,id', 'unique:version_testers,tenant_id,' . $testerId],
            'type'       => ['required', Rule::in(array_column(VersionTesterType::cases(), 'value'))],
            'is_active'  => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'يرجى اختيار العميل (التينانت)',
            'tenant_id.exists'   => 'العميل المحدد غير موجود',
            'tenant_id.unique'   => 'هذا العميل مسجل بالفعل ضمن فريق اختبار الإصدارات',

            'type.required' => 'يرجى تحديد نوع العميل',
            'type.in'       => 'نوع العميل غير صحيح',

            'is_active.required' => 'يرجى تحديد حالة العميل',
            'is_active.boolean'  => 'قيمة الحالة غير صالحة',
        ];
    }
}
