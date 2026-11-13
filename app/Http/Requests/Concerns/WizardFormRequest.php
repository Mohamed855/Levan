<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Services\Shared\Wizard\WizardDraftService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Throwable;

abstract class WizardFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        $this->preserveFailedDraft($validator);

        parent::failedValidation($validator);
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        if ($this->filled('_wizard_resource_key') && app()->bound('request')) {
            app('request')->attributes->set('wizard_validated_request', $this);
        }
    }

    public function preserveValidationException(ValidationException $exception): void
    {
        $validator = $this->getValidatorInstance();
        $validator->errors()->merge($exception->errors());
        $this->preserveFailedDraft($validator);
    }

    private function preserveFailedDraft(Validator $validator): void
    {
        $resourceKey = $this->input('_wizard_resource_key');
        if (! $this->expectsJson() && ! $this->isJson() && ! $this->ajax() && $this->hasSession()
            && is_string($resourceKey) && $resourceKey !== '' && strlen($resourceKey) <= 255) {
            try {
                $files = [];
                $rules = $validator->getRules();
                $errors = $validator->errors()->keys();
                foreach (Arr::dot($this->allFiles()) as $field => $file) {
                    if (! $file instanceof UploadedFile || ! $file->isValid()) {
                        continue;
                    }
                    $fileRules = $rules[$field] ?? [];
                    $isFileField = count(array_filter($fileRules, fn ($rule): bool => $rule instanceof File
                        || (is_string($rule) && preg_match('/^(file|image|mimes|mimetypes)(:|$)/i', $rule)))) > 0;
                    $hasError = count(array_filter($errors, fn (string $error): bool => $field === $error || str_starts_with($field, $error.'.'))) > 0;
                    if ($isFileField && ! $hasError) {
                        Arr::set($files, $field, $file);
                    }
                }

                $draftRequest = Request::createFrom($this);
                $draftRequest->files->replace($files);
                $draft = app(WizardDraftService::class)->save($draftRequest, $resourceKey, max(1, min(100, (int) $this->input('_wizard_step', 1))));
                $this->merge(['_wizard_draft_id' => $draft->getKey()]);
                if (app()->bound('request')) {
                    app('request')->merge(['_wizard_draft_id' => $draft->getKey()]);
                }
                $this->session()->flash('_wizard_failed_draft', ['id' => $draft->getKey(), 'resource_key' => $resourceKey]);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    public function validationData(): array
    {
        if (! $this->filled('_wizard_draft_id') || ! $this->filled('_wizard_resource_key')) {
            return parent::validationData();
        }

        [$input, $files] = app(WizardDraftService::class)->mergeIntoRequest(
            $this,
            (string) $this->input('_wizard_draft_id'),
            (string) $this->input('_wizard_resource_key'),
        );

        $this->merge($input);
        $this->files->add($files);
        // allFiles() was read while merging the draft; invalidate that cached list.
        $this->convertedFiles = null;
        $this->attributes->set('wizard_draft_id', (string) $this->input('_wizard_draft_id'));
        $this->attributes->set('wizard_resource_key', (string) $this->input('_wizard_resource_key'));
        $this->attributes->set('wizard_tenant_id', tenant()?->getKey() ?? '');

        if (app()->bound('request')) {
            $rootRequest = app('request');
            $rootRequest->attributes->set('wizard_draft_id', (string) $this->input('_wizard_draft_id'));
            $rootRequest->attributes->set('wizard_resource_key', (string) $this->input('_wizard_resource_key'));
            $rootRequest->attributes->set('wizard_tenant_id', tenant()?->getKey() ?? '');
        }

        $this->prepareForValidation();

        return parent::validationData();
    }
}
