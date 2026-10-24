<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenant\User;
use App\Services\Tenants\Shared\CentralTicketGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Tests\TestCase;

final class TicketAttachmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app()->forgetInstance(TenantContract::class);

        parent::tearDown();
    }

    public function test_ticket_attachments_are_stored_privately_and_scoped_to_the_current_tenant(): void
    {
        Storage::fake('local');
        $this->bindTenant('tenant-a');
        $actor = new User(['name' => 'مستخدم المستأجر']);
        $actor->setAttribute('id', 77);

        $ticket = app(CentralTicketGateway::class)->createTicket([
            'subject' => 'طلب اختبار مرفق',
            'description' => 'هذا وصف كامل لاختبار دورة المرفقات الخاصة.',
            'department' => 'technical',
        ], $actor, [UploadedFile::fake()->create('evidence.pdf', 64, 'application/pdf')]);

        self::assertCount(1, $ticket->attachments);
        $attachment = $ticket->attachments->sole();
        self::assertSame('tenant-a', $ticket->tenant_id);
        self::assertSame(77, $attachment->tenant_user_id);
        self::assertStringStartsWith('ticket-attachments/tenant-a/', $attachment->path);
        Storage::disk('local')->assertExists($attachment->path);
        self::assertSame($attachment->id, app(CentralTicketGateway::class)->attachmentForCurrentTenant($ticket, $attachment->id)->id);
    }

    public function test_tenant_cannot_reply_after_admin_disables_the_ticket_service(): void
    {
        $this->bindTenant('tenant-a');
        $actor = new User(['name' => 'مستخدم المستأجر']);
        $actor->setAttribute('id', 77);
        $gateway = app(CentralTicketGateway::class);
        $ticket = $gateway->createTicket([
            'subject' => 'خدمة معطلة',
            'description' => 'يجب أن يمنع تعطيل الخدمة أي رد لاحق من المستأجر.',
            'department' => 'technical',
        ], $actor);
        $ticket->update(['service_enabled' => false]);

        $this->expectException(ValidationException::class);

        $gateway->replyTicket($ticket, 'رد لا يجب حفظه', $actor);
    }

    private function bindTenant(string $id): void
    {
        $tenant = new Tenant();
        $tenant->setAttribute('id', $id);

        app()->instance(TenantContract::class, $tenant);
    }
}
