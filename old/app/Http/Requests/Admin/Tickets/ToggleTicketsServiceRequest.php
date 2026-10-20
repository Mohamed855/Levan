<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class ToggleTicketsServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('system.settings') ?? false;
    }

    public function rules(): array
    {
        return ['tickets_enabled' => ['required', 'boolean']];
    }
}
