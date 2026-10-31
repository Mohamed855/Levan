<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\Admin\Subscriptions\StoreSubscriptionRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class SubscriptionCreateFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'mysql', 'database.connections.mysql' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('mysql');

        Schema::create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->text('data')->nullable();
            $table->softDeletes();
        });
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('status');
        });
        DB::table('tenants')->insert(['id' => 'eligible-company']);
        DB::table('plans')->insert(['id' => 1]);
    }

    public function test_create_form_renders_payment_options_in_each_supported_locale(): void
    {
        foreach (['en', 'ar'] as $locale) {
            app()->setLocale($locale);
            $html = view('admin.subscriptions.partials._form', [
                'companyOptions' => [['value' => 'eligible-company', 'label' => 'Company']],
                'planOptions' => [['value' => 1, 'label' => 'Plan']],
                'errors' => new ViewErrorBag,
            ])->render();

            $this->assertStringContainsString('value="bank_transfer"', $html);
            $this->assertStringContainsString('value="online"', $html);
            $this->assertStringContainsString('name="payment_type"', $html);
            $this->assertStringContainsString('value="paid"', $html);
            $this->assertStringContainsString('name="receipt_upload"', $html);
            $this->assertStringContainsString('data-draft-resource-key="admin.subscription-create"', $html);
        }
    }

    public function test_form_payload_keeps_subscription_period_for_service_and_rejects_invalid_period(): void
    {
        $request = StoreSubscriptionRequest::create('/', 'POST', $this->payload());
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
        $this->assertSame(12, $validator->validated()['period_months']);

        $request = StoreSubscriptionRequest::create('/', 'POST', $this->payload(['period_months' => 0]));
        $validator = Validator::make($request->all(), $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('period_months', $validator->errors()->toArray());
    }

    public function test_paid_bank_transfer_requires_valid_receipt_and_dates(): void
    {
        $request = StoreSubscriptionRequest::create('/', 'POST', $this->payload([
            'payment_type' => 'paid', 'payment_method' => 'bank_transfer', 'end_date' => '2025-01-01',
        ]));
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('receipt_upload', $validator->errors()->toArray());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'company_id' => 'eligible-company', 'plan_id' => 1,
            'start_date' => '2026-10-31', 'end_date' => '2027-10-31',
            'period_months' => 12, 'payment_type' => 'unpaid', 'payment_method' => 'cash',
        ], $overrides);
    }
}
