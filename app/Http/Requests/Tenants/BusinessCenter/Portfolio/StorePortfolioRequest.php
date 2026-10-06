<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Portfolio;

use App\Data\Tenants\BusinessCenter\Portfolio\PortfolioCreateDTO;
use App\Http\Requests\Concerns\WizardFormRequest;

class StorePortfolioRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('portfolios.create') ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Rules
    |--------------------------------------------------------------------------
    */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'exists:companies,id'],
            'assignment_date' => ['nullable', 'date'],
            'call_start_time' => ['nullable', 'date_format:H:i'],
            'call_end_time' => ['nullable', 'date_format:H:i', 'after:call_start_time'],
            'has_split_names' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:active,inactive'],

            'field_names' => ['nullable', 'array', 'min:1'],
            'field_names.*' => ['required_with:field_names', 'string', 'max:255'],

            'field_types' => ['nullable', 'array'],
            'field_types.*' => ['required_with:field_names', 'in:text,numeric,date'],

            'field_required_flags' => ['nullable', 'array'],
            'field_required_flags.*' => ['nullable', 'in:0,1'],

            'field_ids' => ['nullable', 'array'],
            'field_ids.*' => ['nullable', 'integer'],
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
            'name.required' => 'اسم المحفظة مطلوب',
            'name.max' => 'اسم المحفظة يجب ألا يتجاوز 255 حرف',
            'company_id.required' => 'الشركة مطلوبة',
            'company_id.exists' => 'الشركة المحددة غير موجودة',
            'assignment_date.date' => 'تاريخ الإسناد يجب أن يكون تاريخ صحيح',
            'call_start_time.date_format' => 'وقت بدء الاتصال يجب أن يكون بصيغة صحيحة (00:00)',
            'call_end_time.date_format' => 'وقت نهاية الاتصال يجب أن يكون بصيغة صحيحة (00:00)',
            'call_end_time.after' => 'وقت نهاية الاتصال يجب أن يكون بعد وقت البدء',
            'has_split_names.boolean' => 'خاصية تقسيم الأسماء يجب أن تكون صحيحة أو خاطئة',
            'status.in' => 'حالة المحفظة يجب أن تكون نشط أو غير نشط',

            'field_names.array' => 'أسماء الحقول يجب أن تكون قائمة',
            'field_names.min' => 'يجب إضافة حقل واحد على الأقل',
            'field_names.*.required_with' => 'اسم الحقل مطلوب',
            'field_names.*.string' => 'اسم الحقل يجب أن يكون نص',
            'field_names.*.max' => 'اسم الحقل يجب ألا يتجاوز 255 حرف',

            'field_types.array' => 'أنواع الحقول يجب أن تكون قائمة',
            'field_types.*.required_with' => 'نوع الحقل مطلوب',
            'field_types.*.in' => 'نوع الحقل يجب أن يكون نص أو رقمي أو تاريخ',

            'field_required_flags.array' => 'خاصية الحقول المطلوبة يجب أن تكون قائمة',
            'field_required_flags.*.in' => 'خاصية الحقل المطلوب يجب أن تكون 0 أو 1',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare For Validation
    |--------------------------------------------------------------------------
    */
    protected function prepareForValidation(): void
    {
        $fieldNames = [];
        if ($this->has('field_names') && is_array($this->input('field_names'))) {
            $fieldNames = array_values(array_filter($this->input('field_names'), fn($name) => trim((string)$name) !== ''));
            $this->merge(['field_names' => $fieldNames]);
        }

        $count = count($fieldNames);

        $types = $this->input('field_types', []);
        $types = array_slice($types, 0, $count);
        $types = array_pad($types, $count, 'text');
        $this->merge(['field_types' => array_values($types)]);

        $flags = [];
        $all = $this->input('field_required_flags', []);
        for ($i = 0; $i < $count; $i++) {
            $flags[] = isset($all[$i]) && (string)$all[$i] === '1' ? '1' : '0';
        }
        $this->merge(['field_required_flags' => $flags]);

        if ($this->has('field_ids') && is_array($this->input('field_ids'))) {
            $ids = array_slice($this->input('field_ids'), 0, $count);
            $this->merge(['field_ids' => array_values($ids)]);
        }

        $this->merge(['has_split_names' => $this->boolean('has_split_names')]);
    }

    /*
    |--------------------------------------------------------------------------
    | With Validator
    |--------------------------------------------------------------------------
    */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateFieldConsistency($validator);
            $this->validateCallTimeRange($validator);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Field Consistency
    |--------------------------------------------------------------------------
    */
    protected function validateFieldConsistency($validator): void
    {
        $names = $this->input('field_names', []);
        $types = $this->input('field_types', []);
        $reqs  = $this->input('field_required_flags', []);

        if (count($names) !== count($types) || count($names) !== count($reqs)) {
            $validator->errors()->add('field_names', 'يجب مطابقة عدد الأنواع وعدد حقول "مطلوب" مع أسماء الحقول');
        }

        $dupNames = array_diff_assoc($names, array_unique($names));
        if (!empty($dupNames)) {
            $validator->errors()->add('field_names', 'لا يمكن تكرار أسماء الحقول');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    protected function validateCallTimeRange($validator): void
    {
        $startTime = $this->input('call_start_time');
        $endTime = $this->input('call_end_time');

        if (($startTime && !$endTime) || (!$startTime && $endTime)) {
            $validator->errors()->add('call_time', 'يجب إدخال وقت البدء والنهاية معاً أو تركهما فارغين');
        }

        if ($startTime && $endTime) {
            $start = \Carbon\Carbon::createFromFormat('H:i', $startTime);
            $end = \Carbon\Carbon::createFromFormat('H:i', $endTime);

            if ($end->lessThanOrEqualTo($start)) {
                $validator->errors()->add('call_end_time', 'وقت نهاية الاتصال يجب أن يكون بعد وقت البدء');
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | To DTO
    |--------------------------------------------------------------------------
    */
    public function toCreateDTO(): PortfolioCreateDTO
    {
        $validated = $this->validated();
        $validated['user_id'] = auth()->id();
        return PortfolioCreateDTO::fromArray($validated);
    }
}
