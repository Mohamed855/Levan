<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Tenants\Tickets\TicketStatus;
use PHPUnit\Framework\TestCase;

final class TicketStatusColorTest extends TestCase
{
    public function test_closed_ticket_status_uses_danger_color_and_badge(): void
    {
        $this->assertSame('danger', TicketStatus::CLOSED->color());
        $this->assertSame('<span class="badge bg-danger">مغلقة</span>', TicketStatus::CLOSED->badge());
        $this->assertSame('<span class="badge bg-danger">مغلقة</span>', TicketStatus::toBadge('closed'));
    }
}
