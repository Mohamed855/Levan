<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\SubscriptionManagement;

class MassRejectSubscriptionsRequest extends MassAcceptSubscriptionsRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'reject_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
