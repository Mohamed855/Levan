<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerFollow;

use App\Services\Tenants\BusinessCenter\Customer\Follow\ContactResultService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerFollowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customer_follows.edit') ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'contact_method_id' => 'required|exists:settings_contact_methods,id',
            'contact_location' => 'nullable|string|max:255',
            // 'contacted_person' => 'required|in:customer,relative',
            'contacted_person' => 'nullable|in:customer,relative',
            // 'contacted_person_name' => 'required_if:contacted_person,relative|string|max:255',
            'contacted_person_name' => 'nullable|required_if:contacted_person,relative|string|max:255',
            'next_contact_datetime' => 'nullable|date',
            'customer_status_id' => 'nullable|exists:settings_customer_status,id', // غير مطلوب
            'contact_result_id' => 'required|exists:contact_results,id',
            'notes' => 'nullable|string|max:2000',
        ];

        if ($this->has('contact_result_id')) {
            $contactResultService = app(ContactResultService::class);
            $contactResultRules = $contactResultService->getValidationRules($this->contact_result_id);
            $rules = array_merge($rules, $contactResultRules);
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'contact_method_id' => 'وسيلة الاتصال',
            'contact_location' => 'مكان الزيارة',
            'contacted_person' => 'الشخص المتصل به',
            'contacted_person_name' => 'اسم الشخص المتصل به',
            'next_contact_datetime' => 'تاريخ ووقت الاتصال القادم',
            'customer_status_id' => 'حالة العميل',
            'contact_result_id' => 'نتيجة الاتصال',
            'notes' => 'الملاحظات',
        ];
    }

    public function messages(): array
    {
        $messages = [
            'contact_method_id.required' => 'وسيلة الاتصال مطلوبة',
            'contact_method_id.exists' => 'وسيلة الاتصال المحددة غير صحيحة',
            // 'contacted_person.required' => 'يجب تحديد الشخص المتصل به',
            'contacted_person.in' => 'الشخص المتصل به يجب أن يكون العميل أو أحد الأقارب',
            'contacted_person_name.required_if' => 'اسم الشخص المتصل به مطلوب عند اختيار "أحد أقارب العميل"',
            'contacted_person_name.max' => 'اسم الشخص يجب ألا يتجاوز 255 حرف',
            'next_contact_datetime.date' => 'تاريخ الاتصال القادم غير صحيح',
            'customer_status_id.required' => 'حالة العميل مطلوبة',
            'customer_status_id.exists' => 'حالة العميل المحددة غير صحيحة',
            'contact_result_id.required' => 'نتيجة الاتصال مطلوبة',
            'contact_result_id.exists' => 'نتيجة الاتصال المحددة غير صحيحة',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 2000 حرف',
            'contact_location.max' => 'مكان الزيارة يجب ألا يتجاوز 255 حرف',
        ];

        if ($this->has('contact_result_id')) {
            $contactResultService = app(ContactResultService::class);
            $contactResultMessages = $contactResultService->getValidationMessages($this->contact_result_id);
            $messages = array_merge($messages, $contactResultMessages);
        }

        return $messages;
    }
}
