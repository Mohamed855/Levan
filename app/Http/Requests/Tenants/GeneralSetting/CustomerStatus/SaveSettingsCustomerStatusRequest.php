<?php

namespace App\Http\Requests\Tenants\GeneralSetting\CustomerStatus;

use Illuminate\Foundation\Http\FormRequest;

class SaveSettingsCustomerStatusRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Determine if the user is authorized to make this request
    |--------------------------------------------------------------------------
    | يتحقق مما إذا كان لدى المستخدم الحالي الصلاحية لتنفيذ هذا الطلب.
    */
    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Get the validation rules that apply to the request
    |--------------------------------------------------------------------------
    | يُرجع مصفوفة بقواعد التحقق التي سيتم تطبيقها على بيانات الطلب.
    */
    public function rules(): array
    {

        return [
            'name' => 'required|string|max:255',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get the custom messages for validator errors
    |--------------------------------------------------------------------------
    | يُرجع مصفوفة بالرسائل المخصصة التي ستظهر عند فشل قواعد التحقق.
    */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم الحالة مطلوب.',
            'name.max' => 'اسم الحالة يجب ألا يتجاوز 255 حرف.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم الحالة',
        ];
    }
}
