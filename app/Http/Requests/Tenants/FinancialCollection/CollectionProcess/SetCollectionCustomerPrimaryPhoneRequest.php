<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\CollectionProcess;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use Illuminate\Foundation\Http\FormRequest;

class SetCollectionCustomerPrimaryPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('tenant');
        $customer = $this->route('customer');

        return $user !== null && ($user->can('customers.edit_all')
            || ($customer instanceof Customer && $user->can('customers.edit') && $customer->relationship_manager_id === $user->getKey()));
    }

    public function rules(): array
    {
        return ['phone_id' => ['required', 'integer', 'exists:customer_phones,id']];
    }

    public function messages(): array
    {
        return ['phone_id.required' => 'معرف رقم الهاتف مطلوب.', 'phone_id.exists' => 'رقم الهاتف المحدد غير موجود.'];
    }

    public function attributes(): array
    {
        return ['phone_id' => 'رقم الهاتف'];
    }
}
