<?php

namespace App\Services\Tenants\Assignments;

use App\Enums\Tenants\Assignments\AssignmentAttachmentCategory;
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
            'total' => Assignment::count(),
            'active' => Assignment::where('status', 'active')->count(),
            'under_review' => Assignment::where('status', 'under_review')->count(),
            'suspended' => Assignment::where('status', 'suspended')->count(),
            'auctioned' => Assignment::where('status', 'auctioned')->count(),
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

    public function updateAssignment(Assignment $assignment, array $data, ?UploadedFile $logo = null): Assignment
    {
        if ($logo) {
            $currentImage = $assignment->assignmentImage;
            if ($currentImage) {
                Storage::disk($currentImage->disk)->delete($currentImage->file_path);
                $currentImage->delete();
            }
            $path = $logo->store('assignments/attachments', 'tenant_public');
            $assignment->attachments()->create([
                'disk' => 'tenant_public',
                'file_path' => $path,
                'original_name' => $logo->getClientOriginalName(),
                'mime_type' => $logo->getClientMimeType(),
                'extension' => $logo->getClientOriginalExtension(),
                'file_size' => $logo->getSize(),
                'category' => AssignmentAttachmentCategory::IMAGE->value,
            ]);
        }

        unset($data['assignment_image']);
        $assignment->update($data);

        return $assignment;
    }

    public function toggleStatus(Assignment $assignment, ?bool $isActive = null): bool
    {
        $newStatus = $isActive ?? ! $assignment->is_active;
        $assignment->update([
            'is_active' => $newStatus,
            'status' => $newStatus ? 'active' : 'suspended',
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

        $attachments = $assignment->attachments()->get(['id', 'disk', 'file_path']);
        $entityLogo = $assignment->entity_logo;
        $deleted = (bool) $assignment->forceDelete();

        if ($deleted) {
            foreach ($attachments as $attachment) {
                Storage::disk($attachment->disk)->delete($attachment->file_path);
            }
            $assignment->attachments()->delete();
            if ($entityLogo) {
                Storage::disk('tenant_public')->delete($entityLogo);
            }
        }

        return $deleted;
    }
}
