<?php

declare(strict_types=1);

namespace App\Services\Tenants\License;

use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Shared\CentralLicenseGateway;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class LicenseService
{
    public function __construct(private readonly CentralLicenseGateway $licenseGateway) {}

    public function assignLicenseToUser(string $userId): array
    {
        $user = User::query()->findOrFail($userId);
        $this->validateUserForAssignment($user);
        $tenantId = (string) tenant('id');
        $seat = null;

        try {
            $seat = $this->licenseGateway->assign($tenantId, $user->global_uid);

            return [
                'success' => true,
                'message' => 'تم تخصيص الترخيص بنجاح',
                'data' => [
                    'seat_id' => $seat['id'],
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'assigned_at' => $seat['assigned_at'],
                ],
            ];
        } catch (DomainException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            if ($seat !== null) {
                rescue(fn () => $this->licenseGateway->release($tenantId, $user->global_uid, false), report: true);
            }

            Log::error('فشل تخصيص الترخيص', ['user_id' => $userId, 'error' => $exception->getMessage()]);
            throw new \RuntimeException('حدث خطأ غير متوقع أثناء تخصيص الترخيص', previous: $exception);
        }
    }

    public function releaseLicenseFromUser(string $userId): array
    {
        $user = User::query()->findOrFail($userId);
        $this->validateUserForRelease($user);
        $tenantId = (string) tenant('id');
        $released = false;

        try {
            return DB::transaction(function () use ($user, $tenantId, &$released): array {
                $this->licenseGateway->release($tenantId, $user->global_uid);
                $released = true;

                return [
                    'success' => true,
                    'message' => 'تم تحرير الترخيص بنجاح',
                    'data' => ['user_id' => $user->id, 'user_name' => $user->name],
                ];
            });
        } catch (DomainException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            if ($released) {
                rescue(fn () => $this->licenseGateway->assign($tenantId, $user->global_uid), report: true);
            }

            Log::error('فشل تحرير الترخيص', ['user_id' => $userId, 'error' => $exception->getMessage()]);
            throw new \RuntimeException('حدث خطأ غير متوقع أثناء تحرير الترخيص', previous: $exception);
        }
    }

    private function validateUserForAssignment(User $user): void
    {
        if (empty($user->global_uid)) {
            throw new DomainException('المستخدم لا يملك معرّف فريد (Global UID)');
        }
    }

    private function validateUserForRelease(User $user): void
    {
        if (empty($user->global_uid)) {
            throw new DomainException('المستخدم لا يملك معرّف فريد (Global UID)');
        }
    }
}
