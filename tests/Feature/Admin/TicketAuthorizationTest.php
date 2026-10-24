<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Admin\AdminUser;
use App\Models\Admin\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TicketAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_update_permission_does_not_grant_reply_access(): void
    {
        $ticket = Ticket::query()->create([
            'ticket_number' => 'TKT-AUTH-001',
            'subject' => 'Authorization boundary',
            'description' => 'The policy must protect the reply operation.',
            'status' => 'open',
        ]);

        $admin = $this->adminWith(['tickets.update']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.tickets.reply', $ticket), ['message' => 'Unauthorized reply'])
            ->assertForbidden();

        $this->assertDatabaseMissing('ticket_replies', ['ticket_id' => $ticket->id]);
    }

    public function test_ticket_update_requires_the_update_permission(): void
    {
        $ticket = Ticket::query()->create([
            'ticket_number' => 'TKT-AUTH-002',
            'subject' => 'Update authorization',
            'description' => 'The status action must use its own permission.',
            'status' => 'open',
        ]);

        $admin = $this->adminWith(['tickets.reply']);

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.tickets.status', $ticket), ['status' => 'in_progress'])
            ->assertForbidden();

        $this->assertSame('open', $ticket->fresh()->status->value);
    }

    public function test_ticket_update_permission_can_change_the_service_availability(): void
    {
        $ticket = Ticket::query()->create([
            'ticket_number' => 'TKT-AUTH-003',
            'subject' => 'Service availability',
            'description' => 'The service switch must be an authorized ticket update.',
            'status' => 'open',
            'service_enabled' => true,
        ]);

        $admin = $this->adminWith(['tickets.update']);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.tickets.service', $ticket), ['service_enabled' => false])
            ->assertRedirect();

        $this->assertFalse((bool) $ticket->fresh()->service_enabled);
    }

    private function adminWith(array $permissions): AdminUser
    {
        $admin = AdminUser::factory()->create();
        $role = Role::query()->create([
            'guard' => 'admin',
            'name' => 'ticket-role-'.implode('-', $permissions),
            'display_name' => 'صلاحية تذاكر للاختبار',
        ]);

        foreach ($permissions as $name) {
            $permission = Permission::query()->firstOrCreate(
                ['guard' => 'admin', 'name' => $name],
                ['display_name' => $name],
            );
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $admin->assignRole($role);

        return $admin;
    }
}
