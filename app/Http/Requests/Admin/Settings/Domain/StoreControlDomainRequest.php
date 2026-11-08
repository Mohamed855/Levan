<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings\Domain;

use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Validation\Rule;

class StoreControlDomainRequest extends WizardFormRequest
{
    protected const EXTENSION_PATTERN = '/^\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*$/iD';

    public function authorize(): bool
    {
        return $this->user('admin')?->can('domain_extension_prices.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'domain_extension' => ['bail', 'required', 'string', 'max:255', 'regex:'.self::EXTENSION_PATTERN, Rule::unique('control_domains', 'domain_extension')],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
        ];
    }

    public function messages(): array
    {
        return [
            'domain_extension.regex' => 'أدخل امتداد نطاق صالحاً يبدأ بنقطة، مثل .com أو .com.sa.',
            'domain_extension.in' => 'امتداد النطاق ثابت ولا يمكن تعديله.',
            'price.decimal' => 'السعر يجب أن يحتوي على منزلتين عشريتين كحد أقصى.',
        ];
    }

    public function attributes(): array
    {
        return [
            'domain_extension' => 'الامتداد',
            'price' => 'السعر',
        ];
    }
}
