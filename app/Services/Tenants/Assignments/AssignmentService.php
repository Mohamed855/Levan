<?php

namespace App\Services\Tenants\Assignments;

use App\Models\Tenants\Assignments\Assignment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;

class AssignmentService
{
    public function getStatistics(): array
    {
        return [
            'total'        => Assignment::count(),
            'active'       => Assignment::where('status', 'active')->count(),
            'under_review' => Assignment::where('status', 'under_review')->count(),
            'suspended'    => Assignment::where('status', 'suspended')->count(),
            'auctioned'    => Assignment::where('status', 'auctioned')->count(),
        ];
    }

    public function generateAssignmentNumber(): string
    {
        $year = date('Y');
        $random = strtoupper(Str::random(4));
        $number = "ES-{$year}-{$random}";

        while (Assignment::where('assignment_number', $number)->exists()) {
            $random = strtoupper(Str::random(4));
            $number = "ES-{$year}-{$random}";
        }

        return $number;
    }

    public function createAssignment(array $data, ?UploadedFile $logo = null): Assignment
    {
        if ($logo) {
            $data['entity_logo'] = $logo->store('assignments/logos', 'tenant_public');
        }

        if (empty($data['assignment_number'])) {
            $data['assignment_number'] = $this->generateAssignmentNumber();
        }

        $data['created_by'] = auth('tenant')->id();

        return Assignment::create($data);
    }

    public function updateAssignment(Assignment $assignment, array $data, ?UploadedFile $logo = null): Assignment
    {
        if ($logo) {
            if ($assignment->entity_logo && Storage::disk('tenant_public')->exists($assignment->entity_logo)) {
                Storage::disk('tenant_public')->delete($assignment->entity_logo);
            }
            $data['entity_logo'] = $logo->store('assignments/logos', 'tenant_public');
        }

        $assignment->update($data);

        return $assignment;
    }

    public function toggleStatus(Assignment $assignment, ?bool $isActive = null): bool
    {
        $newStatus = $isActive ?? ! $assignment->is_active;
        $assignment->update([
            'is_active' => $newStatus,
            'status'    => $newStatus ? 'active' : 'suspended',
        ]);

        return $newStatus;
    }

    public function deleteAssignment(Assignment $assignment): bool
    {
        if ($assignment->properties()->withTrashed()->exists() || $assignment->auctions()->withTrashed()->exists()) {
            throw new LogicException('لا يمكن نقل الإسناد إلى المحذوفات قبل معالجة العقارات والمزادات المرتبطة به.');
        }

        return (bool) $assignment->delete();
    }

    public function forceDeleteAssignment(Assignment $assignment): bool
    {
        if ($assignment->properties()->withTrashed()->exists() || $assignment->auctions()->withTrashed()->exists()) {
            throw new LogicException('لا يمكن حذف الإسناد نهائياً قبل حذف العقارات والمزادات المرتبطة به نهائياً.');
        }

        $entityLogo = $assignment->entity_logo;
        $deleted = (bool) $assignment->forceDelete();

        if ($deleted && $entityLogo) {
            Storage::disk('tenant_public')->delete($entityLogo);
        }

        return $deleted;
    }
}
