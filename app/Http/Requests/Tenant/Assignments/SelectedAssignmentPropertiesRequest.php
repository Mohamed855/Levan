<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Assignments;

use Illuminate\Foundation\Http\FormRequest;

class SelectedAssignmentPropertiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('assignments.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
