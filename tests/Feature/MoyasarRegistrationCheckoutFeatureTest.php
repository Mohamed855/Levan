<?php

namespace Tests\Feature;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MoyasarRegistrationCheckoutFeatureTest extends TestCase
{
    protected Plan $publicPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicPlan = Plan::query()->where('code', '!=', 'trial_7_days')->first()
            ?? Plan::query()->where('code', 'trial_7_days')->firstOrFail()->replicate();

        $this->publicPlan->exists = false;
        $this->publicPlan->id = null;
        $this->publicPlan->code = 'public_test_'.uniqid();
        $this->publicPlan->name = 'Public Test Plan';
        $this->publicPlan->is_active = true;
        $this->publicPlan->price = 100;
        $this->publicPlan->yearly_renewal_price = 100;
        $this->publicPlan->save();
    }

    public function test_checkout_form_loads_successfully(): void
    {
        $plan = $this->publicPlan;
        $this->assertNotNull($plan, 'An active plan must exist');

        $response = $this->get(route('site.plans.checkout', $plan->id));
        $response->assertStatus(200);
        $response->assertSee($plan->name);
    }

    public function test_registration_checkout_renders_moyasar_form_without_card_payload(): void
    {
        config()->set('services.moyasar.mode', 'test');
        config()->set('services.moyasar.publishable', 'pk_test_feature_checkout');

        $plan = $this->publicPlan;
        $this->assertNotNull($plan, 'An active plan must exist');

        $unique = rand(10000, 99999);
        $companyName = "شركة تجريبية {$unique}";
        $commercialNumber = '1010'.rand(100000, 999999);
        $email = "demo_owner_{$unique}@example.com";
        $phone = '05'.rand(10000000, 99999999);

        $city = City::first();

        $payload = [
            'billing_company_name' => $companyName,
            'billing_commercial_number' => $commercialNumber,
            'billing_tax_number' => '300000000000003',
            'billing_city_id' => $city?->id,
            'billing_address' => 'الرياض، حي الصحافة',
            'billing_name' => 'أحمد محمد',
            'billing_email' => $email,
            'billing_phone' => $phone,
            'billing_password' => 'Secret1234!',
            'billing_password_confirmation' => 'Secret1234!',
        ];

        $response = $this->post(route('site.plans.checkout.store', $plan->id), $payload);

        $response->assertRedirect(route('site.plans.checkout', $plan->id));
        $this->get(route('site.plans.checkout', $plan->id))
            ->assertOk()
            ->assertSee('checkout-otp-timer', false)
            ->assertSee('تعديل البيانات');

        $response = $this->post(route('site.plans.checkout.verify.store', $plan->id), ['otp' => '000000']);
        $response->assertRedirect(route('site.plans.checkout', $plan->id));

        $this->get(route('site.plans.checkout', $plan->id))
            ->assertOk()
            ->assertSee('payment_method_card', false)
            ->assertSee('متابعة إلى الدفع');

        $response = $this->post(route('site.plans.checkout.payment', $plan->id), ['payment_method' => 'card']);

        $response->assertOk();
        $response->assertSee('mysr-form', false);
        $response->assertDontSee('name="card_number"', false);
        $response->assertDontSee('name="cvc"', false);

        $pendingBill = Bill::latest('id')->first();
        $this->assertNotNull($pendingBill);
        $this->assertEquals(BillStatus::PENDING, $pendingBill->status);
        $this->assertSame($email, $pendingBill->metadata['billing_email']);
        $this->assertSame($companyName, $pendingBill->metadata['billing_company_name']);
        $this->assertSame($phone, $pendingBill->metadata['billing_phone']);
        $this->assertArrayNotHasKey('billing_password', $pendingBill->metadata);
        $response->assertSessionHas('checkout_verification');

        $backResponse = $this->get(route('site.plans.checkout', $plan->id));
        $backResponse->assertOk();
        $backResponse->assertSee('payment_method_card', false);
        $backResponse->assertDontSee('name="billing_company_name"', false);
    }

    public function test_navigating_back_after_otp_verification_preserves_payment_step_and_does_not_return_to_registration(): void
    {
        $plan = $this->publicPlan;
        $sessionData = [
            'plan_id' => $plan->id,
            'billing_company_name' => 'Verified Company',
            'billing_commercial_number' => '1010998877',
            'billing_tax_number' => '300000000000003',
            'billing_city_id' => City::first()?->id,
            'billing_address' => 'الرياض',
            'billing_name' => 'Verified User',
            'billing_email' => 'verified@example.test',
            'billing_phone' => '0599112233',
            'billing_password_hash' => Hash::make('Secret1234!'),
            'verified_at' => now()->timestamp,
            'expires_at' => now()->addHour()->timestamp,
        ];

        // 1. Directly hitting checkout route with verified session returns payment step
        $response = $this->withSession(['checkout_verification' => $sessionData])
            ->get(route('site.plans.checkout', $plan));

        $response->assertOk();
        $response->assertSee('payment_method_card', false);
        $response->assertDontSee('name="billing_company_name"', false);

        // 2. Explicitly requesting checkout.edit clears verification and returns user to registration form
        $editResponse = $this->withSession(['checkout_verification' => $sessionData])
            ->get(route('site.plans.checkout.edit', $plan));

        $editResponse->assertRedirect(route('site.plans.checkout', $plan));
        $this->get(route('site.plans.checkout', $plan))
            ->assertOk()
            ->assertSee('name="billing_company_name"', false)
            ->assertDontSee('payment_method_card', false);
    }

    public function test_editing_checkout_clears_otp_state_and_keeps_customer_data_safe(): void
    {
        $response = $this->withSession([
            'checkout_coupon_code' => 'EXPIRED-CHECKOUT',
            'checkout_verification' => [
                'plan_id' => $this->publicPlan->id,
                'billing_company_name' => 'Editable Company',
                'billing_email' => 'editable@example.test',
                'billing_password' => 'Secret1234!',
                'expires_at' => now()->addMinute()->timestamp,
                'otp_hash' => hash('sha256', '000000'),
            ],
        ])->get(route('site.plans.checkout.edit', $this->publicPlan));

        $response->assertRedirect(route('site.plans.checkout', $this->publicPlan));
        $this->get(route('site.plans.checkout', $this->publicPlan))
            ->assertOk()
            ->assertDontSee('checkout-otp-timer', false)
            ->assertDontSee('Secret1234!', false);
    }

    public function test_expired_verified_checkout_is_removed_from_the_session(): void
    {
        $response = $this->withSession([
            'checkout_verification' => [
                'plan_id' => $this->publicPlan->id,
                'billing_email' => 'expired@example.test',
                'billing_password_hash' => Hash::make('Secret1234!'),
                'verified_at' => now()->subHour()->timestamp,
                'expires_at' => now()->subMinute()->timestamp,
            ],
        ])->get(route('site.plans.checkout', $this->publicPlan));

        $response->assertOk();
        $response->assertSessionMissing('checkout_verification');
        $response->assertSessionMissing('checkout_coupon_code');
    }

    public function test_failed_payment_can_be_retried_through_a_signed_short_lived_bill_link(): void
    {
        config()->set('services.moyasar.mode', 'test');
        config()->set('services.moyasar.publishable', 'pk_test_feature_checkout');

        $bill = Bill::create([
            'amount' => $this->publicPlan->price_after_discount,
            'original_amount' => $this->publicPlan->price_after_discount,
            'type' => BillType::NEW_SUBSCRIPTION,
            'payment_method' => PaymentMethod::ONLINE,
            'status' => BillStatus::PENDING,
            'due_date' => now()->addHour(),
            'metadata' => [
                'plan_id' => $this->publicPlan->id,
                'checkout_source' => 'site_checkout',
                'billing_email' => 'retry@example.test',
                'billing_password_hash' => Hash::make('Secret1234!'),
            ],
        ]);

        $retryUrl = URL::temporarySignedRoute('site.plans.checkout.retry', now()->addMinutes(30), [
            'billNumber' => $bill->bill_number,
        ]);

        $this->get($retryUrl)
            ->assertOk()
            ->assertSee('mysr-form', false);

        $this->get(route('site.plans.checkout.retry', ['billNumber' => $bill->bill_number]))
            ->assertForbidden();
    }

    public function test_checkout_rejects_a_phone_number_already_used_by_an_admin_user(): void
    {
        $phone = '0598123456';
        $adminUser = AdminUser::create([
            'name' => 'Checkout Phone Test',
            'email' => 'checkout-phone-'.uniqid().'@example.test',
            'password' => 'Secret1234!',
            'phone' => $phone,
        ]);

        try {
            $response = $this->post(route('site.plans.checkout.store', $this->publicPlan), [
                'billing_company_name' => 'Checkout Phone Test Company',
                'billing_commercial_number' => '1010123456',
                'billing_name' => 'Checkout Phone Owner',
                'billing_email' => 'checkout-phone-owner-'.uniqid().'@example.test',
                'billing_phone' => $phone,
                'billing_password' => 'Secret1234!',
                'billing_password_confirmation' => 'Secret1234!',
            ]);

            $response->assertSessionHasErrors('billing_phone');
        } finally {
            $adminUser->forceDelete();
        }
    }

    public function test_checkout_clears_stale_invalid_coupon_from_session_and_completes_payment_without_error(): void
    {
        config()->set('services.moyasar.mode', 'test');
        config()->set('services.moyasar.publishable', 'pk_test_feature_checkout');

        $plan = $this->publicPlan;

        // Simulate stale/invalid coupon code in session
        $response = $this->withSession([
            'checkout_coupon_code' => 'NON_EXISTENT_COUPON',
            'checkout_verification' => [
                'plan_id' => $plan->id,
                'billing_company_name' => 'Stale Coupon Test Co',
                'billing_commercial_number' => '1010887766',
                'billing_name' => 'Owner Name',
                'billing_email' => 'stale_coupon@example.com',
                'billing_phone' => '0599887766',
                'billing_password' => 'Secret1234!',
                'coupon_code' => 'NON_EXISTENT_COUPON',
                'expires_at' => now()->addMinutes(5)->timestamp,
                'verified_at' => now()->timestamp,
            ],
        ])->post(route('site.plans.checkout.payment', $plan->id), ['payment_method' => 'card']);

        // Must NOT redirect with error "كود الخصم غير صحيح"
        $response->assertOk();
        $response->assertSee('mysr-form', false);

        // Stale coupon must be cleared from session
        $this->assertNull(session('checkout_coupon_code'));
    }

    public function test_payment_callback_redirects_to_tenant_login_with_success_toastr_message(): void
    {
        $bill = Bill::create([
            'amount' => $this->publicPlan->price_after_discount,
            'original_amount' => $this->publicPlan->price_after_discount,
            'type' => BillType::NEW_SUBSCRIPTION,
            'payment_method' => PaymentMethod::ONLINE,
            'status' => BillStatus::PENDING,
            'due_date' => now()->addHour(),
            'metadata' => [
                'plan_id' => $this->publicPlan->id,
                'checkout_source' => 'site_checkout',
            ],
        ]);

        $this->mock(\App\Services\shared\Payment\Moyasar\MoyasarCallbackHandler::class, function ($mock) use ($bill) {
            $mock->shouldReceive('verify')->once()->andReturn([
                'bill' => $bill,
                'payment' => ['id' => 'pay_test_123', 'status' => 'paid'],
            ]);
        });

        $subscriptionServiceMock = $this->createMock(\App\Services\Shared\Subscription\SubscriptionService::class);
        $subscriptionServiceMock->expects($this->once())
            ->method('completeOnlineSubscription')
            ->willReturn([
                'tenant_id' => 'test-tenant-uuid',
                'message' => 'تم إنشاء الاشتراك بنجاح.',
            ]);
        $this->app->instance(\App\Services\Shared\Subscription\SubscriptionService::class, $subscriptionServiceMock);
        $this->app->instance(\App\Services\shared\Subscription\SubscriptionService::class, $subscriptionServiceMock);

        $response = $this->get(route('site.plans.checkout.callback', ['bill' => $bill->bill_number, 'id' => 'pay_test_123']));

        $response->assertRedirect(url('/test-tenant-uuid/login'));
        $response->assertSessionHas('success', 'تم تفعيل أشتراكك بنجاح يمكنك الأن تسجيل الدخول');
    }

    public function test_tenant_login_view_includes_toastr_and_renders_flash_success(): void
    {
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());

        $rendered = $this->withSession([
            'success' => 'تم تفعيل أشتراكك بنجاح يمكنك الأن تسجيل الدخول',
        ])->view('tenant.auth.login');

        $rendered->assertSee('toastr');
        $rendered->assertSee('data-flash-success="تم تفعيل أشتراكك بنجاح يمكنك الأن تسجيل الدخول"', false);
    }

    public function test_resending_checkout_otp_is_allowed_up_to_three_times_and_then_blocked(): void
    {
        $payload = [
            'plan_id' => $this->publicPlan->id,
            'billing_company_name' => 'Resend Company '.rand(1000, 9999),
            'billing_commercial_number' => '1010'.rand(100000, 999999),
            'billing_tax_number' => '300000000000003',
            'billing_city_id' => City::firstOrFail()->id,
            'billing_address' => 'Riyadh',
            'billing_name' => 'Resend User',
            'billing_email' => 'resend-checkout-'.rand(10000, 99999).'@example.test',
            'billing_phone' => '05'.rand(10000000, 99999999),
            'billing_password' => 'Secret1234!',
            'billing_password_confirmation' => 'Secret1234!',
        ];

        $this->post(route('site.plans.checkout.store', $this->publicPlan), $payload)->assertRedirect(route('site.plans.checkout', $this->publicPlan));

        // Resend 1
        $this->post(route('site.plans.checkout.resend', $this->publicPlan))->assertRedirect(route('site.plans.checkout', $this->publicPlan))->assertSessionHas('success');
        self::assertSame(1, session('checkout_verification.resend_count'));

        // Resend 2
        $this->post(route('site.plans.checkout.resend', $this->publicPlan))->assertRedirect(route('site.plans.checkout', $this->publicPlan))->assertSessionHas('success');
        self::assertSame(2, session('checkout_verification.resend_count'));

        // Resend 3
        $this->post(route('site.plans.checkout.resend', $this->publicPlan))->assertRedirect(route('site.plans.checkout', $this->publicPlan))->assertSessionHas('success');
        self::assertSame(3, session('checkout_verification.resend_count'));

        // Resend 4 (blocked)
        $this->post(route('site.plans.checkout.resend', $this->publicPlan))->assertRedirect(route('site.plans.checkout', $this->publicPlan))->assertSessionHasErrors('otp');
        self::assertSame(3, session('checkout_verification.resend_count'));
    }
}
