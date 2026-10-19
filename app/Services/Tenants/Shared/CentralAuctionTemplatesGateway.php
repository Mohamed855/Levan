<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Facades\Tenancy;

final class CentralAuctionTemplatesGateway
{
    /**
     * Get active templates formatted for select options.
     *
     * @return array<int, array{value: int, label: string, thumbnail: string|null}>
     */
    public function activeByType(string $type): array
    {
        return Tenancy::central(function () use ($type): array {
            return AuctionTemplate::query()
                ->where('type', $type)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name', 'thumbnail_path'])
                ->map(fn ($t) => [
                    'value'     => (int) $t->id,
                    'label'     => $t->name,
                    'thumbnail' => $t->thumbnail_path
                        ? Storage::disk('central_public')->url($t->thumbnail_path)
                        : null,
                ])
                ->values()
                ->toArray();
        });
    }

    /**
     * Find active template by ID and type within central context.
     */
    public function findActive(int $id, string $type): ?AuctionTemplate
    {
        return Tenancy::central(function () use ($id, $type): ?AuctionTemplate {
            return AuctionTemplate::query()
                ->where('id', $id)
                ->where('type', $type)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->first();
        });
    }

    /**
     * Check if active template exists by ID and type within central context.
     */
    public function existsActive(int $id, string $type): bool
    {
        return Tenancy::central(function () use ($id, $type): bool {
            return AuctionTemplate::query()
                ->where('id', $id)
                ->where('type', $type)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->exists();
        });
    }
}
