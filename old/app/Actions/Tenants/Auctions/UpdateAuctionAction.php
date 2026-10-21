<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Auctions;

use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class UpdateAuctionAction
{
    public function __construct(
        private readonly CreateAuctionAction $createAuctionAction
    ) {}

    /**
     * @param array<string, mixed>|User $userOrData
     * @param array<string, mixed>|null $dataOrNull
     * @param array<int, UploadedFile> $attachments
     */
    public function execute(
        Auction $auction,
        mixed $userOrData,
        mixed $dataOrNull = null,
        ?UploadedFile $coverImage = null,
        array $attachments = []
    ): Auction {
        if ($userOrData instanceof User) {
            $data = (array) $dataOrNull;
        } else {
            $data = (array) $userOrData;
        }

        $storedPaths = [];
        $oldCoverToDelete = null;

        try {
            return DB::transaction(function () use ($auction, $data, $coverImage, $attachments, &$storedPaths, &$oldCoverToDelete): Auction {
                $targetAssignmentId = (int) ($data['assignment_id'] ?? $auction->assignment_id);
                $assignment = Assignment::query()
                    ->whereKey($targetAssignmentId)
                    ->lockForUpdate()
                    ->first();

                if (! $assignment) {
                    throw ValidationException::withMessages([
                        'assignment_id' => 'الإسناد المختار غير موجود.',
                    ]);
                }

                $propertyIds = isset($data['property_ids'])
                    ? array_values(array_unique(array_map('intval', (array) $data['property_ids'])))
                    : null;

                if ($propertyIds !== null) {
                    if (! empty($propertyIds)) {
                        $validPropertyCount = Property::query()
                            ->whereKey($propertyIds)
                            ->where('assignment_id', $targetAssignmentId)
                            ->whereNull('deleted_at')
                            ->lockForUpdate()
                            ->count();

                        if ($validPropertyCount !== count($propertyIds)) {
                            throw ValidationException::withMessages([
                                'property_ids' => 'يجب أن تكون جميع العقارات المختارة تابعة للإسناد المختار وغير محذوفة.',
                            ]);
                        }
                    }

                    $syncData = [];
                    foreach ($propertyIds as $index => $propId) {
                        $syncData[$propId] = ['sort_order' => $index + 1];
                    }
                    $auction->properties()->sync($syncData);
                } elseif ($auction->assignment_id !== $targetAssignmentId) {
                    $auction->properties()
                        ->where('assignment_id', '!=', $targetAssignmentId)
                        ->detach();
                }

                if (array_key_exists('template_versions', $data)) {
                    [$versions, $snapshots] = $this->createAuctionAction->snapshots((array) ($data['template_versions'] ?? []));
                    $data['template_versions'] = $versions;
                    $data['template_snapshot'] = $snapshots;
                }

                if ($coverImage instanceof UploadedFile) {
                    $coverPath = $coverImage->store('auctions/covers', 'tenant_public');
                    $storedPaths[] = $coverPath;
                    $data['cover_image'] = $coverPath;
                    if ($auction->cover_image) {
                        $oldCoverToDelete = $auction->cover_image;
                    }
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

                if (isset($data['status'])) {
                    $statusValue = is_object($data['status']) ? $data['status']->value : (string) $data['status'];
                    $data['is_active'] = $statusValue === AuctionStatus::ACTIVE->value;
                }

                $auction->update($data);

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

                return $auction->fresh(['properties', 'attachments', 'assignment']);
            });
        } catch (Throwable $e) {
            if (! empty($storedPaths)) {
                Storage::disk('tenant_public')->delete($storedPaths);
            }
            throw $e;
        } finally {
            if ($oldCoverToDelete && Storage::disk('tenant_public')->exists($oldCoverToDelete)) {
                Storage::disk('tenant_public')->delete($oldCoverToDelete);
            }
        }
    }
}
