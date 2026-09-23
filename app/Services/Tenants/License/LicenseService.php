<?php

namespace App\Services\Tenants\License;

use App\Models\Tenants\UserMangment\User\User;
use App\Models\Admin\Licenses\LicenseSeat;
use App\Models\Admin\Subscription\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;
use DomainException;
use Exception;

class LicenseService
{
    /*
    |============================================================================
    |                         Public Methods
    |============================================================================
    */

    public function assignLicenseToUser(string $userId): array
    {
        $user = User::findOrFail($userId);
        $this->validateUserForAssignment($user);

        $tenantId = tenant('id');

        try {
            $seat = Tenancy::central(
                fn() => $this->assignInCentral($tenantId, $user->global_uid)
            );

            $user->update(['license_seat_id' => $seat->id]);

            return [
                'success' => true,
                'message' => 'تم تخصيص الترخيص بنجاح',
                'data'    => [
                    'seat_id'     => $seat->id,
                    'user_id'     => $user->id,
                    'user_name'   => $user->name,
                    'assigned_at' => $seat->assigned_at?->format('Y-m-d H:i:s'),
                ],
            ];
        } catch (DomainException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('فشل تخصيص الترخيص', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
            throw new Exception('حدث خطأ غير متوقع أثناء تخصيص الترخيص');
        }
    }


    public function releaseLicenseFromUser(string $userId): array
    {
        $user = User::findOrFail($userId);
        $this->validateUserForRelease($user);

        $tenantId = tenant('id');

        try {
            return DB::transaction(function () use ($user, $tenantId) {

                // tenant أولاً — أي فشل في المركزية يُلغيه تلقائياً
                $user->update(['license_seat_id' => null]);

                Tenancy::central(
                    fn() => $this->releaseInCentral($tenantId, $user->global_uid)
                );

                return [
                    'success' => true,
                    'message' => 'تم تحرير الترخيص بنجاح',
                    'data'    => [
                        'user_id'   => $user->id,
                        'user_name' => $user->name,
                    ],
                ];
            });
        } catch (DomainException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('فشل تحرير الترخيص', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
            throw new Exception('حدث خطأ غير متوقع أثناء تحرير الترخيص');
        }
    }


    /*
    |============================================================================
    |                    Private — Assignment Methods
    |============================================================================
    */

    private function validateUserForAssignment(User $user): void
    {
        if (empty($user->global_uid)) {
            throw new DomainException('المستخدم لا يملك معرّف فريد (Global UID)');
        }

        if ($user->license_seat_id) {
            throw new DomainException('المستخدم يملك ترخيصًا بالفعل');
        }
    }


    private function assignInCentral(string $tenantId, string $userUid): LicenseSeat
    {
        return DB::connection('mysql')->transaction(function () use ($tenantId, $userUid) {

            $existingSeat = LicenseSeat::where('tenant_id', $tenantId)
                ->where('assigned_user_uid', $userUid)
                ->where('status', 'assigned')
                ->where('is_core', false)
                ->lockForUpdate()
                ->first();

            if ($existingSeat) {
                return $existingSeat;
            }

            // التحقق من أنه ليس المستخدم الأساسي
            $this->validateNotCoreLicense($tenantId, $userUid);

            // جلب الاشتراك والتحقق من توفر المقاعد
            $subscription = $this->getActiveSubscription($tenantId);
            $this->validateSeatsAvailable($subscription);

            // الحصول على مقعد حر أو إنشاء جديد
            $seat = $this->getOrCreateFreeSeat($tenantId, $subscription->id);

            $seat->update([
                'assigned_user_uid' => $userUid,
                'status'            => 'assigned',
                'assigned_at'       => now(),
            ]);

            $subscription->increment('used_users');

            return $seat->fresh();
        });
    }


    private function validateNotCoreLicense(string $tenantId, string $userUid): void
    {
        $exists = LicenseSeat::where('tenant_id', $tenantId)
            ->where('assigned_user_uid', $userUid)
            ->where('is_core', true)
            ->exists();

        if ($exists) {
            throw new DomainException('لا يمكن تعديل الترخيص الأساسي للمستخدم الإداري');
        }
    }


    private function getActiveSubscription(string $tenantId): Subscription
    {
        $subscription = Subscription::where('tenant_id', $tenantId)
            ->lockForUpdate()
            ->first();

        if (!$subscription) {
            throw new DomainException('لا يوجد اشتراك نشط');
        }

        return $subscription;
    }


    private function validateSeatsAvailable(Subscription $subscription): void
    {
        if ($subscription->used_users >= $subscription->total_users) {
            throw new DomainException('لا توجد تراخيص متاحة في الاشتراك الحالي');
        }
    }


    private function getOrCreateFreeSeat(string $tenantId, int $subscriptionId): LicenseSeat
    {
        $seat = LicenseSeat::where('tenant_id', $tenantId)
            ->where('subscription_id', $subscriptionId)
            ->where('status', 'free')
            ->lockForUpdate()
            ->first();

        if (!$seat) {
            $seat = LicenseSeat::create([
                'tenant_id'       => $tenantId,
                'subscription_id' => $subscriptionId,
                'status'          => 'free',
            ]);
        }

        return $seat;
    }


    /*
    |============================================================================
    |                    Private — Release Methods
    |============================================================================
    */

    private function validateUserForRelease(User $user): void
    {
        if (empty($user->global_uid)) {
            throw new DomainException('المستخدم لا يملك معرّف فريد (Global UID)');
        }

        if (!$user->license_seat_id) {
            throw new DomainException('المستخدم لا يملك ترخيصًا مُخصصًا');
        }
    }


    private function releaseInCentral(string $tenantId, string $userUid): void
    {
        DB::connection('mysql')->transaction(function () use ($tenantId, $userUid) {

            $seat = $this->findAssignedSeat($tenantId, $userUid);

            if ($seat->is_core) {
                throw new DomainException('لا يمكن تحرير الترخيص الأساسي للمستخدم الإداري');
            }

            $this->validate30DaysRule($seat);

            $seat->update([
                'assigned_user_uid' => null,
                'status'            => 'free',
                'assigned_at'       => null,
            ]);

            Subscription::where('id', $seat->subscription_id)
                ->lockForUpdate()
                ->decrement('used_users');
        });
    }


    private function findAssignedSeat(string $tenantId, string $userUid): LicenseSeat
    {
        $seat = LicenseSeat::where('tenant_id', $tenantId)
            ->where('assigned_user_uid', $userUid)
            ->where('status', 'assigned')
            ->lockForUpdate()
            ->first();

        if (!$seat) {
            throw new DomainException('المستخدم لا يملك ترخيصًا مخصصًا');
        }

        return $seat;
    }


    private function validate30DaysRule(LicenseSeat $seat): void
    {
        if (!$seat->assigned_at) {
            return;
        }

        $daysSinceAssigned = $seat->assigned_at->diffInDays(now());

        if ($daysSinceAssigned < 30) {
            $daysRemaining = 30 - $daysSinceAssigned;
            $releaseDate   = $seat->assigned_at->copy()->addDays(30);

            throw new DomainException(
                "لا يمكن تحرير الترخيص إلا بعد مرور 30 يومًا من تاريخ الإسناد. " .
                    "المتبقي: {$daysRemaining} يوم " .
                    "(يمكن التحرير بتاريخ: {$releaseDate->format('Y-m-d')})"
            );
        }
    }
}
