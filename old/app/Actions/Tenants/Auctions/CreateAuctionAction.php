<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Auctions;

use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User;
use App\Services\Tenants\Shared\CentralAuctionTemplatesGateway;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CreateAuctionAction
{
    public function __construct(
        private readonly CentralAuctionTemplatesGateway $templates
    ) {}

    /**
     * @param array<string, mixed>|User $userOrData
     * @param array<string, mixed>|int|null $dataOrCreatorId
     * @param array<int, UploadedFile> $attachments
     */
    public function execute(
        mixed $userOrData,
        mixed $dataOrCreatorId = null,
        ?UploadedFile $coverImage = null,
        array $attachments = []
    ): Auction {
        if ($userOrData instanceof User) {
            $user = $userOrData;
            $data = (array) $dataOrCreatorId;
            $creatorId = $user->id;
        } else {
            $data = (array) $userOrData;
            $creatorId = is_int($dataOrCreatorId) ? $dataOrCreatorId : null;
        }

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

                // Process template snapshots
                [$versions, $snapshots] = $this->snapshots((array) ($data['template_versions'] ?? []));
                $data['template_versions'] = $versions;
                $data['template_snapshot'] = $snapshots;

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

                $creator = $creatorId ?? auth('tenant')->id() ?? auth()->id();
                $data['created_by'] = $creator ? (int) $creator : null;
                if (! isset($data['status'])) {
                    $data['status'] = AuctionStatus::PENDING_APPROVAL->value;
                }
                $statusValue = is_object($data['status']) ? $data['status']->value : (string) $data['status'];
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
            if (! empty($storedPaths)) {
                Storage::disk('tenant_public')->delete($storedPaths);
            }
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $selected
     * @return array{array<string, int>, array<string, array<string, mixed>>}
     */
    public function snapshots(array $selected): array
    {
        $versions = [];
        $snapshots = [];

        foreach ($selected as $type => $versionId) {
            if (blank($versionId)) {
                continue;
            }

            $version = $this->templates->findPublished((int) $versionId, (string) $type);
            if (! $version) {
                throw ValidationException::withMessages([
                    'template_versions.'.$type => 'نسخة القالب غير متاحة للنشر.',
                ]);
            }

            $versions[$type] = $version->id;
            $snapshots[$type] = [
                'template_id' => $version->auction_template_id,
                'template_name' => $version->template->name,
                'type' => (string) $type,
                'version' => $version->version,
                'schema' => $version->schema,
            ];
        }

        return [$versions, $snapshots];
    }
}
