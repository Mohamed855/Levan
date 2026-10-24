<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Assignments;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAssignmentStatusRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('tenant')?->hasPermissionTo('assignments.edit') ?? false; }
    public function rules(): array { return ['is_active' => ['required', 'boolean']]; }
}
