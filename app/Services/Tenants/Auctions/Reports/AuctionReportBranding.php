<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use App\Models\Tenants\Settings\Settings;
use Illuminate\Support\Facades\Storage;

final class AuctionReportBranding
{
    public function current(): array
    {
        $settings = Settings::query()->first();

        return ['company_logo' => $this->image($settings?->image), 'contacts' => ['phone' => $settings?->report_phone, 'unified_phone' => $settings?->report_unified_phone, 'social' => $settings?->report_social]];
    }

    public function apply(array $snapshot): array
    {
        $current = $this->current();
        $snapshot['branding'] = array_replace($snapshot['branding'] ?? [], ['company_logo' => $current['company_logo']]);
        $snapshot['marketing']['contacts'] = $current['contacts'];

        return $snapshot;
    }

    public function image(?string $path): ?string
    {
        if (blank($path) || ! Storage::disk('tenant_public')->exists($path)) {
            return null;
        }
        $disk = Storage::disk('tenant_public');

        return 'data:'.($disk->mimeType($path) ?: 'image/png').';base64,'.base64_encode($disk->get($path));
    }
}
