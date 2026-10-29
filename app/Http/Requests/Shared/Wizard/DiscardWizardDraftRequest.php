<?php

declare(strict_types=1);

namespace App\Http\Requests\Shared\Wizard;

class DiscardWizardDraftRequest extends AuthenticatedWizardDraftRequest
{
    public function rules(): array
    {
        return [
            'resource_key' => ['required', 'string', 'max:255'],
        ];
    }
}
