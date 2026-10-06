<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Task;

use Illuminate\Foundation\Http\FormRequest;

class ExportTasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = str_contains((string) $this->route()?->getActionName(), 'MyTaskController@')
            ? 'my_tasks.view'
            : 'tasks.view';

        return $this->user('tenant')?->can($permission) ?? false;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'relationship_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'task_type' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:100'],
            'overdue_days' => ['nullable', 'integer', 'min:0', 'max:36500'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
