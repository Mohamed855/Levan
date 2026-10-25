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
            'domain_extension' => ['required', 'string', Rule::unique('control_domains', 'domain_extension')->ignore($domain)],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
