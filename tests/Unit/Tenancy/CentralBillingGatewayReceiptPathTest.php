<?php

declare(strict_types=1);

namespace Tests\Unit\Tenancy;

use App\Models\Admin\Bill;
use App\Models\Admin\Tenants\Tenant;
use App\Services\Tenants\Shared\CentralBillingGateway;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Tests\TestCase;

final class CentralBillingGatewayReceiptPathTest extends TestCase
{
    protected function tearDown(): void
    {
        app()->forgetInstance(TenantContract::class);

        parent::tearDown();
    }

    public function test_it_only_returns_a_receipt_path_for_the_current_tenant_prefix(): void
    {
        $this->bindTenant('tenant-a');
        $bill = new Bill(['tenant_id' => 'tenant-a', 'metadata' => ['receipt_path' => 'receipts/tenant-a/receipt.pdf']]);

        self::assertSame('receipts/tenant-a/receipt.pdf', app(CentralBillingGateway::class)->receiptPathForCurrentTenant($bill));
    }

    public function test_it_hides_a_receipt_outside_the_current_tenant_prefix(): void
    {
        $this->bindTenant('tenant-a');
        $bill = new Bill(['tenant_id' => 'tenant-a', 'metadata' => ['receipt_path' => 'receipts/tenant-b/receipt.pdf']]);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        app(CentralBillingGateway::class)->receiptPathForCurrentTenant($bill);
    }

    private function bindTenant(string $id): void
    {
        $tenant = new Tenant();
        $tenant->setAttribute('id', $id);

        app()->instance(TenantContract::class, $tenant);
    }
}
