<?php

declare(strict_types=1);

namespace App\Actions\Admin\MassDelete;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Services\Admin\AdminMangment\Role\RoleService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;
use Spatie\Permission\Models\Role;

final class DeleteAdminRecordsAction
{
    public function __construct(
        private readonly ?RoleService $roleService = null,
    ) {}

    public function execute(Collection $records): int
    {
        $roleService = $this->roleService ?? app(RoleService::class);

        return DB::connection('mysql')->transaction(function () use ($records, $roleService): int {
            return $records->sum(function ($record) use ($roleService): int {
                if ($record instanceof Role) {
                    return (int) $roleService->deleteRole($record);
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
