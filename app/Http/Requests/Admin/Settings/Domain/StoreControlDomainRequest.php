<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings\Domain;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreControlDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain_extension' => ['required', 'string', Rule::unique('control_domains', 'domain_extension')],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
