<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AdminMangment\Users;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteAdminUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('users.delete') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $ids = $this->input('ids');

        if (is_string($ids)) {
            $ids = array_filter(array_map('trim', explode(',', $ids)), static fn (string $id): bool => $id !== '');
        }

        $this->merge(['ids' => $ids]);
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:admin_users,id'],
        ];
    }
}
