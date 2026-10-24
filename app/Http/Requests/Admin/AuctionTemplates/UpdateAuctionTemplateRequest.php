<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AuctionTemplates;

use App\Models\Admin\AuctionTemplate;
use App\Rules\TemplateSchemaRule;
use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Validation\Rule;

final class UpdateAuctionTemplateRequest extends WizardFormRequest
{
    public function authorize(): bool { return $this->user('admin')?->hasPermissionTo('auction_templates.edit') ?? false; }

    public function rules(): array
    {
        /** @var AuctionTemplate $template */
        $template = $this->route('auctionTemplate');
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('auction_templates', 'name')->ignore($template->id)->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'schema' => ['nullable', 'json', new TemplateSchemaRule($template->type)],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'اسم القالب', 'schema' => 'مخطط القالب'];
    }
}
