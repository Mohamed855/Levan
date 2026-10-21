<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AuctionTemplates;

use App\Models\Admin\AuctionTemplate;
use App\Rules\TemplateSchemaRule;
use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Validation\Rule;

final class StoreAuctionTemplateRequest extends WizardFormRequest
{
    public function authorize(): bool { return $this->user('admin') !== null; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('auction_templates', 'name')->whereNull('deleted_at')],
            'type' => ['required', Rule::in(AuctionTemplate::TYPES)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'schema' => ['required', 'json', new TemplateSchemaRule((string) $this->input('type'))],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'اسم القالب', 'type' => 'نوع القالب', 'schema' => 'مخطط القالب'];
    }
}
