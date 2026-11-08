<?php

namespace App\Services\Tenants\Auctions;

use App\Actions\Tenants\Auctions\CreateAuctionAction;
use App\Actions\Tenants\Auctions\UpdateAuctionAction;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Auctions\Reports\AuctionReportLock;
use App\Services\Tenants\Auctions\Reports\AuctionReportRefreshService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuctionService
{
    public function getStatistics(): array
    {
        return [
            'total' => Auction::count(),
            'active' => Auction::activeStatus()->count(),
            'closed' => Auction::closed()->count(),
            'suspended' => Auction::suspended()->count(),
            'upcoming' => Auction::upcoming()->count(),
            'inactive' => Auction::suspended()->count(),
            'without_properties' => Auction::doesntHave('properties')->count(),
            'ready_reports' => Auction::whereHas('reportGenerations', fn ($q) => $q->where('status', 'ready'))->count(),
        ];
    }

    public function createAuction(array $data, ?UploadedFile $coverImage = null, array $attachments = []): Auction
    {
        return app(CreateAuctionAction::class)->execute(
            $data,
            auth('tenant')->id() ? (int) auth('tenant')->id() : null,
            $coverImage,
            $attachments
        );
    }

    public function updateAuction(Auction $auction, array $data, ?UploadedFile $coverImage = null, array $attachments = []): Auction
    {
        return app(UpdateAuctionAction::class)->execute(
            $auction,
            $data,
            $coverImage,
            $attachments
        );
    }

    public function toggleStatus(Auction $auction, ?bool $isActive = null): bool
    {
        return DB::transaction(function () use ($auction, $isActive): bool {
            $auction = Auction::query()->whereKey($auction->id)->lockForUpdate()->firstOrFail();
            app(AuctionReportLock::class)->assertEditable($auction);
            $newStatus = $isActive ?? ! $auction->is_active;
            $auction->update(['is_active' => $newStatus]);
            app(AuctionReportRefreshService::class)->afterMutation([$auction->id]);

            return $newStatus;
        });
    }

    public function deleteAuction(Auction $auction): bool
    {
        return DB::transaction(function () use ($auction): bool {
            $auction = Auction::query()->whereKey($auction->id)->lockForUpdate()->firstOrFail();
            app(AuctionReportLock::class)->assertEditable($auction);

            return (bool) $auction->delete();
        });
    }

    public function forceDeleteAuction(Auction $auction): bool
    {
        app(AuctionReportLock::class)->assertEditable($auction);
        $attachmentPaths = $auction->attachments()->pluck('file_path')->filter()->all();

        $coverImage = $auction->cover_image;
        $deleted = (bool) $auction->forceDelete();

        if (! $deleted) {
            return false;
        }

        if ($coverImage) {
            Storage::disk('tenant_public')->delete($coverImage);
        }

        foreach ($attachmentPaths as $attachmentPath) {
            Storage::disk('tenant_public')->delete($attachmentPath);
        }

        return true;
    }

    /** @param array<int, int|string> $propertyIds */
    private function assertPropertiesBelongToAssignment(array $propertyIds, int $assignmentId): void
    {
        $propertyIds = array_values(array_unique(array_map('intval', $propertyIds)));

        if ($propertyIds === []) {
            return;
        }

        $validPropertyCount = Property::query()
            ->whereKey($propertyIds)
            ->where('assignment_id', $assignmentId)
            ->lockForUpdate()
            ->count();

        if ($validPropertyCount !== count($propertyIds)) {
            throw ValidationException::withMessages([
                'property_ids' => 'يجب أن تكون جميع العقارات المختارة تابعة للإسناد المختار.',
            ]);
        }
    }
}
