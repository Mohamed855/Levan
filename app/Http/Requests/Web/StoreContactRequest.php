<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:3', 'max:100'],
            'customer_email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

}
