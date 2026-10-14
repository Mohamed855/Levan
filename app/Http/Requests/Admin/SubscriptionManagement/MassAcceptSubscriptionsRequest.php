<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\SubscriptionManagement;

use Illuminate\Foundation\Http\FormRequest;

class MassAcceptSubscriptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('subscriptions.pending.approve') ?? false;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:subscriptions,id'],
        ];
    }
}
