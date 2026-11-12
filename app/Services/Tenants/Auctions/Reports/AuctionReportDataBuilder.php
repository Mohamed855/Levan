<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use App\Models\Tenants\Auctions\Auction;
use Illuminate\Support\Facades\Storage;

final class AuctionReportDataBuilder
{
    /** @return array<string, mixed> */
    public function build(Auction $auction): array
    {
        $auction->loadMissing([
            'assignment.issuingEntity',
            'platformEntity',
            'marketingCampaigns',
            'closing',
            'establishmentRequest',
            'properties.galleryAttachments',
            'attachments' => fn ($query) => $query->orderBy('report_section')->orderBy('display_order')->orderBy('id'),
        ]);

        $properties = $auction->properties->map(function ($property) use ($auction): array {
            $pivot = $property->pivot;
            $boardData = $pivot?->board_data;
            if (is_string($boardData)) {
                $boardData = json_decode($boardData, true) ?: [];
            }
            $boards = $auction->marketingCampaigns->where('channel', 'board_before')->filter(fn ($campaign) => (int) ($campaign->metrics['property_id'] ?? 0) === (int) $property->id);
            foreach ($boards as $board) {
                foreach (['board_count' => 'count', 'board_dimensions' => 'dimensions'] as $field => $key) {
                    if (array_key_exists($field, $board->metrics ?? [])) {
                        $boardData[$key] = $board->metrics[$field];
                    }
                }
            }

            $images = collect([$property->main_image ? ['disk' => 'tenant_public', 'path' => $property->main_image] : null])
                ->merge($property->galleryAttachments->map(fn ($attachment): array => [
                    'disk' => $attachment->disk ?: 'tenant_public',
                    'path' => $attachment->file_path,
                ]))
                ->filter()
                ->map(fn (array $image): ?string => $this->dataUri($image['path'], $image['disk']))
                ->filter()
                ->values()
                ->all();

            return [
                'id' => (int) $property->id,
                'number' => (string) $property->property_number,
                'name' => (string) $property->name,
                'type' => (string) $property->type_label,
                'city' => (string) ($property->city ?: '-'),
                'district' => (string) ($property->district ?: '-'),
                'area' => $property->area !== null ? (float) $property->area : null,
                'deed_number' => (string) ($property->deed_number ?: '-'),
                'estimated_value' => $property->estimated_value !== null ? (float) $property->estimated_value : null,
                'image' => $images[0] ?? null,
                'images' => $images,
                'result_status' => $pivot?->result_status ?: 'pending',
                'opening_price' => $pivot?->opening_price !== null ? (float) $pivot->opening_price : null,
                'sale_price' => $pivot?->sale_price !== null ? (float) $pivot->sale_price : null,
                'participants_count' => $pivot?->participants_count !== null ? (int) $pivot->participants_count : null,
                'bids_count' => $pivot?->bids_count !== null ? (int) $pivot->bids_count : null,
                'unsold_reason' => $pivot?->unsold_reason,
                'buyer_name' => $pivot?->buyer_name,
                'result_notes' => $pivot?->result_notes,
                'board' => is_array($boardData) ? $boardData : [],
            ];
        })->values();

        $attachments = $auction->attachments
            ->filter(fn ($attachment) => in_array('report', $attachment->channels ?? [], true) && in_array($attachment->report_section, config('auction-report-inputs.sections'), true))
            ->map(fn ($attachment): array => [
                'id' => (int) $attachment->id,
                'name' => (string) $attachment->file_name,
                'display_name' => (string) ($auction->marketingCampaigns->firstWhere('id', $attachment->marketing_campaign_id)?->metrics['display_name'] ?? $attachment->report_metadata['display_name'] ?? ''),
                'channel' => $attachment->report_metadata['channel'] ?? null,
                'kind' => $attachment->report_metadata['kind'] ?? null,
                'link_role' => $attachment->report_metadata['link_role'] ?? null,
                'fit' => $attachment->report_metadata['fit'] ?? null,
                'focal_x' => $attachment->report_metadata['focal_x'] ?? 50,
                'focal_y' => $attachment->report_metadata['focal_y'] ?? 50,
                'section' => $attachment->report_section,
                'property_id' => $attachment->property_id ? (int) $attachment->property_id : null,
                'display_order' => (int) $attachment->display_order,
                'campaign_id' => $attachment->marketing_campaign_id ? (int) $attachment->marketing_campaign_id : null,
                'mime' => Storage::disk('tenant_public')->exists($attachment->file_path)
                    ? Storage::disk('tenant_public')->mimeType($attachment->file_path)
                    : null,
                'uri' => $this->dataUri($attachment->file_path),
            ])->groupBy('section')->map(fn ($items): array => $items->values()->all())->all();

        $marketing = is_array($auction->marketing_data) ? $auction->marketing_data : [];
        $marketing['campaigns'] = $auction->marketingCampaigns->map(fn ($campaign) => $campaign->reportData())->all();

        return (new AuctionReportBranding)->apply([
            'cycle_version' => 1,
            'branding' => ['issuing_entity_logo' => (new AuctionReportBranding)->image($auction->assignment?->issuingEntity?->image)],
            'closing_saved' => $auction->closing !== null,
            'closing' => $auction->closing ? ['date' => $auction->closing->closing_date?->format('Y-m-d'), 'approval_number' => $auction->establishmentRequest?->isApproved() ? $auction->establishmentRequest->approval_number : $auction->closing->approval_number] : null,
            'generated_at' => now()->toIso8601String(),
            'auction' => [
                'id' => (int) $auction->id,
                'name' => (string) $auction->name,
                'description' => (string) ($auction->description ?: ''),
                'city' => (string) ($auction->city ?: '-'),
                'type' => (string) $auction->type_label,
                'category' => (string) $auction->category_label,
                'is_active' => (bool) $auction->is_active,
                'status' => $auction->status->value,
                'timezone' => (string) config('app.timezone', 'Asia/Riyadh'),
                'start_date' => $auction->start_date?->format('Y-m-d'),
                'start_time' => $auction->start_time,
                'end_date' => $auction->end_date?->format('Y-m-d'),
                'end_time' => $auction->end_time,
                'supervising_entity' => (string) ($auction->supervising_entity ?: '-'),
                'project_manager_name' => (string) ($auction->project_manager_name ?: '-'),
                'platform_name' => (string) ($auction->platform_name ?: '-'),
                'platform_url' => $auction->platform_url,
                'cover_image' => $this->dataUri($auction->cover_image),
                'report_image' => $this->dataUri($auction->report_image),
            ],
            'assignment' => [
                'number' => (string) ($auction->assignment?->assignment_number ?: '-'),
                'name' => (string) ($auction->assignment?->name ?: '-'),
            ],
            'properties' => $properties->all(),
            'marketing' => $marketing,
            'evidence' => $attachments,
            'totals' => [
                'properties_count' => $properties->count(),
                'sold_count' => $properties->where('result_status', 'sold')->count(),
                'unsold_count' => $properties->where('result_status', 'unsold')->count(),
                'pending_count' => $properties->where('result_status', 'pending')->count(),
                'recorded_sales_total' => (float) $properties->where('result_status', 'sold')->sum('sale_price'),
                'participants_count' => (int) $properties->sum(fn (array $property): int => (int) ($property['participants_count'] ?? 0)),
                'bids_count' => (int) $properties->sum(fn (array $property): int => (int) ($property['bids_count'] ?? 0)),
            ],
        ]);
    }

    private function dataUri(?string $path, string $disk = 'tenant_public'): ?string
    {
        if (blank($path) || ! Storage::disk($disk)->exists($path)) {
            return null;
        }

        $contents = Storage::disk($disk)->get($path);
        $mime = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
