<?php

declare(strict_types=1);

namespace App\Http\Requests\Shared\Wizard;

use App\Services\Shared\Wizard\WizardDraftContext;
use Illuminate\Foundation\Http\FormRequest;

abstract class AuthenticatedWizardDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user(app(WizardDraftContext::class)->guard($this)) !== null;
    }

    public function messages(): array
    {
        return ['resource_key.required' => 'مورد المسودة مطلوب.', 'step.required' => 'خطوة النموذج مطلوبة.', 'file_key.required' => 'ملف المسودة مطلوب.'];
    }

    public function attributes(): array
    {
        return ['resource_key' => 'مورد المسودة', 'step' => 'الخطوة', 'file_key' => 'الملف', 'target_path' => 'مسار النموذج'];
    }
}
