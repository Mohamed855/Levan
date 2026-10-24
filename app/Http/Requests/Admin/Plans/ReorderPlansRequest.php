<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Plans;

use Illuminate\Foundation\Http\FormRequest;

final class ReorderPlansRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->hasPermissionTo('plans.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'integer', 'min:0', 'distinct'],
        ];
    }
}
