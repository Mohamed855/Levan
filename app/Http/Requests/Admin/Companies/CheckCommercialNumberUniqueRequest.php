<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Companies;

use Illuminate\Foundation\Http\FormRequest;

class CheckCommercialNumberUniqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['commercial_number' => ['required', 'string', 'digits:10'], 'company_id' => ['nullable', 'string']];
    }
}
