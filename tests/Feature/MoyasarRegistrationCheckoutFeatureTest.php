<?php

namespace Tests\Feature;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Tenants\Tenant;
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

    public function test_registration_checkout_with_demo_card_succeeds_without_error(): void
    {
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
            'card_number'               => '4111 1111 1111 1111',
            'card_name_display'         => 'AHMED MOHAMED',
            'exp_date_display'          => '12/28',
            'cvc'                       => '123',
        ];

        // 1. Submit the registration checkout form
        $response = $this->post(route('site.plans.checkout.store', $plan->id), $payload);

        // 2. Must redirect to payment transaction URL (which points to callback with demo payment id)
        $response->assertStatus(302);
        $redirectUrl = $response->headers->get('Location');
        $this->assertNotNull($redirectUrl);
        $this->assertStringContainsString('checkout/callback', $redirectUrl);
        $this->assertStringContainsString('id=pay_demo_', $redirectUrl);
        $this->assertStringContainsString('bill=', $redirectUrl);

        // Parse query params to extract bill and payment ID
        $queryParts = parse_url($redirectUrl, PHP_URL_QUERY);
        parse_str($queryParts, $params);
        $this->assertArrayHasKey('bill', $params);
        $this->assertArrayHasKey('id', $params);

        $billNumber = $params['bill'];
        $paymentId = $params['id'];

        $pendingBill = Bill::where('bill_number', $billNumber)->first();
        $this->assertNotNull($pendingBill);
        $this->assertEquals(BillStatus::PENDING, $pendingBill->status);

        // 3. Process the callback (Moyasar Callback Handler)
        $callbackResponse = $this->get(route('site.plans.checkout.callback', [
            'id'     => $paymentId,
            'bill'   => $billNumber,
            'status' => 'paid',
        ]));

        // 4. Must redirect to tenant login URL
        $callbackResponse->assertStatus(302);
        $tenantLoginUrl = $callbackResponse->headers->get('Location');
        $this->assertStringContainsString('/login', $tenantLoginUrl);

        // 5. Verify database records
        $pendingBill->refresh();
        $this->assertEquals(BillStatus::PAID, $pendingBill->status);
        $this->assertNotNull($pendingBill->tenant_id);

        $tenant = Tenant::find($pendingBill->tenant_id);
        $this->assertNotNull($tenant);
        $this->assertEquals($companyName, $tenant->company_name);
        $this->assertEquals($commercialNumber, $tenant->commercial_number);

        // Check subscription created
        $subscription = $tenant->lastSubscription;
        $this->assertNotNull($subscription);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertEquals($plan->id, $subscription->plan_id);
    }
}
