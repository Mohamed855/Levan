<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

final class DisabledReadonlyInputContrastContractTest extends TestCase
{
    public function test_shared_forms_stylesheet_enforces_dark_gray_text_on_disabled_and_readonly_controls(): void
    {
        $source = file_get_contents(base_path('resources/assets/scss/shared/_forms.scss'));

        $this->assertIsString($source);
        $this->assertStringContainsString('$disabled-control-color: #495057;', $source);
        $this->assertStringContainsString('color: $disabled-control-color !important;', $source);
        $this->assertStringContainsString('-webkit-text-fill-color: $disabled-control-color !important;', $source);
        $this->assertStringContainsString('.select2-container.select2-container--disabled', $source);
        $this->assertStringContainsString('.select2-selection__rendered', $source);
        $this->assertStringContainsString('.select2-selection--multiple .select2-selection__choice', $source);
        $this->assertStringContainsString('.flatpickr-input:disabled', $source);
        $this->assertStringContainsString('textarea[readonly]', $source);
        $this->assertStringContainsString('select[readonly]', $source);
    }

    public function test_web_and_auth_stylesheets_enforce_dark_gray_text_on_disabled_and_readonly_controls(): void
    {
        $webForms = file_get_contents(base_path('resources/assets/scss/web/_forms.scss'));
        $authLayout = file_get_contents(base_path('resources/assets/css/shared/auth-layout.css'));
        $tenantLogin = file_get_contents(base_path('resources/assets/css/tenant/auth/login.css'));

        $this->assertIsString($webForms);
        $this->assertStringContainsString('.site-input:disabled', $webForms);
        $this->assertStringContainsString('.select2-container.select2-container--disabled', $webForms);
        $this->assertStringContainsString('color: #495057 !important;', $webForms);
        $this->assertStringContainsString('-webkit-text-fill-color: #495057 !important;', $webForms);

        $this->assertIsString($authLayout);
        $this->assertStringContainsString('.auth-form-side .form-control:disabled', $authLayout);
        $this->assertStringContainsString('.auth-form-side .form-control[readonly]:not([type="search"])', $authLayout);
        $this->assertStringContainsString('color: #495057 !important;', $authLayout);
        $this->assertStringContainsString('-webkit-text-fill-color: #495057 !important;', $authLayout);

        $this->assertIsString($tenantLogin);
        $this->assertStringContainsString('.tenant-login-form-side .form-control:disabled', $tenantLogin);
        $this->assertStringContainsString('.tenant-login-form-side .form-control[readonly]:not([type="search"])', $tenantLogin);
        $this->assertStringContainsString('color: #495057 !important;', $tenantLogin);
        $this->assertStringContainsString('-webkit-text-fill-color: #495057 !important;', $tenantLogin);
    }
}
