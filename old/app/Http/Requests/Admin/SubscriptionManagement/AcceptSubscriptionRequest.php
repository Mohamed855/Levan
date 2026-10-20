<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\SubscriptionManagement;

use Illuminate\Foundation\Http\FormRequest;

class AcceptSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('subscriptions.pending.approve') ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }
}
