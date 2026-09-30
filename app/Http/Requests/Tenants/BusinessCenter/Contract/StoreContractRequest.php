<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Contract;

use App\Data\Tenants\BusinessCenter\Contract\ContractCreateDTO;
use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Support\Carbon;

class StoreContractRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Rules
    |--------------------------------------------------------------------------
    */
    public function rules(): array
    {
        return [
            'company_id'       => ['required', 'integer', 'exists:companies,id'],
            'contract_number'  => ['nullable', 'string', 'max:100', 'unique:contracts,contract_number'],
            'start_date'       => ['required', 'date', 'after_or_equal:today'],
            'end_date'         => ['required', 'date', 'after:start_date'],
            'duration_months'  => ['required', 'integer', 'min:1', 'max:120'],
            'auto_renewal'     => ['boolean'],
            'commission_type'  => ['required', 'in:procedure,duration,delinquency'],
            'status'           => ['in:active,inactive,suspended,expired'],
            'notes'            => ['nullable', 'string', 'max:1000'],

            'commissions'                           => ['required', 'array', 'min:1'],
            'commissions.*.commission_type'         => ['required', 'in:procedure,duration,delinquency'],
            'commissions.*.procedure_type'          => ['nullable', 'in:friendly,legal', 'required_if:commissions.*.commission_type,procedure'],
            'commissions.*.duration_from'           => ['nullable', 'integer', 'min:1', 'required_if:commissions.*.commission_type,duration', 'required_if:commissions.*.commission_type,delinquency'],
            'commissions.*.duration_to'             => ['nullable', 'integer', 'min:1', 'gte:commissions.*.duration_from', 'required_if:commissions.*.commission_type,duration', 'required_if:commissions.*.commission_type,delinquency'],
            'commissions.*.commission_rate'         => ['required', 'numeric', 'min:0.01', 'max:100.00'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Attributes
    |--------------------------------------------------------------------------
    */
    public function attributes(): array
    {
        return [
            'company_id' => 'الشركة',
            'contract_number' => 'رقم العقد',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية',
            'duration_months' => 'مدة العقد (بالأشهر)',
            'auto_renewal' => 'التجديد التلقائي',
            'commission_type' => 'نوع العمولة',
            'status' => 'حالة العقد',
            'notes' => 'الملاحظات',

            'commissions' => 'شرائح العمولات',
            'commissions.*.commission_type' => 'نوع الشريحة',
            'commissions.*.procedure_type' => 'نوع الإجراء',
            'commissions.*.duration_from' => 'المدة من',
            'commissions.*.duration_to' => 'المدة إلى',
            'commissions.*.commission_rate' => 'نسبة العمولة',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */
    public function messages(): array
    {
        return [
            'company_id.required' => 'يجب اختيار الشركة',
            'company_id.exists' => 'الشركة المختارة غير صحيحة',
            'contract_number.unique' => 'رقم العقد مستخدم مسبقاً',
            'start_date.required' => 'تاريخ البداية مطلوب',
            'start_date.after_or_equal' => 'تاريخ البداية يجب أن يكون اليوم أو بعده',
            'end_date.required' => 'تاريخ النهاية مطلوب',
            'end_date.after' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
            'duration_months.required' => 'المدة محسوبة مطلوبة',
            'commission_type.required' => 'نوع العمولة مطلوب',
            'commission_type.in' => 'نوع العمولة غير صحيح',

            'commissions.required' => 'يجب إضافة شريحة عمولة واحدة على الأقل',
            'commissions.min' => 'يجب إضافة شريحة عمولة واحدة على الأقل',
            'commissions.*.commission_type.required' => 'نوع الشريحة مطلوب',
            'commissions.*.procedure_type.required_if' => 'نوع الإجراء مطلوب عند اختيار شريحة إجراء',
            'commissions.*.duration_from.required_if' => 'المدة من مطلوبة عند اختيار شريحة مدة/تعثر',
            'commissions.*.duration_to.required_if' => 'المدة إلى مطلوبة عند اختيار شريحة مدة/تعثر',
            'commissions.*.duration_to.gte' => 'المدة إلى يجب أن تكون أكبر من أو تساوي المدة من',
            'commissions.*.commission_rate.required' => 'نسبة العمولة مطلوبة',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare
    |--------------------------------------------------------------------------
    */
    protected function prepareForValidation(): void
    {
        $start = $this->get('start_date');
        $end   = $this->get('end_date');

        if ($start && $end) {
            $months = max(1, Carbon::parse($start)->diffInMonths(Carbon::parse($end)));
            $this->merge(['duration_months' => $months]);
        }

        $this->merge([
            'auto_renewal' => $this->boolean('auto_renewal'),
            'status' => $this->get('status', 'active'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | To DTO
    |--------------------------------------------------------------------------
    */
    public function toCreateDTO(): ContractCreateDTO
    {
        return ContractCreateDTO::fromArray($this->validated());
    }
}
