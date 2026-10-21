<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Services\Shared\Wizard\WizardDraftService;
use Illuminate\Foundation\Http\FormRequest;

abstract class WizardFormRequest extends FormRequest
{
    public function validationData(): array
    {
        if (! $this->filled('_wizard_draft_id') || ! $this->filled('_wizard_resource_key')) return parent::validationData();
        [$input, $files] = app(WizardDraftService::class)->mergeIntoRequest($this, (string) $this->input('_wizard_draft_id'), (string) $this->input('_wizard_resource_key'));
        $this->merge($input);
        $this->files->add($files);
        return parent::validationData();
    }
}