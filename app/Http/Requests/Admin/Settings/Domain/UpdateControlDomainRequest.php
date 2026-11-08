<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings\Domain;

use App\Models\Admin\ControlDomain\ControlDomain;
use Illuminate\Validation\Rule;

class UpdateControlDomainRequest extends StoreControlDomainRequest
{
    public function rules(): array
    {
        /** @var ControlDomain|null $domain */
        $domain = $this->route('domain');

        return [
            ...parent::rules(),
            'domain_extension' => [
                'bail', 'required', 'string', 'max:255', 'regex:'.self::EXTENSION_PATTERN,
                Rule::unique('control_domains', 'domain_extension')->ignore($domain),
                Rule::in($domain instanceof ControlDomain ? [$domain->domain_extension] : []),
            ],
        ];
    }
}
