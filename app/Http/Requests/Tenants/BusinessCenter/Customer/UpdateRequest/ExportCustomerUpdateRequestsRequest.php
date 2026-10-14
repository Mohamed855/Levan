<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\UpdateRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportCustomerUpdateRequestsRequest extends FormRequest
{
    private const FIELDS = ['id', 'customer_name', 'customer_identity_number', 'requested_by_name', 'created_at', 'status', 'approved_by_name', 'approved_at', 'rejection_reason', 'requested_data'];

    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customer_update_requests.view') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('selected_fields'))) {
            $decoded = json_decode($this->input('selected_fields'), true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['selected_fields' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'requested_by' => ['nullable', 'integer', 'exists:users,id'],
            'search_query' => ['nullable', 'string', 'max:255'],
            'selected_fields' => ['sometimes', 'array', 'min:1'],
            'selected_fields.*' => ['required', 'string', Rule::in(self::FIELDS), 'distinct'],
        ];
    }

    public function messages(): array
    {
        return ['selected_fields.min' => 'يجب اختيار حقل واحد على الأقل.', 'selected_fields.*.in' => 'حقل التصدير المحدد غير صالح.'];
    }

    public function attributes(): array
    {
        return ['selected_fields' => 'حقول التصدير', 'status' => 'الحالة', 'requested_by' => 'مقدم الطلب'];
    }
}
