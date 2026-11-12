<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Admin\PublicRequests\ReplyToContactRequestAction;
use App\Enums\Admin\PublicRequests\ContactRequestStatus;
use App\Mail\PublicRequests\ContactReplyMail;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\PublicRequests\ContactRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class ContactRequestReplyMailTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    public function test_reply_to_contact_request_dispatches_email_synchronously_to_customer(): void
    {
        Mail::fake();

        $admin = AdminUser::firstOrFail();

        $contactRequest = ContactRequest::create([
            'customer_name' => 'سارة أحمد',
            'customer_email' => 'sara@example.test',
            'message' => 'استفسار بخصوص تجربة المنصة للمزادات',
            'status' => ContactRequestStatus::PENDING,
        ]);

        $replyAction = app(ReplyToContactRequestAction::class);
        $updated = $replyAction->execute($contactRequest, 'شكراً لتواصلك، تم تفعيل حسابك التجريبي بنجاح.', $admin);

        self::assertSame(ContactRequestStatus::REPLIED, $updated->status);
        self::assertSame('شكراً لتواصلك، تم تفعيل حسابك التجريبي بنجاح.', $updated->reply);

        Mail::assertQueued(ContactReplyMail::class, function (ContactReplyMail $mail) use ($contactRequest): bool {
            return $mail->hasTo($contactRequest->customer_email)
                && $mail->connection === 'sync'
                && $mail->customerName === $contactRequest->customer_name
                && $mail->originalMessage === $contactRequest->message
                && str_contains($mail->replyMessage, 'تم تفعيل حسابك التجريبي بنجاح');
        });
    }

    public function test_controller_reply_route_sends_email_and_marks_status_replied(): void
    {
        Mail::fake();

        $admin = AdminUser::firstOrFail();

        $contactRequest = ContactRequest::create([
            'customer_name' => 'خالد منصور',
            'customer_email' => 'khaled@example.test',
            'message' => 'هل تدعم المنصة المزادات الهجينة؟',
            'status' => ContactRequestStatus::PENDING,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.contact-requests.reply', $contactRequest), [
                'reply' => 'نعم، المنصة تدعم المزادات الحضورية والإلكترونية والهجينة.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $contactRequest->refresh();
        self::assertSame(ContactRequestStatus::REPLIED, $contactRequest->status);
        self::assertSame('نعم، المنصة تدعم المزادات الحضورية والإلكترونية والهجينة.', $contactRequest->reply);

        Mail::assertQueued(ContactReplyMail::class, function (ContactReplyMail $mail) use ($contactRequest): bool {
            return $mail->hasTo($contactRequest->customer_email)
                && $mail->connection === 'sync'
                && $mail->customerName === $contactRequest->customer_name;
        });
    }
}
