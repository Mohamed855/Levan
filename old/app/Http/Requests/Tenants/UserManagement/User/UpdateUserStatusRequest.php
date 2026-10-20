<?php

namespace App\Http\Requests\Tenants\UserManagement\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserStatusRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Determine if the user is authorized to make this request.
    |--------------------------------------------------------------------------
    */
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('roles.assign') ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Get the validation rules that apply to the request.
    |--------------------------------------------------------------------------
    */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get custom attribute names for validator errors.
    |--------------------------------------------------------------------------
    */
    public function attributes(): array
    {
        return [
            'status' => 'الحالة',
        ];
    }
}
