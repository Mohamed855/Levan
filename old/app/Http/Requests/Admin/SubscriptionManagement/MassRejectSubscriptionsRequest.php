<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\SubscriptionManagement;

class MassRejectSubscriptionsRequest extends MassAcceptSubscriptionsRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('subscriptions.pending.reject') ?? false;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'reject_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
