<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Assignment;
use App\Models\Tenant\User;

final class AssignmentPolicy
{
    public function view(User $user, Assignment $assignment): bool { return $user->hasPermissionTo('assignments.view'); }
    public function create(User $user): bool { return $user->hasPermissionTo('assignments.create'); }
    public function update(User $user, Assignment $assignment): bool { return $user->hasPermissionTo('assignments.edit'); }
    public function delete(User $user, Assignment $assignment): bool { return $user->hasPermissionTo('assignments.edit'); }
}
