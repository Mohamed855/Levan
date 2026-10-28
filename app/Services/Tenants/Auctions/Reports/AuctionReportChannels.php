<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

final class AuctionReportChannels
{
    public static function key(?string $value): string
    {
        return match (mb_strtolower(trim($value ?? ''))) {
            'x', 'twitter', 'أكس', 'اكس', 'تويتر' => 'x',
            'tiktok', 'tik tok', 'تيك توك', 'تيكتوك' => 'tiktok',
            'snapchat', 'snap', 'سناب شات', 'سنابشات' => 'snapchat',
            'youtube', 'you tube', 'يوتيوب' => 'youtube',
            'whatsapp', 'whats app', 'واتساب', 'واتس اب' => 'whatsapp',
            'google', 'google ads', 'جوجل' => 'google',
            default => 'other',
        };
    }

    public static function campaign(array $campaigns, string $channel): array
    {
        $matches = array_values(array_filter($campaigns, static fn (array $campaign): bool => self::key($campaign['platform'] ?? null) === $channel));
        if (count($matches) <= 1) {
            return $matches[0] ?? [];
        }
        $result = ['platform' => $channel];
        // Impressions/views are counts, not deduplicated audience reach. Never
        // invent reach or percentage aggregates across separate campaigns.
        foreach (['impressions', 'views', 'posts', 'messages', 'delivered', 'failed', 'pending', 'total'] as $metric) {
            $values = array_column($matches, $metric);
            $result[$metric] = count($values) === count($matches) && count(array_filter($values, 'is_numeric')) === count($matches)
                ? array_sum($values)
                : null;
        }

        return $result;
    }
}
