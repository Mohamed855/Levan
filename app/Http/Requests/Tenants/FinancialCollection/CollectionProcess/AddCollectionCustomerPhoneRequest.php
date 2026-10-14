<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\CollectionProcess;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use Illuminate\Foundation\Http\FormRequest;

class AddCollectionCustomerPhoneRequest extends FormRequest
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
        return [
            'phone' => ['required', 'string', 'size:10', 'regex:/^05[0-9]{8}$/'],
            'type' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['phone.required' => 'رقم الهاتف مطلوب.', 'phone.size' => 'رقم الهاتف يجب أن يتكون من 10 أرقام.', 'phone.regex' => 'رقم الهاتف يجب أن يبدأ بـ 05.'];
    }

    public function attributes(): array
    {
        return ['phone' => 'رقم الهاتف', 'type' => 'نوع الرقم', 'notes' => 'الملاحظات'];
    }
}
