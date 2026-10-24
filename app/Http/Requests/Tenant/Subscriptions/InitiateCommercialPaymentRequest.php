<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Subscriptions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InitiateCommercialPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->hasPermissionTo($this->routeIs('tenant.subscriptions.renew') ? 'subscriptions.renew' : 'subscriptions.add_licenses') ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['online', 'bank_transfer'])],
            'license_count' => [Rule::requiredIf($this->routeIs('tenant.subscriptions.licenses')), 'nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
