<?php

declare(strict_types=1);

namespace App\Actions\Admin\MassDelete;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Subscription\Subscription;
use App\Services\Admin\AdminMangment\Role\RoleService;
use App\Services\Admin\Subscriptions\AdminSubscriptionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;
use Spatie\Permission\Models\Role;

final class DeleteAdminRecordsAction
{
    public function __construct(
        private readonly ?RoleService $roleService = null,
        private readonly ?AdminSubscriptionService $subscriptionService = null,
    ) {}

    public function execute(Collection $records): int
    {
        $roleService = $this->roleService ?? app(RoleService::class);
        $subscriptionService = $this->subscriptionService ?? app(AdminSubscriptionService::class);

        return DB::connection('mysql')->transaction(function () use ($records, $roleService, $subscriptionService): int {
            return $records->sum(function ($record) use ($roleService, $subscriptionService): int {
                if ($record instanceof Role) {
                    return (int) $roleService->deleteRole($record);
                }

                if ($record instanceof Subscription) {
                    return (int) $subscriptionService->deleteSubscription($record);
                }

                if ($record instanceof AdminUser) {
                    if ($record->isSuperAdmin()) {
                        throw new LogicException('لا يمكن حذف السوبر أدمن.');
                    }
                    if ((int) auth('admin')->id() === (int) $record->getKey()) {
                        throw new LogicException('لا يمكنك حذف حسابك الشخصي.');
                    }

                    return (int) $record->delete();
                }

                return (int) $record->delete();
            });
        });
    }
}
