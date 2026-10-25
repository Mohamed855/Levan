<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions;

use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\Properties\Property;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class AuctionReportInputService
{
    /** @param array<int|string, mixed> $ids @return list<int> */
    public function validatePropertyIds(array $ids, int $assignmentId): array
    {
        $propertyIds = array_values(array_unique(array_map('intval', $ids)));
        if ($propertyIds === []) {
            return [];
        }

        $validCount = Property::query()
            ->whereKey($propertyIds)
            ->where('assignment_id', $assignmentId)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->count();

        if ($validCount !== count($propertyIds)) {
            throw ValidationException::withMessages([
                'property_ids' => 'يجب أن تكون جميع العقارات المختارة تابعة للإسناد المختار وغير محذوفة.',
            ]);
        }

        return $propertyIds;
    }

    /**
     * @param list<int> $propertyIds
     * @param array<int|string, array<string, mixed>> $results
     * @param Collection<int, Property>|null $currentProperties
     * @return array<int, array<string, mixed>>
     */
    public function pivotData(array $propertyIds, array $results, ?Collection $currentProperties = null): array
    {
        $invalidResultIds = array_diff(array_map('intval', array_keys($results)), $propertyIds);
        if ($invalidResultIds !== []) {
            throw ValidationException::withMessages([
                'property_results' => 'نتائج العقارات يجب أن تخص العقارات المرتبطة بالمزاد فقط.',
            ]);
        }

        $currentById = $currentProperties?->keyBy('id') ?? collect();
        $syncData = [];

        foreach ($propertyIds as $index => $propertyId) {
            $result = $results[$propertyId] ?? $results[(string) $propertyId] ?? null;
            $syncData[$propertyId] = ['sort_order' => $index + 1] + ($result === null
                ? $this->retainedResult($currentById->get($propertyId))
                : $this->normalizedResult($result));
        }

        return $syncData;
    }

    /**
     * @param list<UploadedFile> $marketingEvidence
     * @param array<int|string, list<UploadedFile>> $boardImagesBefore
     * @param array<int|string, list<UploadedFile>> $boardImagesAfter
     * @param list<int> $propertyIds
     * @param list<string> $storedPaths
     */
    public function storeEvidence(
        Auction $auction,
        array $marketingEvidence,
        array $boardImagesBefore,
        array $boardImagesAfter,
        array $propertyIds,
        array &$storedPaths,
    ): void {
        foreach ($marketingEvidence as $index => $file) {
            if ($file instanceof UploadedFile) {
                $this->storeAttachment($auction, $file, 'marketing_evidence', null, $index, $storedPaths);
            }
        }

        $this->storeBoardImages($auction, $boardImagesBefore, 'board_before', $propertyIds, $storedPaths);
        $this->storeBoardImages($auction, $boardImagesAfter, 'board_after', $propertyIds, $storedPaths);
    }

    /**
     * @param array<int|string, list<UploadedFile>> $filesByProperty
     * @param list<int> $propertyIds
     * @param list<string> $storedPaths
     */
    private function storeBoardImages(Auction $auction, array $filesByProperty, string $section, array $propertyIds, array &$storedPaths): void
    {
        foreach ($filesByProperty as $propertyId => $files) {
            $propertyId = (int) $propertyId;
            if (! in_array($propertyId, $propertyIds, true)) {
                throw ValidationException::withMessages([
                    $section => 'صور اللوحات يجب أن تخص عقارًا مرتبطًا بالمزاد.',
                ]);
            }

            foreach ((array) $files as $index => $file) {
                if ($file instanceof UploadedFile) {
                    $this->storeAttachment($auction, $file, $section, $propertyId, $index, $storedPaths);
                }
            }
        }
    }

    /** @param list<string> $storedPaths */
    private function storeAttachment(Auction $auction, UploadedFile $file, string $section, ?int $propertyId, int $displayOrder, array &$storedPaths): void
    {
        $path = $file->store('auctions/report-evidence', 'tenant_public');
        $storedPaths[] = $path;

        AuctionAttachment::query()->create([
            'auction_id' => $auction->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'category' => 'marketing',
            'report_section' => $section,
            'property_id' => $propertyId,
            'display_order' => $displayOrder,
            'channels' => ['report'],
        ]);
    }

    /** @param array<string, mixed> $result @return array<string, mixed> */
    private function normalizedResult(array $result): array
    {
        $status = (string) ($result['status'] ?? '');
        if (! in_array($status, ['pending', 'sold', 'unsold'], true)) {
            throw ValidationException::withMessages(['property_results' => 'حالة نتيجة العقار غير صحيحة.']);
        }

        $boardData = array_filter([
            'location' => $result['board_location'] ?? null,
            'notes' => $result['board_notes'] ?? null,
        ], static fn (mixed $value): bool => filled($value));

        return [
            'result_status' => $status,
            'opening_price' => $result['opening_price'] ?? null,
            'sale_price' => $status === 'sold' ? ($result['sale_price'] ?? null) : null,
            'participants_count' => $result['participants_count'] ?? null,
            'bids_count' => $result['bids_count'] ?? null,
            'unsold_reason' => $status === 'unsold' ? ($result['unsold_reason'] ?? null) : null,
            'board_data' => $boardData === [] ? null : json_encode($boardData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ];
    }

    /** @return array<string, mixed> */
    private function retainedResult(?Property $property): array
    {
        $pivot = $property?->pivot;

        return [
            'result_status' => $pivot?->result_status,
            'opening_price' => $pivot?->opening_price,
            'sale_price' => $pivot?->sale_price,
            'participants_count' => $pivot?->participants_count,
            'bids_count' => $pivot?->bids_count,
            'unsold_reason' => $pivot?->unsold_reason,
            'board_data' => $pivot?->board_data,
        ];
    }
}
