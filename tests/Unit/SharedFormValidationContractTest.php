<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SharedFormValidationContractTest extends TestCase
{
    public function test_wizard_navigation_uses_immediate_local_state_and_final_server_validation(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/js/shared/form-validation.js');

        $this->assertStringContainsString('const valid = !getFormState(form).isValidatingStep && isStepValid(form, step);', $script);
        $this->assertStringContainsString("const currentStep = nextButton.closest('[data-form-step]');", $script);
        $this->assertStringNotContainsString("querySelectorAll('[data-form-step], .content')", $script);
        $this->assertStringContainsString('const isValid = await validateFields(form, stepFields, { force: true });', $script);
        $this->assertStringContainsString("nextButton.setAttribute('aria-busy', 'true');", $script);
    }

    public function test_input_server_validation_is_debounced(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/js/shared/form-validation.js');

        $this->assertStringContainsString('const INPUT_VALIDATION_DEBOUNCE_MS = 250;', $script);
        $this->assertStringContainsString('scheduleValidation(control, step);', $script);
        $this->assertStringContainsString('clearScheduledValidation(control);', $script);
    }
}
