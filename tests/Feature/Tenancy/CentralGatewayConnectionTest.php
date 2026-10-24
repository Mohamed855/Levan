<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Admin\Tenants\Tenant;
use App\Services\Tenants\Shared\CentralBillingGateway;
use App\Services\Tenants\Shared\CentralTicketGateway;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Stancl\Tenancy\Exceptions\TenancyNotInitializedException;
use Tests\TestCase;

final class CentralGatewayConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'tenant',
            'tenancy.database.central_connection' => 'central',
        ]);
    }

    protected function tearDown(): void
    {
        app()->forgetInstance(TenantContract::class);

        parent::tearDown();
    }

    public function test_billing_queries_remain_on_the_central_connection_after_the_gateway_returns(): void
    {
        $this->bindTenant('tenant-a');

        $query = (new CentralBillingGateway())->billsQueryForCurrentTenant();

        self::assertSame('central', $query->getModel()->getConnectionName());
        self::assertContains('tenant-a', $query->getQuery()->getBindings());
    }

    public function test_ticket_queries_remain_on_the_central_connection_after_the_gateway_returns(): void
    {
        $this->bindTenant('tenant-a');

        $query = (new CentralTicketGateway())->ticketsQueryForCurrentTenant();

        self::assertSame('central', $query->getModel()->getConnectionName());
        self::assertContains('tenant-a', $query->getQuery()->getBindings());
    }

    public function test_gateways_refuse_to_query_when_tenant_context_is_missing(): void
    {
        $this->expectException(TenancyNotInitializedException::class);

        (new CentralBillingGateway())->billsQueryForCurrentTenant();
    }

    private function bindTenant(string $id): void
    {
        $tenant = new Tenant();
        $tenant->setAttribute('id', $id);

        app()->instance(TenantContract::class, $tenant);
    }
}
