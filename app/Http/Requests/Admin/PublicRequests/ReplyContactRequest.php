<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\PublicRequests;

use Illuminate\Foundation\Http\FormRequest;

final class ReplyContactRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->can('contact_requests.reply') ?? false; }
    public function rules(): array { return ['reply' => ['required', 'string', 'max:5000']]; }
    public function messages(): array { return ['reply.required' => 'يرجى إدخال نص الرد.']; }
    public function attributes(): array { return ['reply' => 'الرد']; }
}
