<?php

namespace App\Http\Requests\Tenants\FinancialCollection\Collection;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('collections.edit') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $collection = $this->route('collection');
        $collectionId = is_object($collection) ? ($collection->id ?? $collection->getKey()) : $collection;

        return [
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],
            'collected_amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999999.99',
            ],
            'collection_type' => [
                'required',
                'string',
                Rule::in(['cash', 'scheduled']),
            ],
            'payment_method_id' => [
                'required',
                'integer',
                'exists:settings_payment_methods,id',
            ],
            'receipt_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('collections', 'receipt_number')->ignore($collectionId)->whereNull('deleted_at'),
            ],
            'closing_code' => [
                'nullable',
                'string',
                'max:255',
            ],
            'collection_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'payment_type' => [
                'required',
                'string',
                Rule::in(['settlement', 'without_settlement']),
            ],
            'attachment' => [
                'nullable',
                'file',
                'max:10240', // 10MB
                'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            ],
            'remove_attachment' => [
                'nullable',
                'in:0,1,true,false,on,off', // قبول القيم المختلفة من HTML forms
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'يرجى اختيار العميل',
            'customer_id.exists' => 'العميل المحدد غير موجود',

            'collected_amount.required' => 'يرجى إدخال المبلغ المحصل',
            'collected_amount.numeric' => 'المبلغ المحصل يجب أن يكون رقماً',
            'collected_amount.min' => 'المبلغ المحصل يجب أن يكون أكبر من صفر',
            'collected_amount.max' => 'المبلغ المحصل كبير جداً',

            'collection_type.required' => 'يرجى اختيار نوع التحصيل',
            'collection_type.in' => 'نوع التحصيل المحدد غير صحيح',

            'payment_method_id.required' => 'يرجى اختيار طريقة التحصيل',
            'payment_method_id.exists' => 'طريقة التحصيل المحددة غير موجودة',

            'receipt_number.required' => 'يرجى إدخال رقم السند',
            'receipt_number.unique' => 'رقم السند موجود مسبقاً',
            'receipt_number.max' => 'رقم السند طويل جداً',

            'closing_code.max' => 'رمز الإقفال طويل جداً',

            'collection_date.required' => 'يرجى إدخال تاريخ التحصيل',
            'collection_date.date' => 'تاريخ التحصيل غير صحيح',
            'collection_date.before_or_equal' => 'تاريخ التحصيل لا يمكن أن يكون في المستقبل',

            'payment_type.required' => 'يرجى اختيار نوع السداد',
            'payment_type.in' => 'نوع السداد المحدد غير صحيح',

            'attachment.file' => 'المرفق يجب أن يكون ملفاً',
            'attachment.max' => 'حجم المرفق يجب أن يكون أقل من 10 ميجابايت',
            'attachment.mimes' => 'نوع المرفق غير مدعوم. الأنواع المدعومة: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX',

            'remove_attachment.in' => 'قيمة حذف المرفق غير صحيحة',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'العميل',
            'collected_amount' => 'المبلغ المحصل',
            'collection_type' => 'نوع التحصيل',
            'payment_method_id' => 'طريقة التحصيل',
            'receipt_number' => 'رقم السند',
            'closing_code' => 'رمز الإقفال',
            'collection_date' => 'تاريخ التحصيل',
            'payment_type' => 'نوع السداد',
            'attachment' => 'المرفق',
            'remove_attachment' => 'حذف المرفق',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->route('customer')) {
            $customer = $this->route('customer');
            $this->merge([
                'customer_id' => is_object($customer) ? ($customer->id ?? $customer->getKey()) : $customer,
            ]);
        }

        // تنظيف المبلغ من الفواصل والرموز
        if ($this->has('collected_amount')) {
            $amount = str_replace([',', ' ', 'ريال'], '', $this->collected_amount);
            $this->merge([
                'collected_amount' => $amount,
            ]);
        }

        // تنظيف رقم السند
        if ($this->has('receipt_number')) {
            $this->merge([
                'receipt_number' => trim($this->receipt_number),
            ]);
        }

        // تحويل remove_attachment إلى boolean صحيح
        if ($this->has('remove_attachment')) {
            $removeAttachment = $this->input('remove_attachment');

            // تحويل القيم المختلفة إلى boolean
            $booleanValue = in_array($removeAttachment, ['1', 'true', 'on', true, 1], true);

            $this->merge([
                'remove_attachment' => $booleanValue,
            ]);
        }
    }

    /**
     * Get validated data with proper boolean conversion
     */
    public function getValidatedData(): array
    {
        $data = $this->validated();

        // التأكد من أن remove_attachment boolean
        if (isset($data['remove_attachment'])) {
            $data['remove_attachment'] = (bool) $data['remove_attachment'];
        }

        return $data;
    }
}
