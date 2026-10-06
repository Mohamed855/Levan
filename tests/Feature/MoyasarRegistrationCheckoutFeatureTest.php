<?php

namespace Tests\Feature;

use App\Enums\Admin\Billing\BillStatus;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Plans\Plan;
use Tests\TestCase;

class MoyasarRegistrationCheckoutFeatureTest extends TestCase
{
    public function test_checkout_form_loads_successfully(): void
    {
        $plan = Plan::where('is_active', true)->first();
        $this->assertNotNull($plan, 'An active plan must exist');

        $response = $this->get(route('site.plans.checkout', $plan->id));
        $response->assertStatus(200);
        $response->assertSee($plan->name);
    }

    public function test_registration_checkout_renders_moyasar_form_without_card_payload(): void
    {
        config()->set('services.moyasar.mode', 'test');
        config()->set('services.moyasar.publishable', 'pk_test_feature_checkout');

        $plan = Plan::where('is_active', true)->first();
        $this->assertNotNull($plan, 'An active plan must exist');

        $unique = rand(10000, 99999);
        $companyName = "شركة تجريبية {$unique}";
        $commercialNumber = "1010" . rand(100000, 999999);
        $email = "demo_owner_{$unique}@example.com";
        $phone = "05" . rand(10000000, 99999999);

        $payload = [
            'billing_company_name'      => $companyName,
            'billing_commercial_number' => $commercialNumber,
            'billing_tax_number'        => '300000000000003',
            'billing_city_id'           => null,
            'billing_address'           => 'الرياض، حي الصحافة',
            'billing_name'              => 'أحمد محمد',
            'billing_email'             => $email,
            'billing_phone'             => $phone,
            'billing_password'          => 'Secret1234!',
            'billing_password_confirmation' => 'Secret1234!',
            'payment_method'            => 'card',
        ];

        $response = $this->post(route('site.plans.checkout.store', $plan->id), $payload);

        $response->assertOk();
        $response->assertSee('mysr-form', false);
        $response->assertDontSee('name="card_number"', false);
        $response->assertDontSee('name="cvc"', false);

        $pendingBill = Bill::latest('id')->first();
        $this->assertNotNull($pendingBill);
        $this->assertEquals(BillStatus::PENDING, $pendingBill->status);
    }
}
