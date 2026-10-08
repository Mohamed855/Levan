<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\shared\Integration\EtmamTicketService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TenantTicketIsolationContractTest extends TestCase
{
    public function test_local_ticket_operations_use_the_tenant_scoped_query(): void
    {
        $source = file_get_contents((new ReflectionClass(EtmamTicketService::class))->getFileName());

        $this->assertStringContainsString('private function tenantTicketQuery()', $source);
        $this->assertSame(6, substr_count($source, '$this->tenantTicketQuery()'));
        $this->assertStringNotContainsString("Ticket::where('ticket_number'", $source);
    }
}
