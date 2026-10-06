<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\Site\StoreTrialRequest;
use App\Http\Requests\Site\StoreContactRequest;
use App\Http\Requests\Site\SubscriptionCheckoutRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class WebsiteFormValidationContractTest extends TestCase
{
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
        $trial = file_get_contents(resource_path('views/site/trial.blade.php'));
        $checkout = file_get_contents(resource_path('views/site/checkout.blade.php'));
        $contact = file_get_contents(resource_path('views/site/index.blade.php'));

        $this->assertStringContainsString('data-form-validate', $trial);
        $this->assertStringContainsString("route('site.form-validation')", $trial);
        $this->assertStringContainsString('data-form-validate', $checkout);
        $this->assertStringContainsString("route('site.form-validation')", $checkout);
        $this->assertStringContainsString('data-form-validate', $contact);
        $this->assertStringContainsString("route('site.form-validation')", $contact);
    }

    public function test_site_scripts_do_not_define_business_validation_rules(): void
    {
        $trial = file_get_contents(resource_path('assets/js/site/trial.js'));
        $checkout = file_get_contents(resource_path('assets/js/site/checkout.js'));
        $contact = file_get_contents(resource_path('assets/js/site/landing.js'));

        $this->assertStringNotContainsString('fieldRules', $trial);
        $this->assertStringNotContainsString('fieldRules', $checkout);
        $this->assertStringNotContainsString('validateAll', $checkout);
        $this->assertStringNotContainsString('regexp:', $trial);
        $this->assertStringNotContainsString('regexp:', $checkout);
        $this->assertStringNotContainsString('fieldRules', $contact);
        $this->assertStringNotContainsString('validateField(', $contact);
    }
}
