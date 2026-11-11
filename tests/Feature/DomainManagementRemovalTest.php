<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Admin\Billing\BillType;
use App\Services\Shared\Cpanel\CpanelService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

final class DomainManagementRemovalTest extends TestCase
{
    public function test_domain_management_endpoints_are_no_longer_registered(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            $this->assertDoesNotMatchRegularExpression('/(^|\.)domains\./', (string) $route->getName());
            $this->assertDoesNotMatchRegularExpression('#(^|/)domains(/|$)#', $route->uri());
        }
    }

    public function test_the_former_registration_endpoint_is_not_found(): void
    {
        $this->expectException(NotFoundHttpException::class);

        app('router')->getRoutes()->match(Request::create('http://localhost/test/domains/payment', 'POST'));
    }

    public function test_tenant_database_provisioning_remains_available_without_domain_hosting(): void
    {
        $service = app(CpanelService::class);

        $this->assertTrue(is_callable([$service, 'createDatabase']));
        $this->assertFalse(method_exists($service, 'createLoginAndToken'));
        $this->assertFalse(method_exists($service, 'writeFileContentForUser'));
    }

    public function test_historical_domain_invoices_remain_readable_without_offering_new_domain_bill_types(): void
    {
        $retiredTypes = ['new_domain', 'transfer_domain', 'renewal_domain'];
        foreach ($retiredTypes as $type) {
            $this->assertNotEmpty(BillType::from($type)->label());
        }
        $this->assertSame([], array_values(array_intersect($retiredTypes, array_column(BillType::options(), 'value'))));
    }
}
