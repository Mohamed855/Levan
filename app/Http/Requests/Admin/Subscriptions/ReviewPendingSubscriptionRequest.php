<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Subscriptions;

use Illuminate\Foundation\Http\FormRequest;

final class ReviewPendingSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('subscriptions.update') ?? false;
    }

    public function rules(): array
    {
        return ['reason' => ['nullable', 'string', 'max:500']];
    }
}
