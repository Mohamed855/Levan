<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Customer;

use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Contracts\Validation\Validator;

class StoreCustomerRequest extends WizardFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required_without_all:first_name,father_name,grandfather_name,family_name',
                'nullable',
                'string',
                'max:255',
            ],
            'first_name' => [
                'required_without:name',
                'nullable',
                'string',
                'max:100',
            ],
            'father_name' => ['nullable', 'string', 'max:100'],
            'grandfather_name' => ['nullable', 'string', 'max:100'],
            'family_name' => ['nullable', 'string', 'max:100'],

            'customer_type' => ['required', 'in:individual,company'],
            'company_id' => ['required', 'exists:companies,id'],
            'portfolio_id' => ['required', 'exists:portfolios,id'],
            'identity_number' => ['required', 'string', 'max:50'],
            'nationality' => ['nullable', 'exists:settings_countries,id'],
            'relationship_manager_id' => ['nullable', 'exists:users,id'],

            // البيانات الاختيارية
            'phone' => ['nullable', 'string', 'size:10', 'regex:/^05[0-9]{8}$/'],
            'account_number' => ['nullable', 'string', 'max:100', 'unique:customers,account_number'],
            'contract_date' => ['nullable', 'date', 'before_or_equal:today'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            // 'customer_status_id' => 'required|exists:settings_customer_status,id',

            // بيانات الهواتف - جميعها اختيارية
            'phones' => ['nullable', 'array'],
            'phones.*.phone' => ['nullable', 'string'],
            'phones.*.type' => ['nullable', 'in:mobile,home,work,other'],
            'phones.*.notes' => ['nullable', 'string', 'max:255'],

            // خاصة بالحقول المتجددة
            'dynamic' => ['nullable', 'array'],
            'dynamic.*' => ['nullable'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required_without_all' => 'يجب إدخال الاسم الكامل أو أجزاء الاسم.',
            'first_name.required_without' => 'الاسم الأول مطلوب إذا لم يتم إدخال الاسم الكامل.',

            'customer_type.required' => 'نوع العميل مطلوب',
            'customer_type.in' => 'نوع العميل يجب أن يكون فردي أو شركة',

            'company_id.required' => 'الشركة مطلوبة',
            'company_id.exists' => 'الشركة المحددة غير موجودة',

            'portfolio_id.required' => 'المحفظة مطلوبة',
            'portfolio_id.exists' => 'المحفظة المحددة غير موجودة',

            'identity_number.required' => 'رقم الهوية مطلوب',
            'identity_number.max' => 'رقم الهوية يجب ألا يتجاوز 50 حرف',

            'nationality.exists' => 'الجنسية المحددة غير موجودة',

            'debt_amount.required' => 'مبلغ المديونية مطلوب',
            'debt_amount.numeric' => 'مبلغ المديونية يجب أن يكون رقم',
            'debt_amount.min' => 'مبلغ المديونية يجب أن يكون أكبر من أو يساوي صفر',

            'relationship_manager_id.exists' => 'مسؤول العلاقة المحدد غير موجود',

            'phone.size' => 'رقم الهاتف يجب أن يتكون من 10 أرقام',
            'phone.regex' => 'رقم الهاتف يجب أن يبدأ بـ 05',
            'account_number.unique' => 'رقم الحساب موجود مسبقاً',
            'account_number.max' => 'رقم الحساب يجب ألا يتجاوز 100 حرف',
            'contract_date.date' => 'تاريخ التعاقد يجب أن يكون تاريخ صحيح',
            'contract_date.before_or_equal' => 'تاريخ التعاقد يجب أن يكون اليوم أو قبله',
            'commission_rate.numeric' => 'نسبة العمولة يجب أن تكون رقم',
            'commission_rate.min' => 'نسبة العمولة يجب أن تكون أكبر من أو تساوي صفر',
            'commission_rate.max' => 'نسبة العمولة يجب أن تكون أقل من أو تساوي 100',
            'default_date.date' => 'تاريخ التعثر يجب أن يكون تاريخ صحيح',
            'default_date.before_or_equal' => 'تاريخ التعثر يجب أن يكون اليوم أو قبله',
            // 'customer_status_id.required' => 'حالة العميل مطلوبة',
            // 'customer_status_id.exists' => 'حالة العميل المحددة غير صحيحة',

            'phones.array' => 'بيانات الهواتف يجب أن تكون مصفوفة',
            'phones.*.type.in' => 'نوع الهاتف يجب أن يكون جوال، منزل، عمل، أو أخرى',
            'phones.*.notes.max' => 'ملاحظات الهاتف يجب ألا تتجاوز 255 حرف',
        ];
    }

    /**
     * Get custom attribute names for validation.
     */
    public function attributes(): array
    {
        return [
            'name' => 'اسم العميل',
            'first_name' => 'الاسم الأول',
            'father_name' => 'اسم الأب',
            'grandfather_name' => 'اسم الجد',
            'family_name' => 'اسم العائلة',
            'customer_type' => 'نوع العميل',
            'company_id' => 'الشركة',
            'portfolio_id' => 'المحفظة',
            'identity_number' => 'رقم الهوية',
            'nationality' => 'الجنسية',
            'debt_amount' => 'مبلغ المديونية',
            'relationship_manager_id' => 'مسؤول العلاقة',
            'phone' => 'رقم الهاتف',
            'account_number' => 'رقم الحساب',
            'contract_date' => 'تاريخ التعاقد',
            'commission_rate' => 'نسبة العمولة',
            'default_date' => 'تاريخ التعثر',
            'status' => 'الحالة',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('phones', []) as $index => $phoneData) {
                $phone = $phoneData['phone'] ?? null;

                if ($phone === null || $phone === '') {
                    continue;
                }

                $isLandline = ($phoneData['type'] ?? null) === 'home';
                $isValid = $isLandline
                    ? preg_match('/^[0-9]{7,15}$/', $phone) === 1
                    : preg_match('/^05[0-9]{8}$/', $phone) === 1;

                if (! $isValid) {
                    $validator->errors()->add(
                        "phones.$index.phone",
                        $isLandline
                            ? 'رقم الهاتف الأرضي يجب أن يتكون من 7 إلى 15 رقماً.'
                            : 'رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام.'
                    );
                }
            }
        });
    }

    /**
     * Get validated data with proper formatting.
     */
    public function getValidatedData(): array
    {
        $data = $this->validated();

        // Remove empty values for optional fields
        $optionalFields = [
            'phone',
            'account_number',
            'contract_date',
            'commission_rate',
            'default_date',
        ];

        foreach ($optionalFields as $field) {
            if (empty($data[$field])) {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
