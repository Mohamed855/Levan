<?php

namespace App\Http\Requests\Tenants\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class MainSettingsRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization for the request.
    |--------------------------------------------------------------------------
    */
    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation rules for the main settings.
    |--------------------------------------------------------------------------
    */
    public function rules(): array
    {
        return [
            // عامة
            'office_name'               => 'nullable|string|max:255',
            'logo_text'                 => 'nullable|string|max:14',
            'image'                         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:800',
            'maintenance_mode'          => 'nullable',
            'breadcrumb_visibility'     => 'nullable',
            'force_two_factor_auth'     => 'nullable',

            // طباعة
            'horizontal_header_image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1200',
            'horizontal_footer_image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1200',
            'signature'           => 'nullable|image|mimes:jpeg,png,jpg,gif|max:500',

            // WhatsApp (Twilio)
            // 'twilio_account_sid'        => 'nullable|string',
            // 'twilio_auth_token'         => 'nullable|string',
            // 'twilio_whatsapp_from'      => 'nullable|string',
            // 'whatsapp_enabled'          => 'boolean',

            // SMS
            'sms_provider'              => 'nullable|string|max:255',
            'sms_api_key'               => 'nullable|string|max:255',
            'sms_api_secret'            => 'nullable|string|max:255',
            'sms_api_endpoint'          => 'nullable|url',
            'sms_sender_id'             => 'nullable|string|max:255',
            'sms_from_number'           => 'nullable|string|max:255',
            'sms_callback_url'          => 'nullable|url',
            'sms_enabled'               => 'nullable',
            'sms_rate_limit'            => 'nullable|integer|min:0',

            // دعم فني
            'support_emails'            => 'nullable|array',
            'support_emails.*'          => 'email|distinct',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom validation messages.
    |--------------------------------------------------------------------------
    */
    public function messages(): array
    {
        return [
            'office_name.string'   => 'اسم المكتب يجب أن يكون نصًا.',
            'office_name.max'      => 'اسم المكتب لا يجب أن يتجاوز 255 حرفًا.',
            'logo_text.max'        => 'نص الشعار لا يجب أن يتجاوز 14 حرفًا.',
            'image.image'          => 'الحقل يجب أن يكون صورة.',
            'image.mimes'          => 'الصورة يجب أن تكون من نوع jpeg، png، jpg، gif.',
            'image.max'            => 'حجم الصورة لا يجب أن يتجاوز 800 كيلوبايت.',

            'horizontal_header_image.image' => 'الترويسة يجب أن تكون صورة.',
            'horizontal_header_image.max'   => 'حجم الترويسة لا يجب أن يتجاوز 1200 كيلوبايت.',
            'vertical_header_image.image'   => 'الترويسة العمودية يجب أن تكون صورة.',
            'vertical_header_image.max'     => 'حجم الترويسة العمودية لا يجب أن يتجاوز 1200 كيلوبايت.',
            'horizontal_footer_image.image' => 'الفوتر يجب أن يكون صورة.',
            'horizontal_footer_image.max'   => 'حجم الفوتر لا يجب أن يتجاوز 1200 كيلوبايت.',
            'vertical_footer_image.image'   => 'الفوتر العمودي يجب أن يكون صورة.',
            'vertical_footer_image.max'     => 'حجم الفوتر العمودي لا يجب أن يتجاوز 1200 كيلوبايت.',
            'signature.image'               => 'الختم يجب أن يكون صورة.',
            'signature.max'                 => 'حجم الختم لا يجب أن يتجاوز 500 كيلوبايت.',

            // 'twilio_account_sid.string'     => 'معرّف حساب Twilio يجب أن يكون نصًا.',
            // 'twilio_auth_token.string'      => 'رمز توثيق Twilio يجب أن يكون نصًا.',
            // 'twilio_whatsapp_from.string'   => 'Twilio WhatsApp from يجب أن يكون نصًا.',
            // 'whatsapp_enabled.boolean'      => 'تمكين WhatsApp يجب أن يكون قيمة منطقية.',

            'sms_provider.max'              => 'مزود SMS لا يجب أن يتجاوز 255 حرفًا.',
            'sms_api_key.max'               => 'مفتاح API لا يجب أن يتجاوز 255 حرفًا.',
            'sms_api_secret.max'            => 'سر API لا يجب أن يتجاوز 255 حرفًا.',
            'sms_api_endpoint.url'          => 'نقطة نهاية SMS يجب أن تكون رابطًا صالحًا.',
            'sms_sender_id.max'             => 'معرّف المرسل لا يجب أن يتجاوز 255 حرفًا.',
            'sms_from_number.max'           => 'رقم المرسل لا يجب أن يتجاوز 255 حرفًا.',
            'sms_callback_url.url'          => 'رابط رد الاتصال يجب أن يكون رابطًا صالحًا.',
            'sms_rate_limit.integer'        => 'معدل الرسائل يجب أن يكون عددًا صحيحًا.',
            'sms_rate_limit.min'            => 'معدل الرسائل لا يمكن أن يكون سالبًا.',

            'support_emails.array'          => 'حقل عناوين الدعم يجب أن يكون مصفوفة.',
            'support_emails.*.email'        => 'كل بريد إلكتروني في الدعم يجب أن يكون صالحًا.',
            'support_emails.*.distinct'     => 'لا يمكن تكرار البريد الإلكتروني في الدعم.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Handle a failed validation attempt.
    |--------------------------------------------------------------------------
    */
    protected function failedValidation(Validator $validator)
    {
        $fragment = $this->input('active_tab', null);
        throw new HttpResponseException(
            redirect()->back()
                ->withInput()
                ->withFragment($fragment)
                ->withErrors($validator)
        );
    }

}
