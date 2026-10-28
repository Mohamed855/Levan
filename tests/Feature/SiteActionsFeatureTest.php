<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Models\Admin\Bank\Bank;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class SiteActionsFeatureTest extends TestCase
{
    public function test_landing_page_and_privacy_policy_load_successfully(): void
    {
        $this->get(route('site.index'))->assertOk();
        $this->get(route('site.privacy.policy'))->assertOk();
    }

    public function test_contact_request_validates_required_fields(): void
    {
        $this->post(route('site.contact.store'), [])
            ->assertSessionHasErrors(['customer_name', 'customer_email', 'message']);
    }

    public function test_contact_request_submits_successfully_via_web_post(): void
    {
        Mail::fake();

        $payload = [
            'customer_name' => 'محمد عبدالله',
            'customer_email' => 'mohammed@example.com',
            'message' => 'هذه رسالة استفسار تجريبية للتأكد من عمل النموذج بشكل سليم.',
        ];

        $response = $this->post(route('site.contact.store'), $payload);

        $response->assertRedirect(route('site.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('contact_requests', [
            'customer_name' => 'محمد عبدالله',
            'customer_email' => 'mohammed@example.com',
        ]);
    }

    public function test_contact_request_submits_successfully_via_ajax_json(): void
    {
        Mail::fake();

        $payload = [
            'customer_name' => 'سارة أحمد',
            'customer_email' => 'sara@example.com',
            'message' => 'استفسار عبر الأجاكس بخصوص باقات الاشتراك السنوية.',
        ];

        $response = $this->postJson(route('site.contact.store'), $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('contact_requests', [
            'customer_name' => 'سارة أحمد',
            'customer_email' => 'sara@example.com',
        ]);
    }

    public function test_checkout_bank_transfer_full_flow_with_receipt(): void
    {
        Mail::fake();
        Storage::fake('public');

        $plan = Plan::where('is_active', true)->first();
        if (! $plan) {
            $plan = Plan::create([
                'name' => 'باقة الأعمال',
                'slug' => 'business-plan-'.uniqid(),
                'price' => 500,
                'price_after_discount' => 500,
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 1,
            ]);
        }

        $bank = Bank::where('is_active', true)->first();
        if (! $bank) {
            $bank = Bank::create([
                'bank_name' => 'مصرف الراجحي',
                'icon' => 'rajhi.png',
                'account_name' => 'شركة لوت للتقنية',
                'account_number' => '12345678901234',
                'iban' => 'SA0380000000608010167519',
                'is_active' => true,
            ]);
        }

        $city = City::first() ?? City::create(['name' => 'الرياض']);

        $unique = rand(10000, 99999);
        $detailsPayload = [
            'billing_company_name' => "شركة التقنية {$unique}",
            'billing_commercial_number' => '1010'.rand(100000, 999999),
            'billing_tax_number' => '300000000000003',
            'billing_city_id' => $city->id,
            'billing_address' => 'الرياض، حي النخيل',
            'billing_name' => 'سلطان القحطاني',
            'billing_email' => "sultan_{$unique}@example.com",
            'billing_phone' => '05'.rand(10000000, 99999999),
            'billing_password' => 'SecretPass123!',
            'billing_password_confirmation' => 'SecretPass123!',
        ];

        // Step 1: Submit details
        $this->post(route('site.plans.checkout.store', $plan), $detailsPayload)
            ->assertRedirect(route('site.plans.checkout', $plan));

        $sessionData = session('checkout_verification');
        $this->assertNotNull($sessionData);
        $this->assertNotEmpty($sessionData['otp_hash']);

        // Step 2: Verify OTP (in test mode otp is 000000)
        $this->post(route('site.plans.checkout.verify.store', $plan), ['otp' => '000000'])
            ->assertRedirect(route('site.plans.checkout', $plan));

        $verifiedSession = session('checkout_verification');
        $this->assertNotNull($verifiedSession['verified_at'] ?? null);

        // Step 3: Complete payment via Bank Transfer
        $fakeReceipt = UploadedFile::fake()->create('receipt.pdf', 200, 'application/pdf');

        $paymentPayload = [
            'payment_method' => 'bank_transfer',
            'bank_id' => $bank->id,
            'transfer_receipt' => $fakeReceipt,
        ];

        $paymentResponse = $this->post(route('site.plans.checkout.payment', $plan), $paymentPayload);

        $paymentResponse->assertRedirect(route('site.index'))
            ->assertSessionHas('success');

        // Verify Bill was created with BANK_TRANSFER and PENDING status
        $bill = Bill::where('metadata->billing_email', $detailsPayload['billing_email'])->first();
        $this->assertNotNull($bill);
        $this->assertSame(BillStatus::PENDING_VERIFICATION, $bill->status);
        $this->assertSame(PaymentMethod::BANK_TRANSFER, $bill->payment_method);
        $this->assertSame($bank->id, (int) $bill->bank_id);
        $this->assertNotNull($bill->invoice_url);
        Storage::disk('public')->assertExists($bill->invoice_url);
    }

    public function test_checkout_otp_resend_ajax_contract_and_limits(): void
    {
        Mail::fake();

        $plan = Plan::where('is_active', true)->first();
        if (! $plan) {
            $plan = Plan::create([
                'name' => 'باقة النمو',
                'slug' => 'growth-plan-'.uniqid(),
                'price' => 300,
                'price_after_discount' => 300,
                'is_active' => true,
                'is_public' => true,
                'sort_order' => 2,
            ]);
        }

        $sessionData = [
            'plan_id' => $plan->getKey(),
            'billing_email' => 'resend-test@example.com',
            'otp_hash' => hash('sha256', '000000'),
            'expires_at' => now()->addMinutes(2)->timestamp,
            'session_expires_at' => now()->addHour()->timestamp,
            'resend_count' => 0,
        ];

        // 1st AJAX resend
        $res1 = $this->withSession(['checkout_verification' => $sessionData])
            ->postJson(route('site.plans.checkout.resend', $plan));
        $res1->assertOk()
            ->assertJson([
                'success' => true,
                'resend_count' => 1,
                'remaining_resends' => 2,
            ]);
        $this->assertNotNull($res1->json('expires_at'));

        // 2nd AJAX resend
        $sessionData['resend_count'] = 1;
        $res2 = $this->withSession(['checkout_verification' => $sessionData])
            ->postJson(route('site.plans.checkout.resend', $plan));
        $res2->assertOk()
            ->assertJson([
                'success' => true,
                'resend_count' => 2,
                'remaining_resends' => 1,
            ]);

        // 3rd AJAX resend (last allowed)
        $sessionData['resend_count'] = 2;
        $res3 = $this->withSession(['checkout_verification' => $sessionData])
            ->postJson(route('site.plans.checkout.resend', $plan));
        $res3->assertOk()
            ->assertJson([
                'success' => true,
                'resend_count' => 3,
                'remaining_resends' => 0,
            ]);

        // 4th AJAX resend should fail with 422
        $sessionData['resend_count'] = 3;
        $res4 = $this->withSession(['checkout_verification' => $sessionData])
            ->postJson(route('site.plans.checkout.resend', $plan));
        $res4->assertStatus(422)
            ->assertJson([
                'success' => false,
                'resend_count' => 3,
            ]);

        // Verify the checkout view renders the edit container visible without d-none when resend_count >= 3
        $viewRes = $this->withSession(['checkout_verification' => $sessionData])
            ->get(route('site.plans.checkout', $plan));
        $viewRes->assertOk();
        $viewContent = $viewRes->getContent();
        $this->assertStringContainsString('(0 محاولة متبقية)', $viewContent);
    }

    public function test_trial_otp_resend_ajax_contract_and_limits(): void
    {
        Mail::fake();

        $sessionData = [
            'billing_email' => 'trial-resend@example.com',
            'otp_hash' => hash('sha256', '000000'),
            'expires_at' => now()->addMinutes(2)->timestamp,
            'session_expires_at' => now()->addHour()->timestamp,
            'resend_count' => 0,
        ];

        // 1st AJAX resend
        $res1 = $this->withSession(['trial_registration' => $sessionData])
            ->postJson(route('site.trial.resend'));
        $res1->assertOk()
            ->assertJson([
                'success' => true,
                'resend_count' => 1,
                'remaining_resends' => 2,
            ]);

        // 3rd AJAX resend (last attempt)
        $sessionData['resend_count'] = 2;
        $res3 = $this->withSession(['trial_registration' => $sessionData])
            ->postJson(route('site.trial.resend'));
        $res3->assertOk()
            ->assertJson([
                'success' => true,
                'resend_count' => 3,
                'remaining_resends' => 0,
            ]);

        // 4th should be blocked
        $sessionData['resend_count'] = 3;
        $res4 = $this->withSession(['trial_registration' => $sessionData])
            ->postJson(route('site.trial.resend'));
        $res4->assertStatus(422)
            ->assertJson([
                'success' => false,
                'resend_count' => 3,
            ]);

        // Verify trial view renders edit button without d-none when resend_count >= 3
        $viewRes = $this->withSession(['trial_registration' => $sessionData])
            ->get(route('site.trial'));
        $viewRes->assertOk();
        $viewContent = $viewRes->getContent();
        $this->assertStringContainsString('(0 محاولة متبقية)', $viewContent);
    }

    public function test_404_and_error_pages_do_not_render_preloader(): void
    {
        // 1. Central 404
        $res = $this->get('/non-existent-central-page-404');
        $res->assertStatus(404);
        $res->assertDontSee('page-loading', false);
        $res->assertDontSee('grid-loader', false);

        // 2. Admin 404
        $adminRes = $this->get('/admin/non-existent-admin-page-404');
        $adminRes->assertStatus(404);
        $adminRes->assertDontSee('page-loading', false);
        $adminRes->assertDontSee('grid-loader', false);

        // 3. Directly rendered error views
        foreach (['401', '403', '404', '419', '429', '500', '503', 'maintenance_mode'] as $viewCode) {
            $rendered = view("errors.{$viewCode}")->render();
            $this->assertStringNotContainsString('page-loading', $rendered, "Error view {$viewCode} must not contain page-loading preloader.");
            $this->assertStringNotContainsString('grid-loader', $rendered, "Error view {$viewCode} must not contain grid-loader.");
        }
    }
}
