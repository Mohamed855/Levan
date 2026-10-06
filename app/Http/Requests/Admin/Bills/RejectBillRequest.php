<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Bills;

use Illuminate\Foundation\Http\FormRequest;

class RejectBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->user()?->can('subscriptions.pending.reject') ?? false;
    }

    public function rules(): array
    {
        return [
            'reject_reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'reject_reason' => 'سبب الرفض',
        ];
    }
}
