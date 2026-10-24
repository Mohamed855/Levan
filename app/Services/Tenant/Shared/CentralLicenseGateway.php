<?php

declare(strict_types=1);

namespace App\Services\Tenant\Shared;

use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\LicenseSeat;
use App\Models\Admin\Subscriptions\Subscription;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Stancl\Tenancy\Exceptions\TenancyNotInitializedException;

/** Keeps central commercial entitlements separate from tenant users and their database. */
final class CentralLicenseGateway
{
    public function ensureCoreSeat(string $tenantId, int $subscriptionId, int $userId): LicenseSeat
    {
        return DB::connection($this->connection())->transaction(function () use ($tenantId, $subscriptionId, $userId): LicenseSeat {
            $existing = $this->seats()->where('tenant_id', $tenantId)->where('assigned_user_id', $userId)->lockForUpdate()->first();
            if ($existing !== null) {
                return $existing;
            }

            return $this->seats()->create([
                'tenant_id' => $tenantId,
                'subscription_id' => $subscriptionId,
                'assigned_user_id' => $userId,
                'status' => 'assigned',
                'assigned_at' => now(),
                'is_core' => true,
            ]);
        });
    }

    public function assertCanCreateUser(): void
    {
        $subscription = $this->activeSubscription();
        if ($subscription === null) {
            throw new DomainException('لا يوجد اشتراك نشط للمنشأة.');
        }

        if ((int) $subscription->used_users >= (int) $subscription->total_users) {
            throw new DomainException('لا توجد تراخيص مستخدمين متاحة في الاشتراك الحالي.');
        }
    }

    public function assignCurrentTenantUser(int $userId): LicenseSeat
    {
        $tenantId = $this->tenantId();

        return DB::connection($this->connection())->transaction(function () use ($tenantId, $userId): LicenseSeat {
            $existing = $this->seats()->where('tenant_id', $tenantId)->where('assigned_user_id', $userId)->lockForUpdate()->first();
            if ($existing !== null) {
                return $existing;
            }

            $subscription = $this->activeSubscription(lock: true);
            if ($subscription === null || (int) $subscription->used_users >= (int) $subscription->total_users) {
                throw new DomainException('لا توجد تراخيص مستخدمين متاحة في الاشتراك الحالي.');
            }

            $seat = $this->seats()
                ->where('tenant_id', $tenantId)
                ->where('subscription_id', $subscription->getKey())
                ->where('status', 'free')
                ->lockForUpdate()
                ->first();

            if ($seat === null) {
                $seat = $this->seats()->create([
                    'tenant_id' => $tenantId,
                    'subscription_id' => $subscription->getKey(),
                    'status' => 'free',
                ]);
            }

            $seat->update(['assigned_user_id' => $userId, 'status' => 'assigned', 'assigned_at' => now()]);
            $subscription->increment('used_users');

            return $seat->fresh();
        });
    }

    public function releaseCurrentTenantUser(int $userId, bool $enforceWaitingPeriod = true): void
    {
        $tenantId = $this->tenantId();

        DB::connection($this->connection())->transaction(function () use ($tenantId, $userId, $enforceWaitingPeriod): void {
            $seat = $this->seats()->where('tenant_id', $tenantId)->where('assigned_user_id', $userId)->lockForUpdate()->first();
            if ($seat === null) {
                return;
            }
            if ($seat->is_core) {
                throw new DomainException('لا يمكن تحرير الترخيص الأساسي لمالك المنشأة.');
            }
            if ($enforceWaitingPeriod && $seat->assigned_at?->gt(now()->subDays(30))) {
                throw new DomainException('لا يمكن تحرير الترخيص إلا بعد مرور 30 يومًا من تخصيصه.');
            }

            $subscription = $this->subscriptions()->whereKey($seat->subscription_id)->lockForUpdate()->first();
            $seat->update(['assigned_user_id' => null, 'status' => 'free', 'assigned_at' => null]);
            if ($subscription !== null && $subscription->used_users > 0) {
                $subscription->decrement('used_users');
            }
        });
    }

    private function activeSubscription(bool $lock = false): ?Subscription
    {
        $query = $this->subscriptions()->where('tenant_id', $this->tenantId())
            ->whereIn('status', [SubscriptionStatus::ACTIVE->value, SubscriptionStatus::TRIAL->value])
            ->where(static fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->latest('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function seats(): Builder
    {
        return (new LicenseSeat())->setConnection($this->connection())->newQuery();
    }

    private function subscriptions(): Builder
    {
        return (new Subscription())->setConnection($this->connection())->newQuery();
    }

    private function connection(): string
    {
        $connection = (string) config('tenancy.database.central_connection');
        if ($connection === '') {
            throw new RuntimeException('The central database connection must be configured.');
        }
        return $connection;
    }

    private function tenantId(): string
    {
        $id = tenant('id');
        if ($id === null || $id === '') {
            throw new TenancyNotInitializedException();
        }
        return (string) $id;
    }
}
