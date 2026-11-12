<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Mail\Auth\OtpMail;
use App\Mail\Auth\PasswordResetMail;
use App\Mail\Auth\UserWelcomeMail;
use App\Mail\Auth\VerifyEmailMail;
use App\Mail\Billing\BankTransferAdminAlertMail;
use App\Mail\Billing\BankTransferApprovedMail;
use App\Mail\Billing\BankTransferPendingMail;
use App\Mail\Billing\BankTransferRejectedMail;
use App\Mail\PublicRequests\ContactReplyMail;
use App\Mail\PublicRequests\ContactRequestAdminAlertMail;
use App\Mail\PublicRequests\ContactRequestReceivedMail;
use App\Mail\PublicRequests\TrialRequestAdminAlertMail;
use App\Mail\PublicRequests\TrialRequestApprovedMail;
use App\Mail\PublicRequests\TrialRequestReceivedMail;
use App\Mail\PublicRequests\TrialRequestRejectedMail;
use App\Mail\Subscriptions\NewSubscriptionMail;
use App\Mail\Subscriptions\RenewSubscriptionMail;
use App\Mail\Subscriptions\SubscriptionExpiredMail;
use App\Mail\Subscriptions\SubscriptionExpiringMail;
use App\Mail\Subscriptions\UpgradeSubscriptionMail;
use App\Mail\Tenants\TenantWelcomeMail;
use App\Mail\Tickets\TicketCreatedAdminMail;
use App\Mail\Tickets\TicketCreatedClientMail;
use App\Mail\Tickets\TicketReplyMail;
use App\Mail\Tickets\TicketStatusUpdatedMail;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Tickets\Ticket;
use App\Models\Admin\Tickets\TicketReply;
use Tests\TestCase;

final class AllMailablesRenderTest extends TestCase
{
    public function test_all_system_mailables_render_without_errors(): void
    {
        $tenant = new Tenant([
            'id' => 'test-tenant',
            'company_name' => 'شركة تجريبية',
            'primary_contact_name' => 'محمد علي',
            'primary_contact_email' => 'mohamed@example.test',
        ]);
        $tenant->exists = true;

        $plan = new Plan([
            'id' => 1,
            'name' => 'الباقة المتقدمة',
            'price' => 1000,
        ]);
        $plan->exists = true;

        $bill = new Bill([
            'bill_number' => 'BILL-1001',
            'amount' => 1150,
            'status' => BillStatus::PENDING,
            'type' => BillType::NEW_SUBSCRIPTION,
            'tenant_id' => 'test-tenant',
            'metadata' => [
                'billing_company_name' => 'شركة تجريبية',
                'billing_name' => 'محمد علي',
            ],
        ]);
        $bill->id = 10;
        $bill->setRelation('tenant', $tenant);
        $bill->exists = true;

        $subscription = new Subscription([
            'subscription_number' => 'SUB-5001',
            'total_users' => 5,
            'price' => 1000,
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
            'tenant_id' => 'test-tenant',
            'plan_id' => 1,
        ]);
        $subscription->id = 5;
        $subscription->setRelation('tenant', $tenant);
        $subscription->setRelation('plan', $plan);
        $subscription->exists = true;

        $ticket = new Ticket([
            'ticket_number' => 'TCK-2001',
            'title' => 'مشكلة في تسجيل الدخول',
            'status' => 'open',
            'tenant_id' => 'test-tenant',
        ]);
        $ticket->id = 20;
        $ticket->setRelation('tenant', $tenant);
        $ticket->exists = true;

        $ticketReply = new TicketReply([
            'message' => 'تم استلام طلبكم وجارٍ العمل عليه.',
            'user_name' => 'فريق الدعم الفني',
        ]);
        $ticketReply->id = 1;
        $ticketReply->exists = true;

        $trialRequest = new TrialRequest([
            'customer_name' => 'أحمد خالد',
            'customer_email' => 'ahmed@example.test',
            'company_name' => 'شركة التقنية الحديثة',
        ]);
        $trialRequest->id = 30;
        $trialRequest->exists = true;

        $mailables = [
            'OtpMail' => new OtpMail('123456'),
            'PasswordResetMail' => new PasswordResetMail('https://example.com/reset', 'أحمد'),
            'UserWelcomeMail' => new UserWelcomeMail('أحمد', 'ahmed@test.com', 'Pass123', 'https://example.com/login'),
            'VerifyEmailMail' => new VerifyEmailMail('https://example.com/verify', 'أحمد'),
            'BankTransferAdminAlertMail' => new BankTransferAdminAlertMail($bill),
            'BankTransferApprovedMail' => new BankTransferApprovedMail($bill),
            'BankTransferPendingMail' => new BankTransferPendingMail($bill),
            'BankTransferRejectedMail' => new BankTransferRejectedMail($bill, 'إيصال غير واضح'),
            'ContactReplyMail' => new ContactReplyMail('سارة', 'رسالة أصلية', 'رد الدعم'),
            'ContactRequestAdminAlertMail' => new ContactRequestAdminAlertMail('سارة', 'sara@test.com', 'محتوى الرسالة'),
            'ContactRequestReceivedMail' => new ContactRequestReceivedMail('سارة'),
            'TrialRequestAdminAlertMail' => new TrialRequestAdminAlertMail($trialRequest),
            'TrialRequestApprovedMail' => new TrialRequestApprovedMail('أحمد', 'ahmed@test.com', 'https://example.com/login'),
            'TrialRequestReceivedMail' => new TrialRequestReceivedMail('أحمد', 'شركة التقنية'),
            'TrialRequestRejectedMail' => new TrialRequestRejectedMail('أحمد', 'عدم استيفاء الشروط'),
            'NewSubscriptionMail' => new NewSubscriptionMail($subscription, $bill),
            'RenewSubscriptionMail' => new RenewSubscriptionMail($subscription, $bill),
            'SubscriptionExpiredMail' => new SubscriptionExpiredMail($subscription),
            'SubscriptionExpiringMail' => new SubscriptionExpiringMail($subscription, 7),
            'UpgradeSubscriptionMail' => new UpgradeSubscriptionMail($subscription, 2),
            'TenantWelcomeMail' => new TenantWelcomeMail($tenant, 'https://example.com/login', 'admin@tenant.com'),
            'TicketCreatedAdminMail' => new TicketCreatedAdminMail($ticket),
            'TicketCreatedClientMail' => new TicketCreatedClientMail($ticket),
            'TicketReplyMail_support' => new TicketReplyMail($ticket, $ticketReply, 'العميل', true),
            'TicketReplyMail_client' => new TicketReplyMail($ticket, $ticketReply, 'الدعم', false),
            'TicketStatusUpdatedMail' => new TicketStatusUpdatedMail($ticket, 'in_progress'),
        ];

        foreach ($mailables as $name => $mailable) {
            try {
                $html = $mailable->render();
                $this->assertNotEmpty($html, "Mailable {$name} rendered empty HTML");
            } catch (\Throwable $e) {
                $this->fail("Mailable {$name} threw exception: {$e->getMessage()} at {$e->getFile()}:{$e->getLine()}");
            }
        }
    }
}
