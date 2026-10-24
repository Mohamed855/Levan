<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Plans;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePlanStatusRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->hasPermissionTo('plans.update') ?? false; }
    public function rules(): array { return ['is_active' => ['required', 'boolean']]; }
}
