<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Properties;

use Illuminate\Foundation\Http\FormRequest;

final class ResolveGoogleMapsUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('tenant');

        return $user !== null && ($user->can('properties.create') || $user->can('properties.edit'));
    }

    public function rules(): array
    {
        return ['url' => ['required', 'url', 'max:2000']];
    }
}
