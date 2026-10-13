<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Assignments;

use App\Models\Tenants\Assignments\Assignment;
use App\Services\Tenants\Assignments\AssignmentService;

final class ForceDeleteAssignmentAction
{
    public function __construct(private readonly AssignmentService $assignmentService) {}

    public function execute(Assignment $assignment): bool
    {
        return $this->assignmentService->forceDeleteAssignment($assignment);
    }
}
