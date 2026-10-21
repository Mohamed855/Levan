<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Models\Admin\AuctionTemplates\AuctionTemplateVersion;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Facades\Tenancy;

final class CentralAuctionTemplatesGateway
{
    /**
     * Get published template versions formatted for select options.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function publishedByType(string $type): array
    {
        return Tenancy::central(function () use ($type): array {
            return AuctionTemplateVersion::query()
                ->with('template:id,name,type,is_active,status')
                ->where('status', 'published')
                ->whereHas('template', fn ($query) => $query->published()->where('type', $type))
                ->latest('published_at')
                ->get()
                ->map(fn ($v) => [
                    'value' => (int) $v->id,
                    'label' => "{$v->template->name} — إصدار {$v->version}",
                ])
                ->values()
                ->toArray();
        });
    }

    /**
     * Find published template version by version ID and template type within central context.
     */
    public function findPublished(int $versionId, string $type): ?AuctionTemplateVersion
    {
        return Tenancy::central(function () use ($versionId, $type): ?AuctionTemplateVersion {
            return AuctionTemplateVersion::query()
                ->with('template:id,name,type,is_active,status')
                ->whereKey($versionId)
                ->where('status', 'published')
                ->whereHas('template', fn ($query) => $query->published()->where('type', $type))
                ->first();
        });
    }

    /**
     * Check if published template version exists by version ID and template type.
     */
    public function existsPublished(int $versionId, string $type): bool
    {
        return $this->findPublished($versionId, $type) !== null;
    }

    /**
     * Get active templates formatted for select options.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function activeByType(string $type): array
    {
        return Tenancy::central(function () use ($type): array {
            return AuctionTemplate::query()
                ->where('type', $type)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($t) => [
                    'value' => (int) $t->id,
                    'label' => $t->name,
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
