<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Admin\Tickets\UpdateTicketStatusAction;
use App\Enums\Admin\Tickets\TicketStatus;
use App\Models\Admin\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class TicketStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_applies_a_valid_transition_while_locked(): void
    {
        $ticket = Ticket::query()->create([
            'ticket_number' => 'TKT-100',
            'subject' => 'مشكلة',
            'description' => 'تفاصيل المشكلة',
            'status' => TicketStatus::OPEN,
        ]);

        app(UpdateTicketStatusAction::class)->execute($ticket, TicketStatus::IN_PROGRESS);

        $this->assertSame(TicketStatus::IN_PROGRESS, $ticket->fresh()->status);
    }

    public function test_action_rejects_a_stale_or_invalid_transition_after_reloading_the_ticket(): void
    {
        $ticket = Ticket::query()->create([
            'ticket_number' => 'TKT-101',
            'subject' => 'مشكلة',
            'description' => 'تفاصيل المشكلة',
            'status' => TicketStatus::CLOSED,
        ]);

        $this->expectException(ValidationException::class);

        app(UpdateTicketStatusAction::class)->execute($ticket, TicketStatus::RESOLVED);
    }
}
