<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class StoreTrialRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $phone = (string) $this->input('customer_phone', '');
        $arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        $this->merge([
            'customer_phone' => preg_replace(
                '/[^0-9]/',
                '',
                str_replace($arabicDigits, range(0, 9), $phone),
            ),
        ]);
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:100'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'regex:/^(05\d{8}|966\d{9})$/'],
            // 'g-recaptcha-response' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'customer_name.required' => 'اسم المستخدم مطلوب',
            'customer_name.regex' => 'اسم المستخدم يجب أن يحتوي على حروف عربية أو إنجليزية فقط',

            'customer_email.required' => 'البريد الإلكتروني مطلوب',
            'customer_email.email' => 'البريد الإلكتروني غير صحيح',

            'customer_phone.required' => 'رقم الجوال مطلوب',
            'customer_phone.regex' => 'صيغة رقم الجوال غير صحيحة (يجب أن يبدأ بـ 05)',

            'g-recaptcha-response.required' => 'فشل التحقق من reCAPTCHA',
        ];
    }

    protected function passedValidation(): void
    {
        // Temporarily disabled until the reCAPTCHA keys are configured.
        return;

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret_key'),
            'response' => $this->input('g-recaptcha-response'),
            'remoteip' => $this->ip(),
        ]);

        $result = $response->json();
        $success = $result['success'] ?? false;
        $score = $result['score'] ?? 0;
        $action = $result['action'] ?? null;

        if (! $success || $action !== 'trial_form' || $score < 0.5) {
            throw ValidationException::withMessages([
                'recaptcha' => 'فشل التحقق من reCAPTCHA، الرجاء المحاولة مرة أخرى.',
            ]);
        }
    }
}
