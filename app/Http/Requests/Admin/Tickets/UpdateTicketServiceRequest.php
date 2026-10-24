<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tickets;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTicketServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('tickets.update') ?? false;
    }

    public function rules(): array
    {
        return ['service_enabled' => ['required', 'boolean']];
    }
}
