<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\PublicRequests;

use Illuminate\Foundation\Http\FormRequest;

final class ReplyContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->hasPermissionTo('public_requests.reply') ?? false;
    }

    public function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'min:5', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reply.required' => 'نص الرد مطلوب.',
            'reply.min'      => 'يجب أن يكون الرد 5 أحرف على الأقل.',
            'reply.max'      => 'يجب ألا يتجاوز الرد 5000 حرف.',
        ];
    }

    public function attributes(): array
    {
        return [
            'reply' => 'الرد',
        ];
    }
}
