<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\SubscriptionManagement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'tax_number' => ['required', 'string', 'max:255'],
            'city_id' => ['required', 'exists:cities,id'],
            'address' => ['required', 'string', 'max:1000'],
            'plan_id' => ['required', 'exists:plans,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string'],
            'total_users' => ['required', 'integer', 'min:1'],
            'licenses_quota' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'next_payment_date' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tenant_id' => 'المستأجر', 'tax_number' => 'الرقم الضريبي', 'city_id' => 'المدينة', 'address' => 'العنوان',
            'plan_id' => 'الباقة', 'price' => 'السعر', 'status' => 'الحالة', 'total_users' => 'إجمالي المستخدمين',
            'licenses_quota' => 'حصة التراخيص', 'starts_at' => 'تاريخ البداية', 'ends_at' => 'تاريخ النهاية',
            'next_payment_date' => 'تاريخ الدفعة القادمة',
        ];
    }
}
