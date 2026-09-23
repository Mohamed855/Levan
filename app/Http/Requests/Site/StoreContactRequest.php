<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name'         => ['required', 'string', 'min:3', 'max:100'],
            'customer_email'        => ['required', 'email'],
            'message'               => ['required', 'string', 'min:10'],
            'g-recaptcha-response'  => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required'        => 'الاسم الكامل مطلوب',
            'customer_name.min'             => 'الاسم الكامل يجب أن يكون 3 أحرف على الأقل',
            'customer_email.required'       => 'البريد الإلكتروني مطلوب',
            'customer_email.email'          => 'الرجاء إدخال بريد إلكتروني صحيح',
            'message.required'              => 'الرسالة مطلوبة',
            'message.min'                   => 'الرسالة يجب أن تكون 10 أحرف على الأقل',
            'g-recaptcha-response.required' => 'فشل التحقق من reCAPTCHA',
        ];
    }

    protected function passedValidation(): void
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'    => config('services.recaptcha.secret_key'),
            'response'  => $this->input('g-recaptcha-response'),
            'remoteip'  => $this->ip(),
        ]);

        $result  = $response->json();
        $success = $result['success'] ?? false;
        $score   = $result['score'] ?? 0;
        $action  = $result['action'] ?? null;

        if (!$success || $action !== 'contact_form' || $score < 0.5) {
            throw ValidationException::withMessages([
                'recaptcha' => 'فشل التحقق من reCAPTCHA، الرجاء المحاولة مرة أخرى.',
            ]);
        }
    }
}
