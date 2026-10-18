<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\Site\SubscriptionCheckoutRequest;
use PHPUnit\Framework\TestCase;

final class SiteCheckoutComponentContractTest extends TestCase
{
    public function test_checkout_uses_shared_form_components_for_fields(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/site/checkout.blade.php');

        $this->assertStringContainsString('<x-form.controls.input', $view);
        $this->assertStringContainsString('<x-form.controls.select', $view);
        $this->assertStringContainsString('<x-form.controls.password', $view);
        $this->assertStringContainsString("name=\"billing_city_id\"", $view);
        $this->assertStringContainsString("name=\"billing_commercial_number\"", $view);
        $this->assertStringContainsString("name=\"billing_tax_number\"", $view);
    }

    public function test_checkout_numeric_identifiers_have_digit_only_length_rules(): void
    {
        $rules = app(SubscriptionCheckoutRequest::class)->rules();

        $this->assertContains('regex:/^\\d{10}$/', $rules['billing_commercial_number']);
        $this->assertContains('regex:/^\\d{15}$/', $rules['billing_tax_number']);
    }
}
