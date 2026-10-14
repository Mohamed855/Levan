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

    private function unitStatusRules(StorePropertyRequest|UpdatePropertyRequest $request): array
    {
        $rules = $request->rules();

        return [
            'units' => $rules['units'],
            'units.*.status' => $rules['units.*.status'],
        ];
    }
}
