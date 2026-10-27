<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Notifications\NotificationDelivery;
use App\Models\Admin\PublicRequests\ContactRequest;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tickets\Ticket;
use App\Models\Admin\Tickets\TicketReply;
use App\Models\Tenants\UserMangment\User\User;
use App\Observers\DashboardActivityObserver;
use App\Services\Shared\Notifications\DashboardNotificationService as Notices;
use App\Services\Shared\Notifications\NotificationDeadlineScanner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\TenantAuctionReportTestCase;

class DashboardNotificationsTest extends TenantAuctionReportTestCase
{
    private User $owner;

    private AdminUser $admin;

    private array $existingDeliveryIds;

    protected function setUp(): void
    {
        parent::setUp();
        // The scanner intentionally switches tenant connections. Clean up only
        // this test's new notification UUIDs instead of relying on a purged PDO.
        DB::connection('tenant')->rollBack();
        config(['dashboard-notifications.enabled' => true, 'dashboard-notifications.broadcast' => false]);
        $this->existingDeliveryIds = NotificationDelivery::pluck('id')->all();
        $this->owner = User::create(['name' => 'اختبار الإشعارات', 'email' => 'notices-'.Str::lower(Str::random(10)).'@example.test', 'password' => bcrypt('NotificationTest123!'), 'status' => 'active']);
        $this->tenant->update(['primary_user_global_uid' => $this->owner->global_uid]);
        $this->admin = AdminUser::where('email', 'superadmin@gmail.com')->firstOrFail();
    }

    protected function tearDown(): void
    {
        $ids = NotificationDelivery::whereNotIn('id', $this->existingDeliveryIds)->pluck('id')->all();
        $this->tenant->run(function () use ($ids): void {
            DB::connection('tenant')->table('notifications')->whereIn('id', $ids)->delete();
            $this->owner->forceDelete();
        });
        $this->travelBack();
        parent::tearDown();
    }

    private function service(): Notices
    {
        return app(Notices::class);
    }

    private function observer(): DashboardActivityObserver
    {
        return app(DashboardActivityObserver::class);
    }

    private function tenantCount(string $event): int
    {
        return $this->tenant->run(fn () => $this->owner->notifications()->where('data->event', $event)->count());
    }

    private function adminCount(string $event): int
    {
        return $this->admin->notifications()->where('data->event', $event)->count();
    }

    public function test_committed_public_request_events_target_superadmins(): void
    {
        $contact = ContactRequest::create(['customer_name' => 'عميل', 'customer_email' => 'contact@example.test', 'message' => 'رسالة', 'status' => 'pending']);
        $trial = TrialRequest::create(['customer_name' => 'عميل', 'customer_email' => 'trial@example.test', 'customer_phone' => '0500000000', 'company_name' => 'شركة اختبار', 'email_verified_at' => now(), 'status' => 'pending']);
        $bill = Bill::create(['amount' => 100, 'original_amount' => 100, 'type' => 'new_subscription', 'status' => 'pending', 'payment_method' => 'online', 'metadata' => ['checkout_source' => 'site_checkout', 'billing_company_name' => 'شركة الاختبار']]);
        $before = [$this->adminCount('contact.requested'), $this->adminCount('trial.requested'), $this->adminCount('subscription.requested')];
        foreach ([$contact, $trial, $bill] as $model) {
            $this->observer()->created($model);
            $this->observer()->created($model);
        }
        self::assertSame($before[0] + 1, $this->adminCount('contact.requested'));
        self::assertSame($before[1] + 1, $this->adminCount('trial.requested'));
        self::assertSame($before[2] + 1, $this->adminCount('subscription.requested'));
        self::assertSame(0, $this->owner->notifications()->count());
    }

    public function test_approval_notifications_are_created_for_the_tenant(): void
    {
        $trial = TrialRequest::create(['customer_name' => 'عميل', 'customer_email' => 'approved@example.test', 'customer_phone' => '0500000000', 'status' => 'pending']);
        $trial->update(['status' => 'approved', 'tenant_id' => $this->tenant->id]);
        $this->observer()->updated($trial);
        $bill = Bill::create(['amount' => 100, 'original_amount' => 100, 'type' => 'new_subscription', 'status' => 'pending', 'payment_method' => 'online']);
        $bill->update(['status' => 'paid', 'tenant_id' => $this->tenant->id]);
        $this->observer()->updated($bill);
        self::assertSame(1, $this->tenantCount('trial.approved'));
        self::assertSame(1, $this->tenantCount('subscription.approved'));
    }

    public function test_ticket_messages_notify_the_correct_side_without_duplicates(): void
    {
        $ticket = Ticket::create(['ticket_number' => 'NTF-'.Str::random(8), 'tenant_id' => $this->tenant->id, 'tenant_user_id' => $this->owner->id, 'title' => 'مساعدة', 'description' => 'اختبار', 'status' => 'open']);
        $before = $this->adminCount('ticket.created');
        $this->observer()->created($ticket);
        self::assertSame($before + 1, $this->adminCount('ticket.created'));
        $client = TicketReply::create(['ticket_id' => $ticket->id, 'ticket_number' => $ticket->ticket_number, 'sender_type' => 'tenant_user', 'message' => 'رسالة العميل']);
        $support = TicketReply::create(['ticket_id' => $ticket->id, 'ticket_number' => $ticket->ticket_number, 'sender_type' => 'support_user', 'message' => 'رد الدعم']);
        $before = $this->adminCount('support.message');
        $this->observer()->created($client);
        $this->observer()->created($support);
        $this->observer()->created($support);
        self::assertSame($before + 1, $this->adminCount('support.message'));
        self::assertSame(1, $this->tenantCount('support.replied'));
    }

    public function test_deadline_thresholds_fire_once_and_expiry_reaches_both_sides(): void
    {
        $end = now()->addDays(31)->startOfSecond();
        $subscription = Subscription::create(['tenant_id' => $this->tenant->id, 'plan_id' => null, 'price' => 0, 'status' => 'active', 'total_users' => 1, 'used_users' => 1, 'starts_at' => now(), 'ends_at' => $end, 'auto_renew' => false]);
        $scanner = app(NotificationDeadlineScanner::class);
        $scanner->subscription($subscription);
        self::assertSame(0, $this->tenantCount('subscription.expiring30'));
        $this->travelTo($end->copy()->subDays(30)->addSecond());
        $scanner->subscription($subscription->fresh());
        $scanner->subscription($subscription->fresh());
        self::assertSame(1, $this->tenantCount('subscription.expiring30'));
        $before7 = $this->adminCount('subscription.expiring7');
        $this->travelTo($end->copy()->subDays(7)->addSecond());
        $scanner->subscription($subscription->fresh());
        $scanner->subscription($subscription->fresh());
        self::assertSame(1, $this->tenantCount('subscription.expiring7'));
        self::assertSame($before7 + 1, $this->adminCount('subscription.expiring7'));
        $expiredBefore = $this->adminCount('subscription.expired');
        $this->travelTo($end->copy()->addSecond());
        $scanner->subscription($subscription->fresh());
        $scanner->subscription($subscription->fresh());
        self::assertSame(1, $this->tenantCount('subscription.expired'));
        self::assertSame($expiredBefore + 1, $this->adminCount('subscription.expired'));
    }

    public function test_coupon_time_and_usage_limits_have_separate_once_only_alerts(): void
    {
        $coupon = Coupon::create(['code' => 'NTF-'.Str::random(8), 'name' => 'اختبار', 'discount_type' => 'fixed', 'discount_value' => 5, 'expiry_date' => now()->subDay(), 'usage_limit' => 1, 'used_count' => 1, 'created_by' => $this->admin->id]);
        $before = [$this->adminCount('coupon.expired'), $this->adminCount('coupon.exhausted')];
        app(NotificationDeadlineScanner::class)->coupon($coupon);
        app(NotificationDeadlineScanner::class)->coupon($coupon);
        self::assertSame($before[0] + 1, $this->adminCount('coupon.expired'));
        self::assertSame($before[1] + 1, $this->adminCount('coupon.exhausted'));
    }

    public function test_read_delete_and_broadcast_auth_are_scoped_to_the_current_user_and_tenant(): void
    {
        $key = 'test.own:'.Str::uuid();
        $this->service()->tenant($this->tenant->id, $key, Notices::payload('test', 'عنوان', '<script>unsafe</script>', '/test/dashboard'), $this->owner->id);
        $notice = $this->owner->notifications()->firstOrFail();
        $this->actingAs($this->owner, 'tenant');
        $parameters = ['tenant' => $this->tenant->id];
        $this->getJson(route('path.dashboard-notifications.feed', $parameters))->assertOk()->assertJsonPath('count', 1)->assertJsonPath('items.0.id', $notice->id);
        $this->get(route('path.dashboard-notifications.index', $parameters))
            ->assertOk()
            ->assertSee('كل الإشعارات')
            ->assertSee('تحديد الكل كمقروء')
            ->assertSee('غير المقروءة')
            ->assertSee('المقروءة');
        $this->get(route('path.dashboard-notifications.index', $parameters + ['filter' => 'unread']))->assertOk();
        $this->get(route('path.dashboard-notifications.index', $parameters + ['filter' => 'read']))->assertOk();
        $this->postJson(route('path.dashboard-notifications.auth', $parameters), ['socket_id' => '123.456', 'channel_name' => "private-dashboard.tenant.{$this->tenant->id}.user.{$this->owner->id}"])->assertOk()->assertJsonStructure(['auth']);
        $this->actingAs($this->admin, 'admin');
        $this->actingAs($this->owner, 'tenant');
        foreach (["private-dashboard.tenant.other.user.{$this->owner->id}", 'private-dashboard.admin.'.$this->admin->id] as $channel) {
            $this->postJson(route('path.dashboard-notifications.auth', $parameters), ['socket_id' => '123.456', 'channel_name' => $channel])->assertForbidden();
        }
        $this->postJson(route('path.dashboard-notifications.read', $parameters + ['notification' => $notice->id]))->assertOk();
        $this->getJson(route('path.dashboard-notifications.feed', $parameters))->assertJsonPath('count', 0);
        $this->deleteJson(route('path.dashboard-notifications.destroy', $parameters + ['notification' => $notice->id]))->assertOk();
        $this->service()->tenant($this->tenant->id, $key, Notices::payload('test', 'عنوان', 'رسالة', '/test/dashboard'), $this->owner->id);
        self::assertSame(0, $this->owner->notifications()->count(), 'Deleted reminders must not return on the next scan.');
        $this->postJson(route('path.dashboard-notifications.read', $parameters + ['notification' => (string) Str::uuid()]))->assertNotFound();
    }

    public function test_admin_feed_and_private_channel_are_not_accessible_as_another_admin(): void
    {
        $this->actingAs($this->admin, 'admin');
        $this->getJson(route('admin.dashboard-notifications.feed'))
            ->assertOk()
            ->assertJsonPath('broadcast.channel', 'dashboard.admin.'.$this->admin->id)
            ->assertJsonStructure([
                'items',
                'count',
                'sidebar_counts' => [
                    'subscriptions_total',
                    'subscriptions_all',
                    'subscriptions_pending',
                    'subscriptions_expiring',
                    'subscriptions_expired',
                    'contact_requests_pending',
                    'trial_requests_pending',
                    'tickets_open',
                ],
                'broadcast',
            ]);
        $this->get(route('admin.dashboard-notifications.index'))->assertOk()->assertSee('كل الإشعارات');
        $this->postJson(route('admin.dashboard-notifications.auth'), ['socket_id' => '123.456', 'channel_name' => 'private-dashboard.admin.'.$this->admin->id])->assertOk();
        $this->postJson(route('admin.dashboard-notifications.auth'), ['socket_id' => '123.456', 'channel_name' => 'private-dashboard.admin.999999'])->assertForbidden();
    }

    public function test_rolled_back_business_events_do_not_emit_notifications(): void
    {
        $before = NotificationDelivery::count();
        DB::connection('mysql')->beginTransaction();
        ContactRequest::create(['customer_name' => 'تراجع', 'customer_email' => 'rollback@example.test', 'message' => 'لا ترسل', 'status' => 'pending']);
        DB::connection('mysql')->rollBack();
        self::assertSame($before, NotificationDelivery::count());
    }
}
