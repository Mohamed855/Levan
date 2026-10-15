<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\Tenants\Properties\StorePropertyRequest;
use App\Http\Requests\Tenants\Properties\UpdatePropertyRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TenantPropertyStatusContractTest extends TestCase
{
    public function test_property_unit_status_rejects_arabic_display_labels(): void
    {
        foreach ([new StorePropertyRequest, new UpdatePropertyRequest] as $request) {
            $validator = Validator::make([
                'units' => [['status' => 'متاح']],
            ], $this->unitStatusRules($request));

            $this->assertTrue(
                $validator->fails(),
                $request::class.' must reject Arabic labels and accept only persisted enum values.'
            );
            $this->assertArrayHasKey('units.0.status', $validator->errors()->toArray());
        }
    }

    public function test_property_unit_status_accepts_english_persisted_values(): void
    {
        foreach (['available', 'rented', 'sold'] as $status) {
            foreach ([new StorePropertyRequest, new UpdatePropertyRequest] as $request) {
                $validator = Validator::make([
                    'units' => [['status' => $status]],
                ], $this->unitStatusRules($request));

                $this->assertFalse($validator->fails(), $request::class.' must accept '.$status.'.');
            }
        }
    }

    public function test_property_units_and_identifiers_accept_only_the_new_persisted_contract(): void
    {
        foreach ([new StorePropertyRequest, new UpdatePropertyRequest] as $request) {
            $rules = $request->rules();
            $validator = Validator::make([
                'plan_number' => '00123',
                'parcel_number' => '00456',
                'deed_number' => '1234567890',
                'agent_national_id' => '1099887766',
                'agency_number' => '5544',
                'boundaries' => [['direction' => 'northeast', 'border_type' => 'street']],
                'units' => [['unit_type' => 'villa', 'current_use' => 'residential']],
            ], [
                'plan_number' => $rules['plan_number'],
                'parcel_number' => $rules['parcel_number'],
                'deed_number' => $rules['deed_number'],
                'agent_national_id' => $rules['agent_national_id'],
                'agency_number' => $rules['agency_number'],
                'boundaries' => $rules['boundaries'],
                'boundaries.*.direction' => $rules['boundaries.*.direction'],
                'boundaries.*.border_type' => $rules['boundaries.*.border_type'],
                'units' => $rules['units'],
                'units.*.unit_type' => $rules['units.*.unit_type'],
                'units.*.current_use' => $rules['units.*.current_use'],
            ]);

            $this->assertFalse($validator->fails(), $request::class.' must accept English enum keys and numeric identifiers.');
        }
    }

    private function unitStatusRules(StorePropertyRequest|UpdatePropertyRequest $request): array
    {
        $rules = $request->rules();

        return [
            'units' => $rules['units'],
            'units.*.status' => $rules['units.*.status'],
        ];
    }
}
