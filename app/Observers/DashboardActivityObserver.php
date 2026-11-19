<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\PublicRequests\ContactRequest;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Tickets\Ticket;
use App\Models\Admin\Tickets\TicketReply;
use App\Models\Tenants\Assignments\Assignment as TenantAssignment;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction as TenantAuction;
use App\Models\Tenants\Properties\Property as TenantProperty;
use App\Models\Tenants\Ticket\Ticket as TenantTicket;
use App\Models\Tenants\UserMangment\User\User as TenantUser;
use App\Services\Shared\Notifications\DashboardNotificationService as Notices;
use App\Services\Shared\Notifications\NotificationDeadlineScanner;
use App\Services\Shared\Sidebar\SidebarCounterService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardActivityObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Model $model): void
    {
        $this->process($model, true);
    }

    public function updated(Model $model): void
    {
        $this->process($model, false);
    }

    public function deleted(Model $model): void
    {
        $this->processArchiveChange($model);
    }

    public function restored(Model $model): void
    {
        $this->processArchiveChange($model);
    }

    public function forceDeleted(Model $model): void
    {
        $this->processArchiveChange($model);
    }

    private function process(Model $model, bool $created): void
    {
        if (! config('dashboard-notifications.enabled', true)) {
            return;
        }
        try {
            $notices = app(Notices::class);
            $sidebarService = app(SidebarCounterService::class);

            if ($model instanceof TrialRequest) {
                $sidebarService->clearAdminCache();
                if (($created || $model->wasChanged('email_verified_at')) && $model->email_verified_at && $model->status->value === 'pending') {
                    $notices->admins("trial.requested:$model->id", Notices::payload('trial.requested', 'طلب تجربة جديد', "طلب تجربة من {$model->customer_name} — {$model->company_name}", route('admin.trial-requests.show', $model, false)));
                }
                if (! $created && $model->wasChanged('status')) {
                    $notices->refreshAdmins();
                    if ($model->status->value === 'approved' && $model->tenant_id) {
                        $notices->tenant($model->tenant_id, "trial.approved:$model->id", Notices::payload('trial.approved', 'تمت الموافقة على طلب التجربة', 'حساب شركتك جاهز للاستخدام. بدأت تجربتك المجانية لمدة 7 أيام.', route('path.dashboard', ['tenant' => $model->tenant_id], false), 'success'));
                    }
                }
            } elseif ($model instanceof ContactRequest) {
                $sidebarService->clearAdminCache();
                if ($created) {
                    $notices->admins("contact.requested:$model->id", Notices::payload('contact.requested', 'طلب تواصل جديد', "طلب تواصل من {$model->customer_name}", route('admin.contact-requests.show', $model, false)));
                } elseif ($model->wasChanged('status')) {
                    $notices->refreshAdmins();
                }
            } elseif ($model instanceof Bill && $model->type->value === 'new_subscription') {
                $sidebarService->clearAdminCache();
                if ($created && (($model->metadata['checkout_source'] ?? null) === 'site_checkout' || isset($model->metadata['tenant_id']))) {
                    $notices->admins("subscription.requested:$model->id", Notices::payload('subscription.requested', 'تسجيل عميل جديد', 'تسجيل عميل جديد من '.($model->metadata['billing_company_name'] ?? $model->metadata['billing_name'] ?? 'عميل'), route('admin.bills.show', $model, false)));
                }
                if ($model->status->value === 'paid' && $model->tenant_id && ($created || $model->wasChanged(['status', 'tenant_id']))) {
                    $notices->tenant($model->tenant_id, "subscription.approved:$model->id", Notices::payload('subscription.approved', 'تم تفعيل الاشتراك', 'تم تفعيل اشتراك شركتك وأصبح جاهزًا للاستخدام.', route('path.subscriptions.index', ['tenant' => $model->tenant_id], false), 'success'));
                    $notices->refreshAdmins();
                } elseif (! $created && $model->wasChanged('status')) {
                    $notices->refreshAdmins();
                }
            } elseif ($model instanceof Ticket) {
                $sidebarService->clearAdminCache();
                if ($created) {
                    $notices->admins("ticket.created:$model->id", Notices::payload('ticket.created', 'تذكرة دعم جديدة', "تذكرة #{$model->ticket_number}: {$model->title}", route('admin.tickets.show', $model->ticket_number, false), 'info', 'ti ti-ticket'));
                } elseif ($model->wasChanged('status')) {
                    $notices->refreshAdmins();
                }
            } elseif ($model instanceof TenantTicket || $model instanceof TenantAuction || $model instanceof AuctionEstablishmentRequest) {
                if (tenancy()->initialized) {
                    $sidebarService->clearTenantCache((string) tenant('id'));
                    $notices->refreshTenant((string) tenant('id'));
                }
            } elseif ($model instanceof TicketReply && $created) {
                $ticket = $model->ticket;
                if (! $ticket) {
                    return;
                }
                if ($model->sender_type === 'support_user') {
                    $notices->tenant($ticket->tenant_id, "ticket.reply:$model->id", Notices::payload('support.replied', 'رد جديد من الدعم الفني', "تمت إضافة رد على التذكرة #{$ticket->ticket_number}.", route('path.tickets.show', ['tenant' => $ticket->tenant_id, 'ticket' => $ticket->ticket_number], false), 'info', 'ti ti-message-dots'), $ticket->tenant_user_id);
                } else {
                    $notices->admins("ticket.reply:$model->id", Notices::payload('support.message', 'رسالة جديدة في الدعم الفني', "رسالة من العميل على التذكرة #{$ticket->ticket_number}.", route('admin.tickets.show', $ticket->ticket_number, false), 'info', 'ti ti-message-dots'));
                }
            } elseif ($model instanceof Coupon) {
                $sidebarService->clearAdminCache();
                app(NotificationDeadlineScanner::class)->coupon($model);
            } elseif ($model instanceof Subscription && ! $created && $model->wasChanged('status')) {
                $sidebarService->clearAdminCache();
                $notices->refreshAdmins();
                if ($model->status->value === 'expired') {
                    app(NotificationDeadlineScanner::class)->subscription($model);
                }
            }
        } catch (Throwable $exception) {
            Log::error('Dashboard event notification failed.', ['model' => get_class($model), 'id' => $model->getKey(), 'error' => $exception->getMessage()]);
        }
    }

    private function processArchiveChange(Model $model): void
    {
        if (! config('dashboard-notifications.enabled', true)) {
            return;
        }

        try {
            $sidebarService = app(SidebarCounterService::class);
            $notices = app(Notices::class);

            if ($this->isAdminArchiveModel($model)) {
                $sidebarService->clearAdminArchiveCache();
                $notices->refreshAdmins();

                return;
            }

            if ($this->isTenantArchiveModel($model) || $model instanceof AuctionEstablishmentRequest) {
                $tenantId = tenancy()->initialized ? (string) tenant('id') : null;
                if ($tenantId) {
                    if ($model instanceof TenantAuction || $model instanceof AuctionEstablishmentRequest) {
                        $sidebarService->clearTenantCache($tenantId);
                    } else {
                        $sidebarService->clearTenantArchiveCache($tenantId);
                    }
                    $notices->refreshTenant($tenantId);
                }
            }
        } catch (Throwable $exception) {
            Log::error('Dashboard archive change notification failed.', [
                'model' => get_class($model),
                'id' => $model->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function isAdminArchiveModel(Model $model): bool
    {
        return $model instanceof AdminUser
            || $model instanceof Tenant
            || $model instanceof Plan
            || $model instanceof Coupon
            || $model instanceof Bill;
    }

    private function isTenantArchiveModel(Model $model): bool
    {
        return $model instanceof TenantUser
            || $model instanceof TenantProperty
            || $model instanceof TenantAssignment
            || $model instanceof TenantAuction;
    }
}
