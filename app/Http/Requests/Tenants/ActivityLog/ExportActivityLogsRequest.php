<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\ActivityLog;

use Illuminate\Foundation\Http\FormRequest;

class ExportActivityLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('activity_log.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'string', 'json'],
        ];
    }
}
