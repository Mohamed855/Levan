<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Assignments;

use App\Models\Tenants\Assignments\Assignment;

final class RestoreAssignmentAction
{
    public function execute(Assignment $assignment): Assignment
    {
        $assignment->restore();

        return $assignment->refresh();
    }
}
