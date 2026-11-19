<?php

declare(strict_types=1);

namespace App\Providers;

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
use App\Models\Tenants\UserMangment\User\User;
use App\Observers\DashboardActivityObserver;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class DashboardNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ([
            TrialRequest::class,
            ContactRequest::class,
            Bill::class,
            Ticket::class,
            TenantTicket::class,
            TicketReply::class,
            Coupon::class,
            Subscription::class,
            AdminUser::class,
            Tenant::class,
            Plan::class,
            User::class,
            TenantProperty::class,
            TenantAssignment::class,
            TenantAuction::class,
            AuctionEstablishmentRequest::class,
        ] as $model) {
            $model::observe(DashboardActivityObserver::class);
        }

        $broadcaster = Broadcast::connection('reverb');
        $broadcaster->channel('dashboard.admin.{id}', fn ($user, $id): bool => $user instanceof AdminUser && (string) $user->id === (string) $id, ['guards' => ['admin']]);
        $broadcaster->channel('dashboard.tenant.{tenantId}.user.{id}', fn ($user, $tenantId, $id): bool => $user instanceof User && tenancy()->initialized && (string) tenant('id') === (string) $tenantId && (string) $user->id === (string) $id, ['guards' => ['tenant']]);
    }
}
