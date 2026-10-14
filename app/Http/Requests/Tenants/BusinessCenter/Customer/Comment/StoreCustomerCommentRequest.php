<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\Comment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerCommentRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorize
    |--------------------------------------------------------------------------
    */
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.edit')
            || $this->user('tenant')?->can('customers.edit_all')
            || false;
    }

    /*
    |--------------------------------------------------------------------------
    | Rules
    |--------------------------------------------------------------------------
    */
    public function rules(): array
    {
        return [
            'comment_text' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Messages
    |--------------------------------------------------------------------------
    */
    public function messages(): array
    {
        return [
            'comment_text.required' => 'نص التعليق مطلوب.',
            'comment_text.min' => 'نص التعليق يجب أن يكون على الأقل 3 أحرف.',
            'comment_text.max' => 'نص التعليق يجب ألا يتجاوز 2000 حرف.',
        ];
    }
}
