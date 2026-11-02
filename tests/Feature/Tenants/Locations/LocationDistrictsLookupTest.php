<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Locations;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Tests\TestCase;

class LocationDistrictsLookupTest extends TestCase
{
    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::findOrFail('test');
        tenancy()->initialize($this->tenant);
        $this->user = User::where('email', 'demo@lot.com')->firstOrFail();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_districts_endpoint_returns_districts_for_selected_city(): void
    {
        $response = $this->actingAs($this->user, 'tenant')
            ->getJson(tenant_route_name('locations.districts', ['city' => 'الرياض']));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $districts = collect($response->json('districts'));
        $this->assertTrue($districts->contains('id', 'الملقا'));
        $this->assertTrue($districts->contains('id', 'النرجس'));
    }

    public function test_districts_endpoint_returns_empty_when_no_city_provided(): void
    {
        $response = $this->actingAs($this->user, 'tenant')
            ->getJson(tenant_route_name('locations.districts'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'districts' => [],
            ]);
    }

    public function test_central_gateway_fetches_districts_by_city_name(): void
    {
        $gateway = app(CentralCityOptionsGateway::class);
        $districts = $gateway->districtsByCityName('جدة');

        $this->assertArrayHasKey('الشاطئ', $districts);
        $this->assertArrayHasKey('الروضة', $districts);
    }
}
