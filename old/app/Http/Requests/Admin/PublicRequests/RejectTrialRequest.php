<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\PublicRequests;

use Illuminate\Foundation\Http\FormRequest;

final class RejectTrialRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->can('trial_requests.reject') ?? false; }
    public function rules(): array { return ['rejection_reason' => ['required', 'string', 'max:2000']]; }
    public function messages(): array { return ['rejection_reason.required' => 'يرجى إدخال سبب الرفض.']; }
    public function attributes(): array { return ['rejection_reason' => 'سبب الرفض']; }
}
