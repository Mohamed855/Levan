<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Communication;

use Illuminate\Foundation\Http\FormRequest;

class ExportMessageIndividualLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('message_logs.export') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'max:100'],
            'message_type' => ['nullable', 'string', 'max:100'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'template_id' => ['nullable', 'integer', 'exists:message_templates,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['date_to.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية أو مساوياً له.'];
    }

    public function attributes(): array
    {
        return ['status' => 'الحالة', 'message_type' => 'نوع الرسالة', 'company_id' => 'الشركة', 'portfolio_id' => 'المحفظة', 'template_id' => 'القالب', 'customer_id' => 'العميل', 'date_from' => 'من تاريخ', 'date_to' => 'إلى تاريخ', 'search' => 'البحث'];
    }
}
