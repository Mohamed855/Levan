<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Auctions;

use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\Properties\Property;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CreateAuctionAction
{
    /**
     * @param array<string, mixed> $data
     * @param array<int, UploadedFile> $attachments
     */
    public function execute(
        array $data,
        ?int $creatorId = null,
        ?UploadedFile $coverImage = null,
        array $attachments = []
    ): Auction {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($data, $creatorId, $coverImage, $attachments, &$storedPaths): Auction {
                $assignmentId = (int) ($data['assignment_id'] ?? 0);
                $assignment = Assignment::query()
                    ->whereKey($assignmentId)
                    ->lockForUpdate()
                    ->first();

                if (! $assignment) {
                    throw ValidationException::withMessages([
                        'assignment_id' => 'الإسناد المختار غير موجود.',
                    ]);
                }

                $propertyIds = array_values(array_unique(array_map('intval', (array) ($data['property_ids'] ?? []))));
                if (! empty($propertyIds)) {
                    $validPropertyCount = Property::query()
                        ->whereKey($propertyIds)
                        ->where('assignment_id', $assignmentId)
                        ->whereNull('deleted_at')
                        ->lockForUpdate()
                        ->count();

                    if ($validPropertyCount !== count($propertyIds)) {
                        throw ValidationException::withMessages([
                            'property_ids' => 'يجب أن تكون جميع العقارات المختارة تابعة للإسناد المختار وغير محذوفة.',
                        ]);
                    }
                }

                if ($coverImage instanceof UploadedFile) {
                    $coverPath = $coverImage->store('auctions/covers', 'tenant_public');
                    $storedPaths[] = $coverPath;
                    $data['cover_image'] = $coverPath;
                }

                unset(
                    $data['property_ids'],
                    $data['attachments'],
                    $data['_wizard_draft_id'],
                    $data['_wizard_resource_key'],
                    $data['_wizard_step'],
                    $data['fields'],
                    $data['target_path']
                );

                $data['created_by'] = $creatorId ?? (int) auth('tenant')->id();
                $data['status'] = AuctionStatus::PENDING_APPROVAL->value;
                $statusValue = $data['status'];
                $data['is_active'] = $statusValue === AuctionStatus::ACTIVE->value;

                $auction = Auction::query()->create($data);

                if (! empty($propertyIds)) {
                    $syncData = [];
                    foreach ($propertyIds as $index => $propId) {
                        $syncData[$propId] = ['sort_order' => $index + 1];
                    }
                    $auction->properties()->sync($syncData);
                }

                foreach ($attachments as $file) {
                    if ($file instanceof UploadedFile) {
                        $path = $file->store('auctions/attachments', 'tenant_public');
                        $storedPaths[] = $path;
                        AuctionAttachment::query()->create([
                            'auction_id' => $auction->id,
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                            'file_type' => $file->getClientOriginalExtension(),
                            'file_size' => $file->getSize(),
                            'category' => 'auction_doc',
                            'channels' => ['internal'],
                        ]);
                    }
                }

                return $auction->load(['properties', 'attachments', 'assignment']);
            });
        } catch (Throwable $e) {
            Storage::disk('tenant_public')->delete($storedPaths);
            throw $e;
        }
    }
}
