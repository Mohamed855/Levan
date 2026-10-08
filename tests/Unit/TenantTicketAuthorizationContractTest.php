<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Tenants\Tickets\TicketController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TenantTicketAuthorizationContractTest extends TestCase
{
    public function test_ticket_controller_declares_its_guard_and_capabilities(): void
    {
        $source = file_get_contents((new ReflectionClass(TicketController::class))->getFileName());

        $this->assertStringContainsString("middleware('auth:tenant')", $source);
        $this->assertStringContainsString("middleware('can:tickets.view')", $source);
        $this->assertStringContainsString("middleware('can:tickets.create')", $source);
    }
}
