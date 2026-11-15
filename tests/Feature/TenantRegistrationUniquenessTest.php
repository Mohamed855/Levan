<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\Site\SubscriptionCheckoutRequest;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Tenants\Tenant;
use App\Rules\SystemEmailAvailable;
use App\Rules\SystemPhoneAvailable;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class TenantRegistrationUniquenessTest extends TestCase
{
    private Tenant $tenantA;

    private Plan $plan;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::query()->where('code', '!=', 'trial_7_days')->first()
            ?? Plan::query()->firstOrFail();

        $this->city = City::first() ?? City::create(['name' => 'الرياض']);

        $tenantId = 't_uniq_'.bin2hex(random_bytes(4));
        $this->tenantA = Tenant::create([
            'id' => $tenantId,
            'db_database' => 'levan_'.$tenantId,
            'db_username' => 'root',
            'db_password' => '',
            'company_name' => 'شركة أ التجريبية',
            'commercial_number' => '1010998877',
            'tax_number' => '300099887766553',
            'primary_contact_name' => 'المالك الأساسي',
            'primary_contact_email' => 'primary-owner@company-a.test',
            'primary_contact_phone' => '0599112233',
        ]);
    }

    protected function tearDown(): void
    {
        $this->tenantA->forceDelete();

        parent::tearDown();
    }

    public function test_registration_rejects_duplicate_tenant_primary_email(): void
    {
        $validator = Validator::make([
            'billing_company_name' => 'شركة جديدة تماماً',
            'billing_commercial_number' => '1020304050',
            'billing_tax_number' => '311122233344453',
            'billing_city_id' => $this->city->id,
            'billing_address' => 'شارع الملك فهد',
            'billing_name' => 'مستخدم جديد',
            'billing_email' => 'primary-owner@company-a.test', // Duplicate primary email
            'billing_phone' => '0588776655',
        ], (new SubscriptionCheckoutRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('billing_email', $validator->errors()->toArray());
    }

    public function test_registration_rejects_duplicate_tenant_primary_phone(): void
    {
        // 1. Same 05 format
        $validator1 = Validator::make([
            'billing_company_name' => 'شركة جديدة تماماً',
            'billing_commercial_number' => '1020304050',
            'billing_tax_number' => '311122233344453',
            'billing_city_id' => $this->city->id,
            'billing_address' => 'شارع الملك فهد',
            'billing_name' => 'مستخدم جديد',
            'billing_email' => 'new-owner@new-company.test',
            'billing_phone' => '0599112233', // Duplicate primary phone (05...)
        ], (new SubscriptionCheckoutRequest)->rules());

        $this->assertTrue($validator1->fails());
        $this->assertArrayHasKey('billing_phone', $validator1->errors()->toArray());

        // 2. 966 candidate format
        $validator2 = Validator::make([
            'billing_company_name' => 'شركة جديدة ثانية',
            'billing_commercial_number' => '1020304051',
            'billing_tax_number' => '311122233344454',
            'billing_city_id' => $this->city->id,
            'billing_address' => 'شارع الملك فهد',
            'billing_name' => 'مستخدم جديد',
            'billing_email' => 'another-owner@new-company.test',
            'billing_phone' => '966599112233', // Duplicate primary phone (9665...)
        ], (new SubscriptionCheckoutRequest)->rules());

        $this->assertTrue($validator2->fails());
        $this->assertArrayHasKey('billing_phone', $validator2->errors()->toArray());
    }

    public function test_registration_rejects_duplicate_tenant_commercial_and_tax_numbers(): void
    {
        // Duplicate commercial number
        $validatorCr = Validator::make([
            'billing_company_name' => 'شركة جديدة',
            'billing_commercial_number' => '1010998877', // Duplicate CR
            'billing_tax_number' => '311122233344453',
            'billing_city_id' => $this->city->id,
            'billing_address' => 'شارع الملك فهد',
            'billing_name' => 'مستخدم جديد',
            'billing_email' => 'valid-email@new-company.test',
            'billing_phone' => '0588776655',
        ], (new SubscriptionCheckoutRequest)->rules());

        $this->assertTrue($validatorCr->fails());
        $this->assertArrayHasKey('billing_commercial_number', $validatorCr->errors()->toArray());

        // Duplicate tax number
        $validatorTax = Validator::make([
            'billing_company_name' => 'شركة جديدة أخرى',
            'billing_commercial_number' => '1020304050',
            'billing_tax_number' => '300099887766553', // Duplicate Tax
            'billing_city_id' => $this->city->id,
            'billing_address' => 'شارع الملك فهد',
            'billing_name' => 'مستخدم جديد',
            'billing_email' => 'valid-email-2@new-company.test',
            'billing_phone' => '0588776655',
        ], (new SubscriptionCheckoutRequest)->rules());

        $this->assertTrue($validatorTax->fails());
        $this->assertArrayHasKey('billing_tax_number', $validatorTax->errors()->toArray());
    }

    public function test_registration_allows_email_and_phone_that_do_not_belong_to_a_primary_tenant(): void
    {
        // An email and phone that do not exist as primary_contact_email / primary_contact_phone of any tenant
        // must pass SystemEmailAvailable and SystemPhoneAvailable cleanly.
        $emailAvailable = new SystemEmailAvailable;
        $emailFailed = false;
        $emailAvailable->validate('billing_email', 'regular-employee@somewhere.test', function () use (&$emailFailed) {
            $emailFailed = true;
        });
        $this->assertFalse($emailFailed, 'SystemEmailAvailable should not fail for regular emails not in primary tenant contacts');

        $phoneAvailable = new SystemPhoneAvailable;
        $phoneFailed = false;
        $phoneAvailable->validate('billing_phone', '0577665544', function () use (&$phoneFailed) {
            $phoneFailed = true;
        });
        $this->assertFalse($phoneFailed, 'SystemPhoneAvailable should not fail for regular phones not in primary tenant contacts');

        // Full checkout request rules validation passes when tenant primary fields are unique
        $validator = Validator::make([
            'billing_company_name' => 'شركة رائدة جديدة',
            'billing_commercial_number' => '1099001122',
            'billing_tax_number' => '399988877766653',
            'billing_city_id' => $this->city->id,
            'billing_address' => 'طريق الملك عبد العزيز',
            'billing_name' => 'موظف في مستأجر آخر',
            'billing_email' => 'regular-employee@somewhere.test',
            'billing_phone' => '0577665544',
        ], (new SubscriptionCheckoutRequest)->rules());

        $this->assertTrue($validator->passes(), 'New tenant registration must pass when tenant data is unique');
    }
}
