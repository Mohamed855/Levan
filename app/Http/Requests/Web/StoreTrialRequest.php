<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrialRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $phone = strtr((string) $this->input('customer_phone', ''), ['?' => '0', '?' => '1', '?' => '2', '?' => '3', '?' => '4', '?' => '5', '?' => '6', '?' => '7', '?' => '8', '?' => '9']);
        $this->merge(['customer_phone' => preg_replace('/[^0-9]/', '', $phone)]);
    }

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:100'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'regex:/^(05\d{8}|966\d{9})$/'],
        ];
    }

}
