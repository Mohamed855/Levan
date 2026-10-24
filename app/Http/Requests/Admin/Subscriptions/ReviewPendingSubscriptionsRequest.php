<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Subscriptions;

use Illuminate\Foundation\Http\FormRequest;

final class ReviewPendingSubscriptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('subscriptions.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'distinct', 'exists:subscriptions,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
