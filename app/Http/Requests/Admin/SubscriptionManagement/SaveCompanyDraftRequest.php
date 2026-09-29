<?php

namespace App\Http\Requests\Admin\SubscriptionManagement;

use Illuminate\Foundation\Http\FormRequest;

class SaveCompanyDraftRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'draft_step' => ['required', 'integer', 'between:1,4'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'commercial_number' => ['nullable', 'digits:10'],
            'tax_number' => ['nullable', 'string', 'max:255'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'address' => ['nullable', 'string', 'max:1000'],
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'regex:/^05[0-9]{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'total_users' => ['nullable', 'integer', 'min:1'],
            'activate_immediately' => ['nullable', 'boolean'],
            'payment_type' => ['nullable', 'in:trial,paid,unpaid'],
            'payment_method' => ['nullable', 'string'],
            'has_discount' => ['nullable', 'in:yes,no'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
