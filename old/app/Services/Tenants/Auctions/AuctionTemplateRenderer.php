<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions;

use App\Models\Tenants\Auctions\Auction;
use App\Support\Auctions\TemplateSchema;
use Illuminate\Validation\ValidationException;

final class AuctionTemplateRenderer
{
    public function __construct(private readonly TemplateSchema $schema) {}

    /** @return array<string, mixed> */
    public function render(Auction $auction, string $type): array
    {
        $snapshot = $auction->template_snapshot[$type] ?? null;
        if (! is_array($snapshot)) {
            throw ValidationException::withMessages(['template_versions.'.$type => 'لا توجد نسخة منشورة صالحة لهذا النوع.']);
        }

        $bindings = $this->bindings($auction);
        foreach ($this->schema->slots($snapshot['schema'] ?? []) as $slot) {
            if ($slot['required'] && blank($bindings[$slot['binding']] ?? null)) {
                throw ValidationException::withMessages(['template_bindings.'.$slot['binding'] => 'البيان المطلوب غير موجود في حقول المزاد: '.$slot['binding']]);
            }
        }

        return ['template' => $snapshot, 'bindings' => $bindings];
    }

    /** @return array<string, mixed> */
    public function bindings(Auction $auction): array
    {
        return [
            'auction.name' => $auction->name,
            'auction.description' => $auction->description,
            'auction.start_date' => $auction->start_date?->format('Y-m-d'),
            'auction.start_time' => $auction->start_time,
            'auction.end_date' => $auction->end_date?->format('Y-m-d'),
            'auction.end_time' => $auction->end_time,
            'auction.type' => $auction->type instanceof \BackedEnum ? $auction->type->value : (string) $auction->type,
            'auction.category' => $auction->category instanceof \BackedEnum ? $auction->category->value : (string) $auction->category,
            'auction.city' => $auction->city,
            'auction.expected_properties_count' => $auction->expected_properties_count,
            'auction.supervising_entity' => $auction->supervising_entity,
            'auction.platform_name' => $auction->platform_name,
            'auction.platform_url' => $auction->platform_url,
            'auction.cover_image' => $auction->cover_image,
        ];
    }
}
