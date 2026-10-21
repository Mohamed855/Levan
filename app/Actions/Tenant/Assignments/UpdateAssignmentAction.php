<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Assignments;

use App\Models\Tenant\Assignment;
use App\Services\Tenants\Assignments\AssignmentService;
use Illuminate\Http\UploadedFile;

final class UpdateAssignmentAction
{
    public function __construct(private readonly AssignmentService $assignmentService) {}

    public function execute(Assignment $assignment, array $data, ?UploadedFile $image = null): Assignment
    {
        return $this->assignmentService->updateAssignment($assignment, $data, $image);
    }
}
