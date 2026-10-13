<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\Import;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ImportCustomersRequest extends FormRequest
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
            'portfolio_id' => [
                'required',
                'integer',
                'exists:portfolios,id',
            ],
            'import_file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240', // 10MB
            ],
            'confirm_new_fields' => [
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'portfolio_id.required' => 'يرجى اختيار المحفظة',
            'portfolio_id.exists' => 'المحفظة المحددة غير موجودة',

            'import_file.required' => 'يرجى اختيار ملف للاستيراد',
            'import_file.file' => 'الملف المرفق غير صحيح',
            'import_file.mimes' => 'يجب أن يكون الملف من نوع Excel (.xlsx, .xls)',
            'import_file.max' => 'حجم الملف كبير جداً. الحد الأقصى 10 ميجابايت',

            'confirm_new_fields.boolean' => 'قيمة تأكيد الحقول الجديدة غير صحيحة',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'portfolio_id' => 'المحفظة',
            'import_file' => 'ملف الاستيراد',
            'confirm_new_fields' => 'تأكيد الحقول الجديدة',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            $response = response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors(),
            ], 422);

            throw new ValidationException($validator, $response);
        }

        parent::failedValidation($validator);
    }
}
