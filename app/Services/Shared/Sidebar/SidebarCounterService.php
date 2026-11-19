<?php

declare(strict_types=1);

namespace App\Services\Shared\Sidebar;

use App\Enums\Admin\PublicRequests\ContactRequestStatus;
use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\PublicRequests\ContactRequest;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Tickets\Ticket;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\SubscriptionManagement\SubscriptionManagementService;
use App\Services\Shared\Integration\EtmamTicketService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SidebarCounterService
{
    public const CACHE_TTL = 60;

    /**
     * Get all sidebar counters for the Admin panel.
     *
     * @return array<string, int>
     */
    public function getAdminCounts(?AdminUser $user = null): array
    {
        try {
            $user = $user ?? auth('admin')->user();

            $expiring = (int) Cache::remember('admin:expiring_subscriptions_count', self::CACHE_TTL, function (): int {
                try {
                    return app(SubscriptionManagementService::class)->getExpiringSoonCount();
                } catch (Throwable) {
                    return 0;
                }
            });

            $expired = (int) Cache::remember('admin:expired_subscriptions_count', self::CACHE_TTL, function (): int {
                try {
                    return Subscription::whereIn('status', [
                        SubscriptionStatus::EXPIRED,
                        SubscriptionStatus::CANCELLED,
                    ])->count();
                } catch (Throwable) {
                    return 0;
                }
            });

            $pendingContact = (int) Cache::remember('admin:pending_contact_requests_count', self::CACHE_TTL, function (): int {
                try {
                    return ContactRequest::where('status', ContactRequestStatus::PENDING)->count();
                } catch (Throwable) {
                    return 0;
                }
            });

            $pendingTrial = (int) Cache::remember('admin:pending_trial_requests_count', self::CACHE_TTL, function (): int {
                try {
                    return TrialRequest::where('status', TrialRequestStatus::PENDING)->count();
                } catch (Throwable) {
                    return 0;
                }
            });

            $openTickets = (int) Cache::remember('admin:open_tickets_count', self::CACHE_TTL, function (): int {
                try {
                    return Ticket::whereIn('status', ['open', 'reopened'])->count();
                } catch (Throwable) {
                    return 0;
                }
            });

            $rawArchive = $this->getAdminArchiveCounts();

            $canUsers = $this->userCan($user, 'users.view');
            $canTenants = $this->userCan($user, 'companies.view');
            $canPlans = $this->userCan($user, 'plans.view');
            $canCoupons = $this->userCan($user, 'coupons.view');
            $canBills = $this->userCan($user, 'financial_transactions.view');

            $archiveCounts = [
                'users' => $canUsers ? $rawArchive['users'] : 0,
                'tenants' => $canTenants ? $rawArchive['tenants'] : 0,
                'plans' => $canPlans ? $rawArchive['plans'] : 0,
                'coupons' => $canCoupons ? $rawArchive['coupons'] : 0,
                'bills' => $canBills ? $rawArchive['bills'] : 0,
            ];

            $totalArchive = array_sum($archiveCounts);

            return [
                'subscriptions_total' => $expiring + $expired,
                'subscriptions_all' => 0,
                'subscriptions_pending' => 0,
                'subscriptions_expiring' => $expiring,
                'subscriptions_expired' => $expired,
                'contact_requests_pending' => $pendingContact,
                'trial_requests_pending' => $pendingTrial,
                'tickets_open' => $openTickets,
                'archive_admin_total' => $totalArchive,
                'archive_admin_users' => $archiveCounts['users'],
                'archive_admin_tenants' => $archiveCounts['tenants'],
                'archive_admin_plans' => $archiveCounts['plans'],
                'archive_admin_coupons' => $archiveCounts['coupons'],
                'archive_admin_bills' => $archiveCounts['bills'],
            ];
        } catch (Throwable $e) {
            Log::error('SidebarCounterService admin counts failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if (app()->environment('testing')) {
                throw $e;
            }

            return [];
        }
    }

    /**
     * Get raw admin archive counts.
     *
     * @return array{users: int, tenants: int, plans: int, coupons: int, bills: int}
     */
    public function getAdminArchiveCounts(): array
    {
        return Cache::remember('admin:archive_sidebar_counts', self::CACHE_TTL, function (): array {
            try {
                return [
                    'users' => Schema::hasTable('admin_users') ? AdminUser::onlyTrashed()->count() : 0,
                    'tenants' => Schema::hasTable('tenants') ? Tenant::onlyTrashed()->count() : 0,
                    'plans' => Schema::hasTable('plans') ? Plan::onlyTrashed()->notTrial()->count() : 0,
                    'coupons' => Schema::hasTable('coupons') ? Coupon::onlyTrashed()->count() : 0,
                    'bills' => Schema::hasTable('bills') ? Bill::onlyTrashed()->count() : 0,
                ];
            } catch (Throwable) {
                return ['users' => 0, 'tenants' => 0, 'plans' => 0, 'coupons' => 0, 'bills' => 0];
            }
        });
    }

    /**
     * Get all sidebar counters for the Tenant panel.
     *
     * @return array<string, int>
     */
    public function getTenantCounts(?User $user = null): array
    {
        try {
            if (! tenancy()->initialized) {
                return [];
            }

            $tenantId = (string) tenant('id');
            $user = $user ?? auth()->user();

            $pendingAuctions = $user?->can('pending_auctions.view')
                ? (int) Cache::remember("tenant:{$tenantId}:pending_auctions_count", self::CACHE_TTL, fn (): int => Auction::pending()->count())
                : 0;

            $openTickets = (int) Cache::remember("tenant:{$tenantId}:open_tickets_count", self::CACHE_TTL, function (): int {
                try {
                    return app(EtmamTicketService::class)->getTenantTicketStats()['open'] ?? 0;
                } catch (Throwable) {
                    return 0;
                }
            });

            $rawArchive = $this->getTenantArchiveCounts($tenantId);

            $canUsers = $this->userCan($user, 'user_archive.view');
            $canProperties = $this->userCan($user, 'properties_archive.view');
            $canAssignments = $this->userCan($user, 'assignments_archive.view');
            $canAuctions = $this->userCan($user, 'auctions_archive.view');

            $archiveCounts = [
                'users' => $canUsers ? $rawArchive['users'] : 0,
                'properties' => $canProperties ? $rawArchive['properties'] : 0,
                'assignments' => $canAssignments ? $rawArchive['assignments'] : 0,
                'auctions' => $canAuctions ? $rawArchive['auctions'] : 0,
            ];

            $totalArchive = array_sum($archiveCounts);

            return [
                'tickets_open' => $openTickets,
                'pending_auctions' => $pendingAuctions,
                'archive_tenant_total' => $totalArchive,
                'archive_tenant_users' => $archiveCounts['users'],
                'archive_tenant_properties' => $archiveCounts['properties'],
                'archive_tenant_assignments' => $archiveCounts['assignments'],
                'archive_tenant_auctions' => $archiveCounts['auctions'],
            ];
        } catch (Throwable $e) {
            Log::error('SidebarCounterService admin counts failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if (app()->environment('testing')) {
                throw $e;
            }

            return [];
        }
    }

    /**
     * Get raw tenant archive counts.
     *
     * @return array{users: int, properties: int, assignments: int, auctions: int}
     */
    public function getTenantArchiveCounts(?string $tenantId = null): array
    {
        $tenantId = $tenantId ?? (tenancy()->initialized ? (string) tenant('id') : 'unknown');

        return Cache::remember("tenant:{$tenantId}:archive_sidebar_counts", self::CACHE_TTL, function (): array {
            try {
                return [
                    'users' => Schema::hasTable('users') ? User::onlyTrashed()->count() : 0,
                    'properties' => Schema::hasTable('properties') ? Property::onlyTrashed()->count() : 0,
                    'assignments' => Schema::hasTable('assignments') ? Assignment::onlyTrashed()->count() : 0,
                    'auctions' => Schema::hasTable('auctions') ? Auction::onlyTrashed()->count() : 0,
                ];
            } catch (Throwable) {
                return ['users' => 0, 'properties' => 0, 'assignments' => 0, 'auctions' => 0];
            }
        });
    }

    /**
     * Clear cached admin archive counts.
     */
    public function clearAdminArchiveCache(): void
    {
        Cache::forget('admin:archive_sidebar_counts');
    }

    /**
     * Clear all cached admin sidebar counts.
     */
    public function clearAdminCache(): void
    {
        Cache::forget('admin:expiring_subscriptions_count');
        Cache::forget('admin:expired_subscriptions_count');
        Cache::forget('admin:pending_contact_requests_count');
        Cache::forget('admin:pending_trial_requests_count');
        Cache::forget('admin:open_tickets_count');
        Cache::forget('admin:archive_sidebar_counts');
    }

    /**
     * Clear cached tenant archive counts.
     */
    public function clearTenantArchiveCache(?string $tenantId = null): void
    {
        $tenantId = $tenantId ?? (tenancy()->initialized ? (string) tenant('id') : null);
        if ($tenantId) {
            Cache::forget("tenant:{$tenantId}:archive_sidebar_counts");
        }
    }

    /**
     * Clear all cached tenant sidebar counts.
     */
    public function clearTenantCache(?string $tenantId = null): void
    {
        $tenantId = $tenantId ?? (tenancy()->initialized ? (string) tenant('id') : null);
        if ($tenantId) {
            Cache::forget("tenant:{$tenantId}:open_tickets_count");
            Cache::forget("tenant:{$tenantId}:archive_sidebar_counts");
            Cache::forget("tenant:{$tenantId}:pending_auctions_count");
        }
    }

    private function userCan(?Model $user, string $permission): bool
    {
        if (! $user) {
            return true;
        }
        try {
            return (bool) $user->can($permission);
        } catch (Throwable) {
            return true;
        }
    }
}
