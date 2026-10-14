<?php

namespace App\Http\Requests\Tenants\GeneralSetting\MessageTemplate;

use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMessageTemplateRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('message_templates.view') ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */
    public function rules(): array
    {
        $template = $this->route('messageTemplate');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('message_templates', 'name')->ignore($template?->id),
            ],
            'content' => 'required|string',
            'category' => 'required|string|in:'.implode(',', array_keys(MessageTemplate::getCategories())),
            'channel' => 'required|string|in:'.implode(',', array_keys(MessageTemplate::getChannels())),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Messages
    |--------------------------------------------------------------------------
    */
    public function messages(): array
    {
        return [
            'name.required' => 'حقل اسم القالب مطلوب.',
            'name.string' => 'اسم القالب يجب أن يكون نصاً.',
            'name.max' => 'اسم القالب يجب ألا يزيد عن 255 حرفاً.',
            'name.unique' => 'اسم القالب مستخدم من قبل، الرجاء اختيار اسم آخر.',

            'content.required' => 'حقل محتوى القالب مطلوب.',
            'content.string' => 'محتوى القالب يجب أن يكون نصاً صحيحاً.',

            'category.required' => 'حقل فئة القالب مطلوب.',
            'category.string' => 'فئة القالب يجب أن تكون نصاً.',
            'category.in' => 'فئة القالب غير صحيحة.',

            'channel.required' => 'حقل قناة الإرسال مطلوب.',
            'channel.string' => 'قناة الإرسال يجب أن تكون نصاً.',
            'channel.in' => 'قناة الإرسال غير صحيحة.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Attributes Names
    |--------------------------------------------------------------------------
    */
    public function attributes(): array
    {
        return [
            'name' => 'اسم القالب',
            'content' => 'محتوى القالب',
            'category' => 'فئة القالب',
            'channel' => 'قناة الإرسال',
        ];
    }
}
