<?php
declare(strict_types=1);
namespace App\Http\Requests\Admin\Plans;
use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Validation\Rule;

final class StorePlanRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function validationData(): array
    {
        $data = parent::validationData();

        $data['code'] = strtoupper(trim((string) ($data['code'] ?? '')));
        $data['is_active'] = filter_var($data['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['domain_management_enabled'] = filter_var($data['domain_management_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['features'] = array_values(array_filter(
            preg_split('/\r\n|\r|\n/', (string) ($data['features_text'] ?? '')) ?: [],
            static fn (string $feature): bool => trim($feature) !== '',
        ));

        return $data;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'max:50', 'regex:/^[A-Z][A-Z0-9_]*$/', Rule::unique('plans', 'code')],
            'plan_type' => ['required', Rule::in(['basic', 'professional', 'enterprise'])],
            'color' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'limits_mode' => ['required', Rule::in(['limited', 'unlimited'])],
            'included_users' => ['nullable', 'integer', 'min:1'],
            'properties_limit' => ['nullable', 'integer', 'min:1'],
            'storage_quota_gb' => ['nullable', 'integer', 'min:1'],
            'active_auctions_limit' => ['nullable', 'integer', 'min:1'],
            'subscription_billing_cycle' => ['required', Rule::in(['monthly', 'quarterly', 'yearly'])],
            'available_from' => ['nullable', 'date'],
            'features' => ['required', 'array', 'min:1'],
            'features.*' => ['string', 'max:255'],
            'is_active' => ['boolean'],
            'domain_management_enabled' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'اسم الباقة', 'code' => 'رمز الباقة', 'price' => 'السعر', 'features' => 'مزايا الباقة'];
    }
}
