<?php

namespace Tests\Feature;

use App\Http\Requests\Web\StoreCheckoutRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreCheckoutRequestTest extends TestCase
{
    public function test_commercial_registration_requires_ten_digits_without_a_leading_zero(): void
    {
        $rules = (new StoreCheckoutRequest)->rules();

        $this->assertTrue(Validator::make(
            ['billing_commercial_number' => '1010123456'],
            ['billing_commercial_number' => $rules['billing_commercial_number']],
        )->passes());

        $this->assertFalse(Validator::make(
            ['billing_commercial_number' => '0010123456'],
            ['billing_commercial_number' => $rules['billing_commercial_number']],
        )->passes());
    }

    public function test_tax_number_must_start_and_end_with_three(): void
    {
        $rules = (new StoreCheckoutRequest)->rules();

        $this->assertTrue(Validator::make(
            ['billing_tax_number' => '300000000000003'],
            ['billing_tax_number' => $rules['billing_tax_number']],
        )->passes());

        $this->assertFalse(Validator::make(
            ['billing_tax_number' => '300000000000001'],
            ['billing_tax_number' => $rules['billing_tax_number']],
        )->passes());
    }
}
