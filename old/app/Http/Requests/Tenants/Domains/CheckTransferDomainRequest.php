<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Domains;

class CheckTransferDomainRequest extends ConnectDomainRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('domain.transfer') ?? false;
    }
}
