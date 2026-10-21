<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\PublicRequests;

use Illuminate\Foundation\Http\FormRequest;

final class ApproveTrialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->hasPermissionTo('public_requests.process') ?? false;
    }

    public function rules(): array
    {
        // No additional input required for approval; the action validates business state.
        return [];
    }
}
