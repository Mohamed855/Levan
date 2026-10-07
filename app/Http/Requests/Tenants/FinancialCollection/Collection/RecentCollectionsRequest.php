<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Collection;

use Illuminate\Foundation\Http\FormRequest;

class RecentCollectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('collections.view') ?? false;
    }

    public function rules(): array
    {
        return ['days' => ['nullable', 'integer', 'min:1', 'max:365']];
    }
}
