<?php

declare(strict_types=1);

namespace Tests\Feature\Tickets;

use App\Http\Requests\Admin\Tickets\ReplyTicketRequest;
use App\Http\Requests\Tenants\Tickets\TicketReplyRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Settings\Settings;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Tickets\Ticket;
use App\Models\Admin\Tickets\TicketReply;
use App\Models\Admin\Tickets\TicketSection;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class TicketConversationEnhancementsTest extends TestCase
{
    public function test_admin_and_tenant_reply_requests_allow_attachments_without_message_and_reject_empty_messages(): void
    {
        Storage::fake('public');

        foreach ([ReplyTicketRequest::class, TicketReplyRequest::class] as $requestClass) {
            $prepare = new \ReflectionMethod($requestClass, 'prepareForValidation');
            $prepare->setAccessible(true);

            // 1. Whitespace-only message without attachments -> invalid
            $emptyRequest = $requestClass::create('/reply', 'POST', ['message' => '   ']);
            $prepare->invoke($emptyRequest);
            $emptyValidator = Validator::make(
                $emptyRequest->all(),
                $emptyRequest->rules(),
                $emptyRequest->messages(),
                $emptyRequest->attributes(),
            );
            $this->assertTrue($emptyValidator->fails(), "{$requestClass} must reject whitespace-only message without attachments.");
            $this->assertArrayHasKey('message', $emptyValidator->errors()->toArray());

            // 2. Null / whitespace message with valid attachment -> valid
            $file = UploadedFile::fake()->image('evidence.png');
            $attachmentOnlyRequest = $requestClass::create('/reply', 'POST', ['message' => '   '], [], ['attachments' => [$file]]);
            $prepare->invoke($attachmentOnlyRequest);
            $attachmentValidator = Validator::make(
                $attachmentOnlyRequest->all(),
                $attachmentOnlyRequest->rules(),
                $attachmentOnlyRequest->messages(),
                $attachmentOnlyRequest->attributes(),
            );
            $this->assertFalse($attachmentValidator->fails(), "{$requestClass} must accept attachments without a text message.");

            // 3. Valid text message without attachments -> valid
            $textOnlyRequest = $requestClass::create('/reply', 'POST', ['message' => 'رد اختبار صالح']);
            $prepare->invoke($textOnlyRequest);
            $textValidator = Validator::make(
                $textOnlyRequest->all(),
                $textOnlyRequest->rules(),
                $textOnlyRequest->messages(),
                $textOnlyRequest->attributes(),
            );
            $this->assertFalse($textValidator->fails(), "{$requestClass} must accept valid text without attachments.");
        }
    }

    public function test_admin_and_tenant_can_post_attachments_only_reply_and_live_validate(): void
    {
        Storage::fake('public');
        Mail::fake();

        $admin = AdminUser::where('email', 'superadmin@gmail.com')->firstOrFail();
        $tenant = Tenant::findOrFail('test');
        $section = TicketSection::query()->firstOrCreate(
            ['name' => 'قسم اختبار المرفقات'],
            ['is_active' => true],
        );

        $settings = Settings::first();
        $originalEnabled = $settings?->tickets_enabled;
        $originalEtmamUrl = $settings?->etmam_base_url;
        $originalEtmamToken = $settings?->etmam_api_token;

        $ticket = Ticket::create([
            'ticket_number' => 'TKT-ATT-'.uniqid(),
            'tenant_id' => $tenant->id,
            'ticketing_sections_id' => $section->id,
            'title' => 'تذكرة اختبار المرفقات بدون رسالة',
            'description' => 'وصف اختباري',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        try {
            $settings?->update([
                'tickets_enabled' => true,
                'etmam_base_url' => null,
                'etmam_api_token' => null,
            ]);

            // Admin show view renders Select2 without search + scoped composer + attachments dropdown
            $adminShow = $this->actingAs($admin, 'admin')
                ->get(route('admin.tickets.show', $ticket->ticket_number));
            $adminShow->assertOk();
            $adminHtml = $adminShow->getContent();
            $this->assertStringContainsString('id="ticketStatusSelect"', $adminHtml);
            $this->assertStringContainsString('data-select2-search="false"', $adminHtml);
            $this->assertMatchesRegularExpression('/select2-[\w-]+\.css/', $adminHtml);
            $this->assertMatchesRegularExpression('/tickets-show-[\w-]+\.css/', $adminHtml);
            $this->assertStringContainsString('class="ticket-reply-composer', $adminHtml);
            $this->assertStringContainsString('id="ticket-attachments-dropdown"', $adminHtml);
            $this->assertStringContainsString('id="ticket-attachments-list"', $adminHtml);
            $this->assertDoesNotMatchRegularExpression('/<textarea[^>]*id="replyMessage"[^>]*\brequired\b/', $adminHtml);

            // Admin rejects empty reply without attachments
            $this->actingAs($admin, 'admin')
                ->post(route('admin.tickets.reply', $ticket->ticket_number), [
                    'message' => '   ',
                ])
                ->assertSessionHasErrors('message');

            // Admin succeeds with attachment only
            $adminFile = UploadedFile::fake()->create('admin-note.pdf', 120, 'application/pdf');
            $this->actingAs($admin, 'admin')
                ->post(route('admin.tickets.reply', $ticket->ticket_number), [
                    'message' => '',
                    'attachments' => [$adminFile],
                ])
                ->assertRedirect();

            $adminReply = TicketReply::where('ticket_id', $ticket->id)->latest('id')->first();
            $this->assertNotNull($adminReply);
            $this->assertSame('', $adminReply->message);
            $this->assertCount(1, $adminReply->attachments ?? []);
            $this->assertSame('admin-note.pdf', $adminReply->attachments[0]['name'] ?? null);

            // Tenant live validation (wizard-drafts.validate-step) & reply POST
            tenancy()->initialize($tenant);
            $tenantUser = User::where('email', 'demo@lot.com')->firstOrFail();
            $ticket->update(['tenant_user_id' => $tenantUser->id]);

            $tenantShow = $this->actingAs($tenantUser, 'tenant')
                ->get(tenant_route_name('tickets.show', $ticket->ticket_number));
            $tenantShow->assertOk();
            $tenantHtml = $tenantShow->getContent();
            $this->assertStringContainsString('data-validation-dependents="attachments[]"', $tenantHtml);
            $this->assertStringContainsString('data-validation-dependents="message"', $tenantHtml);
            $this->assertMatchesRegularExpression('/tickets-show-[\w-]+\.css/', $tenantHtml);
            $this->assertStringContainsString('ti-trash', $tenantHtml);

            // Live validation fails when message empty and no attachments
            $this->actingAs($tenantUser, 'tenant')
                ->postJson(tenant_route_name('wizard-drafts.validate-step'), [
                    'target_path' => parse_url(tenant_route_name('tickets.reply', $ticket->ticket_number), PHP_URL_PATH),
                    'target_method' => 'POST',
                    'fields' => ['message', 'attachments'],
                    'message' => '',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors('message');

            // Live validation passes when attachment is present without text message
            $tenantFile = UploadedFile::fake()->image('tenant-photo.png');
            $this->actingAs($tenantUser, 'tenant')
                ->post(tenant_route_name('wizard-drafts.validate-step'), [
                    'target_path' => parse_url(tenant_route_name('tickets.reply', $ticket->ticket_number), PHP_URL_PATH),
                    'target_method' => 'POST',
                    'fields' => ['message', 'attachments'],
                    'message' => '',
                    'attachments' => [$tenantFile],
                ], ['Accept' => 'application/json'])
                ->assertOk()
                ->assertJson(['success' => true]);

            // Tenant reply POST succeeds with attachments only
            $tenantUpload = UploadedFile::fake()->image('tenant-photo-2.png');
            $this->actingAs($tenantUser, 'tenant')
                ->post(tenant_route_name('tickets.reply', $ticket->ticket_number), [
                    'message' => '',
                    'attachments' => [$tenantUpload],
                ])
                ->assertRedirect();

            $this->assertSame(2, TicketReply::where('ticket_id', $ticket->id)->count());
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            TicketReply::where('ticket_id', $ticket->id)->delete();
            $ticket->delete();
            if ($settings) {
                $settings->update([
                    'tickets_enabled' => $originalEnabled,
                    'etmam_base_url' => $originalEtmamUrl,
                    'etmam_api_token' => $originalEtmamToken,
                ]);
            }
        }
    }
}
