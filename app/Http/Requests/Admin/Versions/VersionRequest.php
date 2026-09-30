<?php

namespace App\Http\Requests\Admin\Versions;


use App\Enums\Admin\Version\VersionStage;
use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Validation\Rule;

class VersionRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $versionId = $this->route('version') ? $this->route('version')->id : null;

        return [
            'version'      => ['required', 'string', 'max:50', 'regex:/^v?\d+\.\d+\.\d+$/', 'unique:versions,version,' . $versionId],
            'changelog'    => ['nullable', 'string', 'max:2000'],
            'file_path'    => ['required', 'string', 'max:255', 'regex:/^(?:[\/]?[A-Za-z0-9_\-\/\.]+)$/'],
            'stage'        => ['required', Rule::in(VersionStage::values())],
            'released_at'  => ['nullable', 'date', 'before_or_equal:today'],
            'is_active'    => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'version.required'  => 'رقم الإصدارة مطلوب',
            'version.string'    => 'رقم الإصدارة يجب أن يكون نصًا',
            'version.max'       => 'رقم الإصدارة يجب ألا يتجاوز 50 حرفًا',
            'version.regex'     => 'صيغة رقم الإصدارة غير صحيحة، استخدم مثل: v1.0.0 أو 2.3.1',
            'version.unique'    => 'هذا الرقم مستخدم لإصدارة أخرى بالفعل',

            'name.required'     => 'اسم الإصدارة مطلوب',
            'name.max'          => 'اسم الإصدارة يجب ألا يتجاوز 100 حرف',

            'description.max'   => 'الوصف يجب ألا يتجاوز 500 حرف',
            'changelog.max'     => 'سجل التغييرات يجب ألا يتجاوز 2000 حرف',

            'file_path.required' => 'مسار الإصدارة مطلوب',
            'file_path.string'   => 'مسار الإصدارة يجب أن يكون نصًا',
            'file_path.max'      => 'المسار يجب ألا يتجاوز 255 حرفًا',
            'file_path.regex'    => 'المسار يحتوي على رموز غير مسموح بها. استخدم أحرفًا، أرقامًا، شرطات أو شرطات سفلية فقط',

            'stage.required' => 'يرجى اختيار مرحلة الإصدارة',
            'stage.in'       => 'مرحلة الإصدارة غير صحيحة',

            'released_at.date'  => 'تاريخ الإصدار غير صحيح',
            'released_at.before_or_equal' => 'تاريخ الإصدار يجب ألا يتجاوز تاريخ اليوم',

            'is_active.required' => 'يرجى تحديد حالة الإصدارة',
            'is_active.boolean'  => 'قيمة الحالة غير صالحة',
        ];
    }
}
