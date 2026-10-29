<?php

declare(strict_types=1);

namespace App\Http\Requests\Shared\Wizard;

use App\Services\Shared\Wizard\WizardDraftUploads;

class StoreWizardDraftRequest extends AuthenticatedWizardDraftRequest
{
    public function rules(): array
    {
        return [
            'resource_key' => ['required', 'string', 'max:255'],
            'step' => ['required', 'integer', 'min:1', 'max:100'],
            'target_path' => ['nullable', 'string', 'max:2048'],
        ] + app(WizardDraftUploads::class)->rules($this);
    }
}
