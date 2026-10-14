<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\SubscriptionManagement;

use Illuminate\Foundation\Http\FormRequest;

class RejectSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('subscriptions.pending.reject') ?? false;
    }

    public function rules(): array
    {
        return ['reject_reason' => ['nullable', 'string', 'max:1000']];
    }
}
