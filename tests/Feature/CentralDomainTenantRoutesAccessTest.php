<?php

namespace Tests\Feature;

use Tests\TestCase;

class CentralDomainTenantRoutesAccessTest extends TestCase
{
    /**
     * Test tenant routes on central domain without prefix return 404.
     */
    public function test_tenant_routes_without_prefix_on_central_domain_return_404(): void
    {
        $centralHost = 'http://localhost';

        // Direct tenant auth routes without prefix
        $this->get("{$centralHost}/login")->assertStatus(404);
        $this->get("{$centralHost}/register")->assertStatus(404);
        $this->get("{$centralHost}/forgot-password")->assertStatus(404);

        // Direct tenant dashboard/management routes without prefix
        $this->get("{$centralHost}/dashboard")->assertStatus(404);
        $this->get("{$centralHost}/teams")->assertStatus(404);
        $this->get("{$centralHost}/customers")->assertStatus(404);
    }

    /**
     * Test tenant route with valid prefix on central domain works.
     */
    public function test_tenant_route_with_valid_prefix_on_central_domain_works(): void
    {
        $response = $this->get('http://localhost/test/login');
        $response->assertStatus(200);
    }

    /**
     * Test tenant route with non-existent prefix on central domain returns 404.
     */
    public function test_tenant_route_with_invalid_prefix_on_central_domain_returns_404(): void
    {
        $response = $this->get('http://localhost/non-existent-tenant-id/login');
        $response->assertStatus(404);
    }
}
