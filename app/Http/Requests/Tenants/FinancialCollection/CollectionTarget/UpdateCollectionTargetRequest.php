<?php

namespace App\Http\Requests\Tenants\FinancialCollection\CollectionTarget;

use App\Data\Tenants\FinancialCollection\Target\UpdateCollectionTargetDTO;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Tenants\FinancialCollection\Target\CollectionTarget;
use Illuminate\Validation\Rule;

class UpdateCollectionTargetRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | authorize
    |--------------------------------------------------------------------------
    | Determine if the user is authorized to make this request.
    */
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('collection_targets.edit') ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Get the validation rules that apply to the request.
    |--------------------------------------------------------------------------
    */
    public function rules(): array
    {
        $currentYear = date('Y');
        $rules = [
            'user_id' => 'required|exists:users,id',
            'target_type' => 'required|in:monthly,yearly,custom',
            'target_amount' => 'required|numeric|min:0.01|max:999999999.99',
            'status' => 'nullable|in:active,inactive'
        ];

        switch ($this->target_type) {
            case 'monthly':
                $rules['target_month'] = 'required|integer|min:1|max:12';
                $rules['target_year'] = "required|integer|min:{$currentYear}|max:" . ($currentYear + 5);
                break;
            case 'yearly':
                $rules['target_year'] = "required|integer|min:{$currentYear}|max:" . ($currentYear + 5);
                break;
            case 'custom':
                $rules['custom_start_date'] = 'required|date';
                $rules['custom_end_date'] = 'required|date|after:custom_start_date';
                break;
        }

        return $rules;
    }

    /*
    |--------------------------------------------------------------------------
    | Get custom error messages for validator errors.
    |--------------------------------------------------------------------------
    */
    public function messages(): array
    {
        $currentYear = date('Y');
        return [
            'user_id.required' => 'يرجى اختيار المستخدم',
            'user_id.exists' => 'المستخدم المختار غير صحيح',
            'target_type.required' => 'يرجى اختيار نوع الهدف',
            'target_type.in' => 'نوع الهدف المختار غير صحيح',
            'target_amount.required' => 'يرجى إدخال مبلغ الهدف',
            'target_amount.numeric' => 'مبلغ الهدف يجب أن يكون رقماً صحيحاً',
            'target_amount.min' => 'مبلغ الهدف يجب أن يكون أكبر من صفر',
            'target_amount.max' => 'مبلغ الهدف كبير جداً (الحد الأقصى 999,999,999.99)',
            'target_month.required' => 'يرجى اختيار الشهر',
            'target_month.integer' => 'الشهر يجب أن يكون رقماً صحيحاً',
            'target_month.min' => 'يجب اختيار شهر صحيح (1-12)',
            'target_month.max' => 'يجب اختيار شهر صحيح (1-12)',
            'target_year.required' => 'يرجى اختيار السنة',
            'target_year.integer' => 'السنة يجب أن تكون رقماً صحيحاً',
            'target_year.min' => "السنة يجب أن تكون من {$currentYear} فما فوق",
            'target_year.max' => "السنة يجب أن تكون حتى " . ($currentYear + 5),
            'custom_start_date.required' => 'يرجى اختيار تاريخ بداية الفترة',
            'custom_start_date.date' => 'تاريخ البداية غير صحيح',
            'custom_end_date.required' => 'يرجى اختيار تاريخ نهاية الفترة',
            'custom_end_date.date' => 'تاريخ النهاية غير صحيح',
            'custom_end_date.after' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
            'status.in' => 'حالة الهدف غير صحيحة'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get custom attributes for validator errors.
    |--------------------------------------------------------------------------
    */
    public function attributes(): array
    {
        return [
            'user_id' => 'المستخدم',
            'target_type' => 'نوع الهدف',
            'target_month' => 'الشهر',
            'target_year' => 'السنة',
            'custom_start_date' => 'تاريخ البداية',
            'custom_end_date' => 'تاريخ النهاية',
            'target_amount' => 'مبلغ الهدف',
            'status' => 'الحالة'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare the data for validation.
    |--------------------------------------------------------------------------
    */
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('target_amount')) {
            $data['target_amount'] = str_replace([',', ' '], '', $this->target_amount);
        }

        switch ($this->target_type) {
            case 'monthly':
                $data['custom_start_date'] = null;
                $data['custom_end_date'] = null;
                break;
            case 'yearly':
                $data['target_month'] = null;
                $data['custom_start_date'] = null;
                $data['custom_end_date'] = null;
                break;
            case 'custom':
                $data['target_month'] = null;
                $data['target_year'] = null;
                break;
        }

        $this->merge($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Configure the validator instance.
    |--------------------------------------------------------------------------
    */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validatePeriodUniqueness($validator);

            if ($this->target_type === 'custom') {
                $this->validateCustomPeriodOverlap($validator);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Period Uniqueness
    |--------------------------------------------------------------------------
    */
    private function validatePeriodUniqueness($validator): void
    {
        $collectionTargetId = $this->route('collection_target')->id;
        $query = CollectionTarget::where('user_id', $this->user_id)
            ->where('target_type', $this->target_type)
            ->where('id', '!=', $collectionTargetId)
            ->whereNull('deleted_at');

        $existingTarget = null;
        $errorMessage = '';
        $errorField = '';

        switch ($this->target_type) {
            case 'monthly':
                $existingTarget = $query->where('target_month', $this->target_month)
                    ->where('target_year', $this->target_year)
                    ->first();

                if ($existingTarget) {
                    $monthNames = [
                        1 => 'يناير',
                        2 => 'فبراير',
                        3 => 'مارس',
                        4 => 'أبريل',
                        5 => 'مايو',
                        6 => 'يونيو',
                        7 => 'يوليو',
                        8 => 'أغسطس',
                        9 => 'سبتمبر',
                        10 => 'أكتوبر',
                        11 => 'نوفمبر',
                        12 => 'ديسمبر'
                    ];
                    $monthName = $monthNames[$this->target_month] ?? $this->target_month;
                    $errorMessage = "يوجد هدف مسبق لهذا المستخدم في شهر {$monthName} {$this->target_year}. يرجى اختيار فترة أخرى.";
                    $errorField = 'target_month';
                }
                break;

            case 'yearly':
                $existingTarget = $query->where('target_year', $this->target_year)->first();

                if ($existingTarget) {
                    $errorMessage = "يوجد هدف مسبق لهذا المستخدم في عام {$this->target_year}. يرجى اختيار سنة أخرى.";
                    $errorField = 'target_year';
                }
                break;

            case 'custom':
                // سيتم التحقق من التداخل في الدالة المخصصة
                return;
        }

        if ($existingTarget) {
            $validator->errors()->add($errorField, $errorMessage);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Custom Period Overlap
    |--------------------------------------------------------------------------
    */
    private function validateCustomPeriodOverlap($validator): void
    {
        if (!$this->custom_start_date || !$this->custom_end_date) {
            return;
        }

        $collectionTargetId = $this->route('collection_target')->id;
        $existingTarget = CollectionTarget::where('user_id', $this->user_id)
            ->where('target_type', 'custom')
            ->where('id', '!=', $collectionTargetId)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereBetween('custom_start_date', [$this->custom_start_date, $this->custom_end_date])
                    ->orWhereBetween('custom_end_date', [$this->custom_start_date, $this->custom_end_date])
                    ->orWhere(function ($q) {
                        $q->where('custom_start_date', '<=', $this->custom_start_date)
                            ->where('custom_end_date', '>=', $this->custom_end_date);
                    });
            })
            ->first();

        if ($existingTarget) {
            $startDate = date('d/m/Y', strtotime($existingTarget->custom_start_date));
            $endDate = date('d/m/Y', strtotime($existingTarget->custom_end_date));
            $errorMessage = "يوجد هدف مسبق لهذا المستخدم في الفترة من {$startDate} إلى {$endDate}. الفترة المحددة تتداخل مع هدف موجود مسبقاً.";
            $validator->errors()->add('custom_start_date', $errorMessage);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get DTO from Request
    |--------------------------------------------------------------------------
    | Convert validated request data to DTO.
    */
    public function toDTO(): UpdateCollectionTargetDTO
    {
        return UpdateCollectionTargetDTO::fromArray($this->validated());
    }
}
