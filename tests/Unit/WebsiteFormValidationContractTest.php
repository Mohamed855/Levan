<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\Site\StoreContactRequest;
use App\Http\Requests\Site\StoreTrialRequest;
use App\Http\Requests\Site\SubscriptionCheckoutRequest;
use ReflectionClass;
use Tests\TestCase;

class WebsiteFormValidationContractTest extends TestCase
{
    public function test_checkout_controller_delegates_bank_transfer_storage_and_bill_mutation_to_the_billing_service(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/Site/SubscriptionController.php');

        $this->assertStringContainsString('submitBankTransfer(', $controller);
        $this->assertDoesNotMatchRegularExpression('/\$request->file\([^)]*transfer_receipt[^)]*\)->store\(/', $controller);
        $this->assertDoesNotMatchRegularExpression('/\$bill->update\s*\(/', $controller);
    }

    public function test_public_form_requests_are_the_source_of_truth(): void
    {
        foreach ([StoreContactRequest::class, StoreTrialRequest::class, SubscriptionCheckoutRequest::class] as $requestClass) {
            $request = new ReflectionClass($requestClass);
            $this->assertTrue($request->hasMethod('authorize'));
            $this->assertTrue($request->hasMethod('rules'));
        }
    }

    public function test_public_forms_use_the_shared_validation_endpoint(): void
    {
        $trial = file_get_contents(resource_path('views/web/trial.blade.php'));
        $checkout = file_get_contents(resource_path('views/web/checkout.blade.php'));
        $contact = file_get_contents(resource_path('views/web/index.blade.php'));

        $this->assertStringContainsString('data-form-validate', $trial);
        $this->assertStringContainsString("route('site.form-validation')", $trial);
        $this->assertStringContainsString('data-form-validate', $checkout);
        $this->assertStringContainsString("route('site.form-validation')", $checkout);
        $this->assertStringContainsString('data-form-validate', $contact);
        $this->assertStringContainsString("route('site.form-validation')", $contact);
    }

    public function test_site_scripts_do_not_define_business_validation_rules(): void
    {
        $trial = file_get_contents(resource_path('assets/js/web/trial.js'));
        $checkout = file_get_contents(resource_path('assets/js/web/checkout.js'));
        $contact = file_get_contents(resource_path('assets/js/web/landing.js'));

        $this->assertStringNotContainsString('fieldRules', $trial);
        $this->assertStringNotContainsString('fieldRules', $checkout);
        $this->assertStringNotContainsString('validateAll', $checkout);
        $this->assertStringNotContainsString('regexp:', $trial);
        $this->assertStringNotContainsString('regexp:', $checkout);
        $this->assertStringNotContainsString('fieldRules', $contact);
        $this->assertStringNotContainsString('validateField(', $contact);
    }

    public function test_duplicate_billing_email_validation_message_is_arabic_friendly(): void
    {
        $response = $this->postJson(route('site.form-validation'), [
            'target_path' => '/trial',
            'target_method' => 'POST',
            'fields' => ['billing_email'],
            'billing_email' => 'superadmin@gmail.com',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['billing_email' => 'البريد الإلكتروني موجود بالفعل']);
    }
}
