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
        $auction->loadMissing(['assignment', 'properties', 'attachments']);

        $properties = $auction->properties->map(function ($property): array {
            $pivot = $property->pivot;
            $boardData = $pivot?->board_data;
            if (is_string($boardData)) {
                $boardData = json_decode($boardData, true) ?: [];
            }

            return [
                'id' => (int) $property->id,
                'number' => (string) $property->property_number,
                'name' => (string) $property->name,
                'type' => (string) $property->type_label,
                'city' => (string) ($property->city ?: '-'),
                'district' => (string) ($property->district ?: '-'),
                'area' => $property->area !== null ? (float) $property->area : null,
                'estimated_value' => $property->estimated_value !== null ? (float) $property->estimated_value : null,
                'image' => $this->dataUri($property->main_image),
                'result_status' => $pivot?->result_status ?: 'pending',
                'opening_price' => $pivot?->opening_price !== null ? (float) $pivot->opening_price : null,
                'sale_price' => $pivot?->sale_price !== null ? (float) $pivot->sale_price : null,
                'participants_count' => $pivot?->participants_count !== null ? (int) $pivot->participants_count : null,
                'bids_count' => $pivot?->bids_count !== null ? (int) $pivot->bids_count : null,
                'unsold_reason' => $pivot?->unsold_reason,
                'board' => is_array($boardData) ? $boardData : [],
            ];
        })->values();

        $attachments = $auction->attachments->map(fn ($attachment): array => [
            'name' => (string) $attachment->file_name,
            'section' => $attachment->report_section,
            'property_id' => $attachment->property_id ? (int) $attachment->property_id : null,
            'display_order' => (int) $attachment->display_order,
            'uri' => $this->dataUri($attachment->file_path),
        ])->groupBy('section')->map->values()->all();

        return [
            'generated_at' => now()->toIso8601String(),
            'auction' => [
                'id' => (int) $auction->id,
                'name' => (string) $auction->name,
                'description' => (string) ($auction->description ?: ''),
                'city' => (string) ($auction->city ?: '-'),
                'type' => (string) $auction->type_label,
                'category' => (string) $auction->category_label,
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
                'issuing_entity' => (string) ($auction->assignment?->issuing_entity ?: '-'),
            ],
            'properties' => $properties->all(),
            'marketing' => is_array($auction->marketing_data) ? $auction->marketing_data : [],
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
        ];
    }

    private function dataUri(?string $path): ?string
    {
        if (blank($path) || ! Storage::disk('tenant_public')->exists($path)) {
            return null;
        }

        $contents = Storage::disk('tenant_public')->get($path);
        $mime = Storage::disk('tenant_public')->mimeType($path) ?: 'application/octet-stream';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
