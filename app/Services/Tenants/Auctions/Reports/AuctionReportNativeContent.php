<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

final class AuctionReportNativeContent
{
    public function apply(array $content, array $snapshot): array
    {
        if (($snapshot['cycle_version'] ?? 0) < 1) {
            return $content;
        }
        foreach ($content['values'] as $role => $value) {
            if ($value === '-' || $value === null) {
                $content['values'][$role] = '';
            }
        }
        foreach ($content['tables'] as $table => $rows) {
            $content['tables'][$table] = array_map(fn ($row) => array_map(fn ($value) => $value === '-' || $value === null ? '' : $value, $row), $rows);
        }
        $campaigns = $snapshot['marketing']['campaigns'] ?? [];
        foreach (array_keys(config('auction-marketing-fields.text')) as $role) {
            if (array_key_exists($role, $content['values'])) {
                $value = $snapshot['marketing'][$role] ?? '';
                $recorded = [];
                foreach ($campaigns as $campaign) {
                    if (array_key_exists($role, $campaign)) {
                        $recorded[] = $campaign[$role];
                    }
                }
                if ($recorded !== []) {
                    $value = implode("\n", array_filter($recorded, fn ($text) => $text !== null && $text !== ''));
                }
                $content['values'][$role] = (string) $value;
            }
        }
        foreach ($campaigns as $campaign) {
            if (($campaign['platform'] ?? '') === 'brokers' && isset($campaign['display_name'])) {
                $content['values']['broker_1'] = (string) $campaign['display_name'];
            }
            if (($campaign['platform'] ?? '') === 'brokers' && isset($campaign['broker_second_name']) && isset($content['values']['broker_2'])) {
                $content['values']['broker_2'] = (string) $campaign['broker_second_name'];
            }
        }
        $campaign = AuctionReportChannels::campaign($snapshot['marketing']['campaigns'] ?? [], 'whatsapp');
        foreach (['messages' => 'whatsapp_summary', 'views' => 'whatsapp_views_summary'] as $metric => $role) {
            if (array_key_exists($role, $content['values']) && ($campaign[$metric] ?? null) === null) {
                $content['values'][$role] = '';
            }
        }
        if (! array_key_exists('status', $snapshot['auction']) && ! array_key_exists('is_active', $snapshot['auction']) && array_key_exists('status', $content['values'])) {
            $content['values']['status'] = '';
        }
        $content['artwork_overrides']['omit_empty_notices'] = true;
        if (array_key_exists('narrative', $content['values'])) {
            $content['values']['narrative'] = (string) ($snapshot['marketing']['results_narrative'] ?? '');
        }
        if (array_key_exists('removal_date', $content['values'])) {
            $date = ! empty($snapshot['closing_saved']) ? ($snapshot['marketing']['board_removal_date'] ?? null) : null;
            $content['values']['removal_date'] = $date ? 'فك اللوحات - '.(new AuctionReportFormat)->date($date) : '';
        }
        if ($content['index'] === 14) {
            $counts = [];
            foreach (['snapchat', 'tiktok', 'x'] as $index => $channel) {
                $campaign = AuctionReportChannels::campaign($snapshot['marketing']['campaigns'] ?? [], $channel);
                $value = $campaign['views'] ?? $campaign['impressions'] ?? null;
                $content['values']['campaign_'.($index + 1).'_views'] = $value === null ? '' : (new AuctionReportFormat)->number($value);
                if ($campaign !== []) {
                    $counts[] = $value;
                }
            }
            $content['values']['social_total'] = $counts !== [] && count(array_filter($counts, 'is_numeric')) === count($counts) ? (new AuctionReportFormat)->arabicDigits((new AuctionReportFormat)->number(array_sum($counts))) : '';
        }

        return $content;
    }
}
