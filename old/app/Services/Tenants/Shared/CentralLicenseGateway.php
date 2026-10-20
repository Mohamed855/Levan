<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Licenses\LicenseSeat;
use App\Models\Admin\Subscription\Subscription;
use DomainException;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;

final class CentralLicenseGateway
{
    /** @return array{id:int,assigned_at:?string} */
    public function assign(string $tenantId, string $userUid): array
    {
        return Tenancy::central(fn (): array => DB::connection('mysql')->transaction(function () use ($tenantId, $userUid): array {
            $existing = LicenseSeat::query()
                ->where('tenant_id', $tenantId)
                ->where('assigned_user_uid', $userUid)
                ->where('status', 'assigned')
                ->where('is_core', false)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $this->seatData($existing);
            }

            if (LicenseSeat::query()->where('tenant_id', $tenantId)->where('assigned_user_uid', $userUid)->where('is_core', true)->exists()) {
                throw new DomainException('لا يمكن تعديل الترخيص الأساسي للمستخدم الإداري');
            }

            $subscription = Subscription::query()
                ->where('tenant_id', $tenantId)
                ->where('status', SubscriptionStatus::ACTIVE)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($subscription === null) {
                throw new DomainException('لا يوجد اشتراك نشط');
            }
            if ($subscription->used_users >= $subscription->total_users) {
                throw new DomainException('لا توجد تراخيص متاحة في الاشتراك الحالي');
            }

            $seat = LicenseSeat::query()
                ->where('tenant_id', $tenantId)
                ->where('subscription_id', $subscription->id)
                ->where('status', 'free')
                ->lockForUpdate()
                ->first() ?? LicenseSeat::query()->create([
                    'tenant_id' => $tenantId,
                    'subscription_id' => $subscription->id,
                    'status' => 'free',
                ]);

            $seat->update([
                'assigned_user_uid' => $userUid,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);
            $subscription->increment('used_users');

            return $this->seatData($seat->fresh());
        }));
    }

    public function release(string $tenantId, string $userUid, bool $enforceWaitingPeriod = true): void
    {
        Tenancy::central(fn () => DB::connection('mysql')->transaction(function () use ($tenantId, $userUid, $enforceWaitingPeriod): void {
            $seat = LicenseSeat::query()
                ->where('tenant_id', $tenantId)
                ->where('assigned_user_uid', $userUid)
                ->where('status', 'assigned')
                ->lockForUpdate()
                ->first();

            if ($seat === null) {
                throw new DomainException('المستخدم لا يملك ترخيصًا مخصصًا');
            }
            if ($seat->is_core) {
                throw new DomainException('لا يمكن تحرير الترخيص الأساسي للمستخدم الإداري');
            }

            if ($enforceWaitingPeriod && $seat->assigned_at && $seat->assigned_at->diffInDays(now()) < 30) {
                $daysRemaining = 30 - $seat->assigned_at->diffInDays(now());
                $releaseDate = $seat->assigned_at->copy()->addDays(30)->format('Y-m-d');
                throw new DomainException("لا يمكن تحرير الترخيص إلا بعد مرور 30 يومًا من تاريخ الإسناد. المتبقي: {$daysRemaining} يوم (يمكن التحرير بتاريخ: {$releaseDate})");
            }

            $subscriptionId = (int) $seat->subscription_id;
            $seat->update(['assigned_user_uid' => null, 'status' => 'free', 'assigned_at' => null]);
            Subscription::query()->whereKey($subscriptionId)->lockForUpdate()->decrement('used_users');
        }));
    }

    /** @return array{id:int,assigned_at:?string} */
    private function seatData(LicenseSeat $seat): array
    {
        return [
            'id' => (int) $seat->id,
            'assigned_at' => $seat->assigned_at?->format('Y-m-d H:i:s'),
        ];
    }
}
