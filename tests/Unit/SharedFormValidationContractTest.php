<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SharedFormValidationContractTest extends TestCase
{
    public function test_wizard_next_button_stays_enabled_and_uses_final_server_validation(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2).'/resources/assets/js/shared/form-validation.js');
        $button = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/shared/form/wizard/next-button.blade.php');

        $this->assertStringContainsString('btn.disabled = false;', $script);
        $this->assertStringNotContainsString('nextButton.disabled = true;', $script);
        $this->assertStringContainsString("const currentStep = nextButton.closest('[data-form-step]');", $script);
        $this->assertStringNotContainsString("querySelectorAll('[data-form-step], .content')", $script);
        $this->assertStringContainsString('const isValid = await validateFields(form, stepFields, { force: true });', $script);
        $this->assertStringContainsString("nextButton.setAttribute('aria-busy', 'true');", $script);
        $this->assertStringContainsString("'disabled' => false", $button);
        $this->assertStringNotContainsString('@disabled($disabled)', $button);
    }

    public function test_input_server_validation_is_debounced(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2).'/resources/assets/js/shared/form-validation.js');

        $this->assertStringContainsString('const INPUT_VALIDATION_DEBOUNCE_MS = 250;', $script);
        $this->assertStringContainsString('scheduleValidation(control, step);', $script);
        $this->assertStringContainsString('clearScheduledValidation(control);', $script);
        $this->assertStringContainsString('event?.isTrusted || hasControlValue(control)', $script);
        $this->assertStringContainsString('formControls.filter(hasInitialValue)', $script);
    }

    public function test_password_confirmation_revalidates_its_matching_password_field(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2).'/resources/assets/js/shared/form-validation.js');

        $this->assertStringContainsString("const suffix = '_confirmation';", $script);
        $this->assertStringContainsString('name.endsWith(suffix)', $script);
        $this->assertStringContainsString('name.slice(0, -suffix.length)', $script);
        $this->assertStringContainsString('passwordConfirmationDependencies(control)', $script);
    }
}
