<?php

namespace App\Http\Requests\Tenants\Domains;

use Illuminate\Foundation\Http\FormRequest;

class CheckMultipleDomainsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $domains = $this->input('domains', []);

        if (is_array($domains)) {
            $this->merge([
                'domains' => array_map(
                    static fn (mixed $domain): string => strtolower(trim((string) $domain)),
                    $domains,
                ),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user('tenant')?->can('domain.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'domains' => ['required', 'array', 'min:1', 'max:10'],
            'domains.*' => ['required', 'string', 'regex:/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i'],
        ];
    }

    public function messages(): array
    {
        return [
            'domains.required' => 'أدخل نطاقاً واحداً على الأقل.',
            'domains.array' => 'يجب إرسال النطاقات في قائمة.',
            'domains.min' => 'أدخل نطاقاً واحداً على الأقل.',
            'domains.max' => 'الحد الأقصى لفحص النطاقات هو 10.',
            'domains.*.regex' => 'أحد أسماء النطاقات غير صالح.',
        ];
    }

    public function attributes(): array
    {
        return [
            'domains' => 'النطاقات',
            'domains.*' => 'اسم النطاق',
        ];
    }
}
