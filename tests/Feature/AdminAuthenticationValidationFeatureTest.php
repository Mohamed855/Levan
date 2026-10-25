<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class AdminAuthenticationValidationFeatureTest extends TestCase
{
    public function test_admin_login_validation_uses_its_form_request_rules(): void
    {
        $response = $this->postJson(route('admin.form-validation'), [
            'target_path' => '/admin/login',
            'target_method' => 'POST',
            'fields' => ['email', 'password'],
            'email' => '',
            'password' => '',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function test_admin_login_validation_rejects_non_authentication_targets(): void
    {
        $this->postJson(route('admin.form-validation'), [
            'target_path' => '/admin/users',
            'target_method' => 'POST',
            'fields' => ['name'],
            'name' => 'مستخدم غير مصرح',
        ])->assertNotFound();
    }
}
