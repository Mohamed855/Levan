<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class WebsiteFormValidationFeatureTest extends TestCase
{
    public function test_contact_form_uses_its_form_request_through_the_shared_endpoint(): void
    {
        $response = $this->postJson(route('site.form-validation'), [
            'target_path' => '/contact',
            'target_method' => 'POST',
            'fields' => ['customer_name'],
            'customer_name' => 'أ',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['customer_name']);
    }

    public function test_shared_endpoint_accepts_an_explicitly_registered_public_form_action(): void
    {
        $response = $this->postJson(route('site.form-validation'), [
            'target_path' => '/contact',
            'target_method' => 'POST',
            'fields' => ['customer_name'],
            'customer_name' => 'اسم صحيح',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
    }
}
