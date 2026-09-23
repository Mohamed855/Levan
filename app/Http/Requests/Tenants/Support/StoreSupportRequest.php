<?php

namespace App\Http\Requests\Tenants\Support;



use Illuminate\Foundation\Http\FormRequest;

class StoreSupportRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
        ];
    }


    public function messages(): array
    {
        return [
            'subject.required'      => 'موضوع التذكرة مطلوب',
            'subject.string'        => 'موضوع التذكرة يجب ان يكون نص',
            'subject.max'           => 'موضوع التذكرة يجب ان لا يتجاوز 255 حرف',

            'message.required'      => 'محتوى التذكرة مطلوب',
            'message.string'        => 'محتوى التذكرة يجب ان يكون نص',
            'message.min'           => 'محتوى التذكرة يجب ان لا يقل عن 10 احرف'
        ];
    }
}
