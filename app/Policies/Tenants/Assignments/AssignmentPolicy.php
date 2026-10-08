<?php

declare(strict_types=1);

namespace App\Policies\Tenants\Assignments;

use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\UserMangment\User\User;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('assignments.view');
    }

    public function view(User $user, Assignment $assignment): bool
    {
        return $user->can('assignments.view') && tenancy()->initialized;
    }

    public function create(User $user): bool
    {
        return $user->can('assignments.create');
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->can('assignments.edit') && tenancy()->initialized;
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->can('assignments.delete') && tenancy()->initialized;
    }

    public function restore(User $user, Assignment $assignment): bool
    {
        return $user->can('assignments_archive.restore') && tenancy()->initialized;
    }

    public function forceDelete(User $user, Assignment $assignment): bool
    {
        return $user->can('assignments_archive.force_delete') && tenancy()->initialized;
    }
}
