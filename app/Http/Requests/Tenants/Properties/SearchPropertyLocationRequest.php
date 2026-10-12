<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Properties;

use Illuminate\Foundation\Http\FormRequest;

final class SearchPropertyLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('tenant');

        return $user?->can('properties.create') || $user?->can('properties.edit');
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:3', 'max:150'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function attributes(): array
    {
        return [
            'query' => 'عبارة البحث',
            'latitude' => 'خط العرض',
            'longitude' => 'خط الطول',
        ];
    }
}
