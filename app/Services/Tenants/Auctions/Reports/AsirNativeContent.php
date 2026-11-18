<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

final class AsirNativeContent
{
    public function prepareCover(array $content, array $snapshot): array
    {
        $content['native_cover'] = true;
        $content['native_template'] = $content['index'] === 0 ? 'cover' : 'closing';
        $branding = $snapshot['branding'] ?? null;
        $content['cover_legacy_branding'] = $branding === null;
        $content['company_logo'] = $branding === null ? $this->asset('cover-company-logo.svg') : $this->imageUri($branding['company_logo'] ?? null);
        $content['issuing_logo'] = $branding === null ? $this->asset('cover-issuing-logo.svg') : $this->imageUri($branding['issuing_entity_logo'] ?? null);
        $destinations = (new AuctionReportLinks)->destinations($snapshot);
        $content['cover_contacts'] = [];
        foreach ($snapshot['marketing']['contacts'] ?? [] as $role => $value) {
            if (! in_array($role, ['phone', 'unified_phone', 'social'], true) || trim((string) $value) === '') {
                continue;
            }
            $content['cover_contacts'][$role] = ['value' => (string) $value, 'url' => $destinations[$role] ?? null];
        }
        $content['cover_icons'] = ['phone' => $this->asset('cover-phone.svg'), 'social' => $this->asset('cover-social.svg')];

        return $content;
    }

    public function prepare(array $content, array $snapshot, array $page): array
    {
        $index = $content['index'];
        $content['native_template'] = match ($index) {
            1 => 'index', 2 => 'introduction', 3, 4, 5, 6 => 'narrative',
            7, 8, 16, 17 => 'boards', 9 => 'brochure', 10 => 'platform', 11 => 'video',
            12 => 'x', 13 => 'whatsapp', 14 => 'social', 15 => 'brokers', 18, 19 => 'results',
        };
        $content['page_title'] = match ($index) {
            1 => 'بيانات المزاد', 2 => 'مقدمة التقرير', 3 => 'ملخص التقرير', 4, 5, 6 => 'منهجية أعمال التسويق',
            18, 19 => 'مخرجات المزاد', default => "خطة التسويق بحسب\nمتطلبات دليل أعمال وكلاء البيع",
        };
        $content['section_title'] = match ($index) {
            4 => 'التخطيط والاستعداد', 5 => 'الإعداد والتصميم', 6 => 'النشر والترويج',
            7, 8 => 'لوحات دعائية عين العقار', 9 => 'الكتيب التسويقي', 10 => "نشر المزاد على\nالمنصة الإلكترونية",
            11 => 'الفيديو التسويقي', 12 => "الحملة الإعلانية\nعلى منصة x", 13 => "الحملة الإعلانية\nعلى الواتساب",
            14 => "الحملة الإعلانية عبر\nوسائل التواصل الاجتماعي", 15 => "عقود الوسطاء\nالمشتركين", 16, 17 => 'إزالة لوحات العقارات',
            18, 19 => 'تقرير دراسة العقارات', default => '',
        };
        $content['section_number'] = match ($index) {
            7, 8 => '01', 9 => '02', 10 => '03', 11 => '04', 12 => '05', 13 => '06', 14 => '07', 15 => '09', 16, 17 => '10', default => '',
        };
        $content['background'] = $this->imageUri($snapshot['auction']['report_image'] ?? null);
        $branding = $snapshot['branding'] ?? null;
        $content['company_logo'] = $branding !== null ? $this->imageUri($branding['company_logo'] ?? null) : $this->asset($index === 2 ? 'company-logo.svg' : 'static-3ba2f8c81664a1845f61.png');
        $content['issuing_logo'] = $branding !== null ? $this->imageUri($branding['issuing_entity_logo'] ?? null) : $this->asset('static-ebbe1f2e0fdcb1dceaaf.png');
        $content['icons'] = [];
        foreach (['snapchat', 'tiktok', 'x'] as $channel) {
            $content['icons'][$channel] = $this->asset('icon-'.$channel.'.svg');
        }
        $content['whatsapp_icon'] = $this->asset('native-whatsapp-icon.png');
        $content['youtube_icon'] = $this->asset('static-16fb58e6172207b55b87.jpg');
        $content['x_icon'] = $this->asset('native-x-symbol.png');
        $content['broker_watermark'] = $branding === null ? $this->asset('native-broker-watermark.png') : null;
        $content['broker_pattern'] = $this->asset('native-broker-pattern.png');
        $content['destinations'] = (new AuctionReportLinks)->destinations($snapshot);
        $content['campaigns'] = $snapshot['marketing']['campaigns'] ?? [];
        $content['legacy'] = ($snapshot['cycle_version'] ?? 0) < 1;
        $content['whatsapp_chart'] = $index === 13 ? $this->whatsappChart($snapshot) : null;
        $content['paragraphs'] = $this->paragraphs($index, $snapshot['marketing'] ?? []);
        $content['channels'] = $this->channels($content, $snapshot);
        $content['board_section'] = $index >= 16 ? 'board_after' : 'board_before';
        $content['board_offset'] = $page['payload']['offset'] ?? 0;
        $content['gallery_items'] = $page['payload'][$content['board_section']] ?? [];
        $content['image_items'] = $page['payload'][$content['native_template']] ?? [];

        return $content;
    }

    public function gallery(array $content, array $snapshot, array $page): array
    {
        $content = $this->prepare(array_replace($content, ['index' => 9]), $snapshot, $page);
        $content['native_template'] = 'gallery';
        $content['section_title'] = $content['values']['gallery_title'] ?? $content['values']['campaign_heading'] ?? 'مرفقات التسويق';
        $content['section_number'] = '';
        $content['image_items'] = $page['payload']['gallery'] ?? [];
        $content['gallery_campaign'] = isset($content['values']['campaign_heading']);
        $content['gallery_single_broker'] = ($page['payload']['gallery_section'] ?? null) === 'brokers' && count($content['image_items']) === 1;
        if ($content['gallery_campaign']) {
            $content['section_title'] = '';
        }

        return $content;
    }

    private function paragraphs(int $index, array $marketing): array
    {
        static $copy;
        $copy ??= require resource_path('auction-reports/asir/native-copy.php');
        $paragraphs = [];
        foreach ($copy[$index] ?? [] as $role) {
            $recorded = array_column($marketing['campaigns'] ?? [], $role);
            $value = $recorded === [] ? ($marketing[$role] ?? '') : implode("\n", array_filter($recorded, static fn ($text): bool => $text !== null && $text !== ''));
            $value = (string) $value;
            if (trim($value) !== '' && trim($value) !== '-') {
                $paragraphs[] = ['text' => $value, 'role' => $role];
            }
        }

        return $paragraphs;
    }

    private function channels(array $content, array $snapshot): array
    {
        $channels = [];
        static $labels;
        $labels ??= require base_path('lang/ar/auction_reports.php');
        foreach (['snapchat' => 'سناب شات', 'tiktok' => 'تيكتوك', 'x' => 'منصة اكس', 'youtube' => 'يوتيوب', 'whatsapp' => 'واتساب', 'google' => 'جوجل'] as $channel => $label) {
            $campaign = AuctionReportChannels::campaign($snapshot['marketing']['campaigns'] ?? [], $channel);
            $metrics = [];
            foreach ($labels['metrics'] ?? [] as $key => $metricLabel) {
                $value = $campaign[$key] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                $precision = in_array($key, ['watch_hours', 'profile_change', 'reach_change', 'views_change'], true) ? 2 : 0;
                $metrics[$key] = ['label' => $metricLabel, 'value' => is_numeric($value) ? (new AuctionReportFormat)->number((float) $value, $precision) : (string) $value];
            }
            if ($channel === 'whatsapp') {
                $ordered = [];
                foreach (['messages' => 'تم الإرسال', 'views' => 'تم المشاهدة', 'delivered' => 'تم الاستلام', 'failed' => 'فشل', 'pending' => 'قيد الانتظار', 'total' => 'الكل'] as $key => $tableLabel) {
                    if (isset($metrics[$key])) {
                        $ordered[$key] = array_replace($metrics[$key], ['label' => $tableLabel]);
                    }
                }
                $metrics = $ordered + $metrics;
            }
            $images = [];
            if ($content['index'] === 14) {
                // The original phone badges summarize views only. Other metrics
                // remain on their campaign's detail page rather than enlarging it.
                $metric = isset($metrics['views']) ? 'views' : 'impressions';
                $metrics = array_intersect_key($metrics, [$metric => true]);
                $slot = array_search($channel, ['snapchat', 'tiktok', 'x'], true);
                if ($slot !== false && isset($content['images']['social'][$slot])) {
                    $images[] = $content['images']['social'][$slot];
                }
            }
            $url = (new AuctionReportLinks)->safeUrl($campaign['url'] ?? null);
            if ($metrics !== [] || $images !== [] || $url !== null) {
                $channels[$channel] = compact('label', 'metrics', 'images', 'url');
            }
        }

        return $channels;
    }

    private function imageUri(?string $uri): ?string
    {
        return is_string($uri) && str_starts_with($uri, 'data:image/') ? $uri : null;
    }

    private function whatsappChart(array $snapshot): ?array
    {
        $campaign = AuctionReportChannels::campaign($snapshot['marketing']['campaigns'] ?? [], 'whatsapp');
        $counts = array_intersect_key($campaign, array_flip(['delivered', 'failed', 'pending']));
        if (count($counts) !== 3 || count(array_filter($counts, 'is_numeric')) !== 3 || array_sum($counts) <= 0) {
            return null;
        }
        $total = array_sum($counts);
        $segments = [];
        $offset = 0;
        foreach (['delivered' => '#005589', 'failed' => '#d9534f', 'pending' => '#a5a5a5'] as $key => $color) {
            $percent = round((float) $counts[$key] / $total * 100, 6);
            $segments[$key] = ['percent' => $percent, 'color' => $color, 'start' => $offset, 'end' => $offset + $percent];
            $offset += $percent;
        }

        return $segments;
    }

    private function asset(string $name): ?string
    {
        $path = resource_path('auction-reports/asir/'.$name);

        $mime = match (pathinfo($name, PATHINFO_EXTENSION)) {
            'svg' => 'svg+xml', 'jpg' => 'jpeg', default => 'png',
        };

        return is_file($path) ? 'data:image/'.$mime.';base64,'.base64_encode(file_get_contents($path)) : null;
    }
}
