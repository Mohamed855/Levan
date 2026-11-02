<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\PromotionalAdvertisements;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionalAdvertisementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant') !== null;
    }

    public function rules(): array
    {
        return [
            'template_type' => ['required', 'string', 'in:billboard,social'],
            'auction_template_id' => ['required', 'integer'],
            'auction_id' => ['required', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'template_type' => 'نوع القالب',
            'auction_template_id' => 'القالب',
            'auction_id' => 'المزاد',
        ];
    }

    public function messages(): array
    {
        return [
            'template_type.required' => 'يرجى اختيار نوع القالب.',
            'template_type.in' => 'نوع القالب غير صالح، يرجى الاختيار بين إعلاني وسوشيال ميديا.',
            'auction_template_id.required' => 'يرجى اختيار القالب.',
            'auction_id.required' => 'يرجى اختيار المزاد.',
            'auction_id.exists' => 'المزاد المختار غير موجود في النظام.',
        ];
    }
}
