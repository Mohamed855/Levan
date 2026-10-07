<?php

namespace App\Services\Tenants\Auctions;

use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AuctionService
{
    public function getStatistics(): array
    {
        return [
            'total'            => Auction::count(),
            'active'           => Auction::where('status', 'active')->count(),
            'in_preparation'   => Auction::where('status', 'in_preparation')->orWhere('status', 'draft')->count(),
            'pending_approval' => Auction::where('status', 'pending_approval')->count(),
        ];
    }

    public function createAuction(array $data, ?UploadedFile $coverImage = null, array $attachments = []): Auction
    {
        return DB::transaction(function () use ($data, $coverImage, $attachments) {
            if ($coverImage) {
                $data['cover_image'] = $coverImage->store('auctions/covers', 'tenant_public');
            }

            $propertyIds = $data['property_ids'] ?? [];
            unset($data['property_ids'], $data['attachments']);

            $data['created_by'] = auth('tenant')->id();
            if (empty($data['status'])) {
                $data['status'] = 'draft';
            }

            $auction = Auction::create($data);

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
                    AuctionAttachment::create([
                        'auction_id' => $auction->id,
                        'file_path'  => $path,
                        'file_name'  => $file->getClientOriginalName(),
                        'file_type'  => $file->getClientOriginalExtension(),
                        'file_size'  => $file->getSize(),
                        'category'   => 'auction_doc',
                        'channels'   => ['internal'],
                    ]);
                }
            }

            return $auction;
        });
    }

    public function updateAuction(Auction $auction, array $data, ?UploadedFile $coverImage = null, array $attachments = []): Auction
    {
        return DB::transaction(function () use ($auction, $data, $coverImage, $attachments) {
            if ($coverImage) {
                if ($auction->cover_image && Storage::disk('tenant_public')->exists($auction->cover_image)) {
                    Storage::disk('tenant_public')->delete($auction->cover_image);
                }
                $data['cover_image'] = $coverImage->store('auctions/covers', 'tenant_public');
            }

            $propertyIds = $data['property_ids'] ?? null;
            unset($data['property_ids'], $data['attachments']);

            $auction->update($data);

            if ($propertyIds !== null) {
                $syncData = [];
                foreach ($propertyIds as $index => $propId) {
                    $syncData[$propId] = ['sort_order' => $index + 1];
                }
                $auction->properties()->sync($syncData);
            }

            foreach ($attachments as $file) {
                if ($file instanceof UploadedFile) {
                    $path = $file->store('auctions/attachments', 'tenant_public');
                    AuctionAttachment::create([
                        'auction_id' => $auction->id,
                        'file_path'  => $path,
                        'file_name'  => $file->getClientOriginalName(),
                        'file_type'  => $file->getClientOriginalExtension(),
                        'file_size'  => $file->getSize(),
                        'category'   => 'auction_doc',
                        'channels'   => ['internal'],
                    ]);
                }
            }

            return $auction;
        });
    }

    public function toggleStatus(Auction $auction, ?bool $isActive = null): bool
    {
        $newStatus = $isActive ?? ! $auction->is_active;
        $auction->update([
            'is_active' => $newStatus,
            'status'    => $newStatus ? 'active' : 'suspended',
        ]);

        return $newStatus;
    }

    public function deleteAuction(Auction $auction): bool
    {
        return (bool) $auction->delete();
    }

    public function forceDeleteAuction(Auction $auction): bool
    {
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
}
