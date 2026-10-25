<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\PublicRequests;

use Illuminate\Foundation\Http\FormRequest;

final class ApproveTrialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('trial_requests.approve') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
