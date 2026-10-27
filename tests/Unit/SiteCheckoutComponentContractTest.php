<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\Site\SubscriptionCheckoutRequest;
use PHPUnit\Framework\TestCase;

final class SiteCheckoutComponentContractTest extends TestCase
{
    public function test_checkout_uses_shared_form_components_for_fields(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/web/checkout.blade.php');
        $this->assertStringContainsString("@include('web.partials.registration-fields')", $view);
        $view .= file_get_contents(dirname(__DIR__, 2).'/resources/views/web/partials/registration-fields.blade.php');

        $this->assertStringContainsString('<x-form.controls.input', $view);
        $this->assertStringContainsString('<x-form.controls.select', $view);
        $this->assertStringContainsString('<x-form.controls.password', $view);
        $this->assertStringContainsString('name="billing_city_id"', $view);
        $this->assertStringContainsString('name="billing_commercial_number"', $view);
        $this->assertStringContainsString('name="billing_tax_number"', $view);
        $this->assertStringContainsString('data-digits-only', $view);
    }

    public function test_checkout_numeric_identifiers_have_digit_only_length_rules(): void
    {
        $rules = app(SubscriptionCheckoutRequest::class)->rules();

        $this->assertContains('regex:/^\d{10}$/', $rules['billing_commercial_number']);
        $this->assertContains('regex:/^3\d{13}3$/', $rules['billing_tax_number']);
    }

    public function test_landing_page_hero_subscription_button_targets_checkout_only_for_single_plan_otherwise_pricing_section(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/web/index.blade.php');

        $this->assertStringContainsString(
            "\$plans->count() === 1 ? route('site.plans.checkout', \$plans->first()->id) : '#pricing'",
            $view
        );
    }

    public function test_package_card_features_section_is_omitted_when_no_features_and_not_in_preview_mode(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/shared/ui/package-card.blade.php');

        $this->assertStringContainsString('@if ($preview || !empty($features))', $view);
        $this->assertStringContainsString('package-card__empty-feature', $view);
    }
}
