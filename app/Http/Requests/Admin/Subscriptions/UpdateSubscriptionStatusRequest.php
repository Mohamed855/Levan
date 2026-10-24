<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Subscriptions;

use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSubscriptionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('subscriptions.update') ?? false;
    }

    public function rules(): array
    {
        $current   = $this->route('subscription')?->status ?? '';
        $allowed   = implode(',', SubscriptionStatus::allowedTransitions()[$current] ?? []);

        return [
            'status' => ['required', 'string', 'in:' . $allowed],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'الحالة مطلوبة.',
            'status.in'       => 'الانتقال إلى هذه الحالة غير مسموح من الحالة الحالية.',
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'الحالة',
        ];
    }

    /** Returns the map so other classes can read it without coupling. */
    public static function allowedTransitions(): array
    {
        return SubscriptionStatus::allowedTransitions();
    }
}
