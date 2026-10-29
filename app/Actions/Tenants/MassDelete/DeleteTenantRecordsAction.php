<?php

declare(strict_types=1);

namespace App\Actions\Tenants\MassDelete;

use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Assignments\AssignmentService;
use App\Services\Tenants\Auctions\AuctionService;
use App\Services\Tenants\Properties\PropertyService;
use App\Services\Tenants\UserManagement\Role\RoleService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Spatie\Permission\Models\Role;

final class DeleteTenantRecordsAction
{
    public function __construct(
        private readonly AssignmentService $assignments,
        private readonly PropertyService $properties,
        private readonly AuctionService $auctions,
        private readonly RoleService $roles,
    ) {}

    public function execute(Collection $records): int
    {
        $allowedClasses = array_column(config('tenant-mass-delete', []), 'class');
        foreach ($records as $record) {
            if (! $record instanceof Model || ! in_array($record::class, $allowedClasses, true)) {
                throw ValidationException::withMessages(['model' => 'نوع السجل غير مدعوم للحذف الجماعي للمستأجر.']);
            }
        }

        return DB::transaction(function () use ($records): int {
            $locked = $records->sort(fn (Model $first, Model $second): int => ($first::class <=> $second::class) ?: ($first->getKey() <=> $second->getKey()))
                ->map(fn (Model $record): Model => $record->newQuery()->whereKey($record->getKey())->lockForUpdate()->firstOrFail());

            return $locked->sum(fn (Model $record): int => (int) match (true) {
                $record instanceof Assignment => $this->assignments->deleteAssignment($record),
                $record instanceof Property => $this->properties->deleteProperty($record),
                $record instanceof Auction => $this->auctions->deleteAuction($record),
                $record instanceof User => $this->deleteUser($record),
                $record instanceof Role => $this->deleteRole($record),
            });
        });
    }

    private function deleteUser(User $user): bool
    {
        if ((int) $user->getKey() === 1) {
            throw new LogicException('لا يمكن حذف المستخدم الأساسي.');
        }

        if ((int) auth('tenant')->id() === (int) $user->getKey()) {
            throw new LogicException('لا يمكنك حذف حسابك الشخصي.');
        }

        // Preserve the image with the soft-deleted account so it remains restorable.
        return (bool) $user->delete();
    }

    private function deleteRole(Role $role): bool
    {
        if ($role->guard_name !== 'tenant' || ! $this->roles->canDeleteRole($role)) {
            throw new LogicException('لا يمكن حذف هذا الدور.');
        }

        return $this->roles->deleteRole($role);
    }
}
