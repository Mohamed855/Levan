<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

final class HaramNativeContent
{
    public function prepare(array $content, array $snapshot): array
    {
        $content['native_template'] = match ($content['index']) {
            1 => 'index', 2 => 'results', 3 => 'marketing', 4 => 'boards', 5 => 'brokers',
        };
        $content['page_title'] = match ($content['index']) {
            1 => 'مخرجات المزاد', 2 => 'ملخص المزاد', 3 => 'التسويق الرقمي',
            4 => 'صور تركيب اللوحات على أصول العقار', 5 => 'التسويق عبر الوسطاء العقاريين',
        };
        if ($content['index'] === 3) {
            $content['channels'] = $this->channels($content, $snapshot);
            $content['destinations'] = (new AuctionReportLinks)->destinations($snapshot);
        }

        return $content;
    }

    public function gallery(array $snapshot, array $page): array
    {
        $payload = $page['payload'];
        $title = __('auction_reports.sections.'.$payload['gallery_section'], [], 'ar');
        $campaign = $payload['campaigns'][0] ?? null;
        $content = [
            'index' => 4, 'native_template' => 'gallery',
            'page_title' => __('auction_reports.evidence_appendix', [], 'ar').' - '.$title,
            'values' => ['footer' => 'مخرجات المزاد - '.$snapshot['auction']['name']],
            'tables' => [], 'images' => $payload, 'gallery_title' => $title,
        ];
        if ($campaign !== null) {
            $channel = AuctionReportChannels::key($campaign['platform'] ?? null);
            $label = $campaign['platform_label'] ?? ($channel === 'other' ? ($campaign['platform'] ?? $title) : __('auction_reports.channels.'.$channel, [], 'ar'));
            $content['gallery_title'] = $label;
            $content['campaign_metrics'] = $this->metrics($campaign);
            $content['campaign_url'] = (new AuctionReportLinks)->safeUrl($campaign['url'] ?? null);
        }

        return $content;
    }

    private function channels(array $content, array $snapshot): array
    {
        $channels = [];
        foreach (['x' => 'أكس', 'tiktok' => 'تيك توك', 'snapchat' => 'سناب شات'] as $channel => $label) {
            $campaign = AuctionReportChannels::campaign($snapshot['marketing']['campaigns'] ?? [], $channel);
            $metrics = $this->metrics($campaign);
            $images = $content['images'][$channel] ?? [];
            $url = (new AuctionReportLinks)->safeUrl($campaign['url'] ?? null);
            if ($metrics === [] && $images === [] && $url === null) {
                continue;
            }
            $summaryKey = isset($metrics['impressions']) ? 'impressions' : (isset($metrics['views']) ? 'views' : null);
            $channels[$channel] = [
                'label' => $label, 'metrics' => $metrics, 'images' => $images, 'url' => $url,
                'summary_key' => $summaryKey,
                'summary' => $summaryKey !== null ? $metrics[$summaryKey][1] : null,
                'has_details' => $images !== [] || $url !== null || count($metrics) > ($summaryKey !== null ? 1 : 0)
                    || ($channel === 'tiktok' && isset($metrics['views'])),
            ];
        }

        return $channels;
    }

    private function metrics(array $campaign): array
    {
        $metrics = [];
        $format = new AuctionReportFormat;
        foreach (__('auction_reports.metrics', [], 'ar') as $key => $label) {
            if (! isset($campaign[$key]) || $campaign[$key] === '') {
                continue;
            }
            $precision = in_array($key, ['watch_hours', 'profile_change', 'reach_change', 'views_change'], true) ? 2 : 0;
            $value = is_numeric($campaign[$key]) ? $format->number((float) $campaign[$key], $precision) : (string) $campaign[$key];
            $metrics[$key] = [$label, $value];
        }

        return $metrics;
    }
}
