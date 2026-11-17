<?php

declare(strict_types=1);

return static function (string $scenario): array {
    $snapshot = require __DIR__.'/asir.php';
    $image = static function (int $id, string $section, int $slot = 0): array {
        $path = resource_path('auction-reports/fallback.svg');
        if ($section === 'board_before' || $section === 'board_after') {
            $reference = base_path('tmp/pdfs/source-inventory/asir-8-i'.(2 + $slot % 6).'.jpg');
            if (is_file($reference)) {
                $path = $reference;
            }
        }

        return [
            'id' => $id, 'uri' => 'data:image/'.(str_ends_with($path, '.jpg') ? 'jpeg' : 'svg+xml').';base64,'.base64_encode(file_get_contents($path)),
            'fit' => 'contain', 'display_name' => 'مرفق '.$id,
        ];
    };
    if ($scenario === 'reference') {
        foreach (['board_before', 'board_after'] as $offset => $section) {
            for ($i = 0; $i < 13; $i++) {
                $snapshot['evidence'][$section][] = $image(100 + $offset * 100 + $i, $section, $i);
            }
        }

        return $snapshot;
    }
    $snapshot['cycle_version'] = 1;
    $snapshot['closing_saved'] = true;
    $snapshot['marketing'] = array_replace($snapshot['marketing'], [
        'city_context' => 'المملكة ومنطقة عسير.', 'planning_methodology' => 'دراسة الفئات المستهدفة ووسائل الوصول إليها وواقع السوق العقاري.',
        'name_context' => 'إعداد الهوية البصرية للمزاد.', 'brochure_methodology' => 'تصميم الملف التعريفي للعقارات.',
        'audience_methodology' => 'التواصل مع المهتمين بالعقارات.', 'messages_methodology' => 'إعداد الرسائل التعريفية والتذكيرية.',
        'board_context' => 'صور تركيب اللوحات للعقارات المعروضة بالمزاد.',
        'brochure_url' => 'https://example.com/brochure', 'video_url' => 'https://example.com/video',
    ]);
    $snapshot['auction']['platform_url'] = 'https://example.com/platform';
    $campaigns = [];
    foreach (['brochure', 'platform', 'youtube', 'x', 'whatsapp', 'snapchat', 'tiktok', 'brokers', 'google'] as $i => $platform) {
        $campaigns[] = ['id' => $i + 1, 'platform' => $platform, 'views' => 1200 + $i, 'url' => 'https://example.com/'.$platform];
    }
    $campaigns[3]['posts'] = 7;
    $campaigns[4] += ['messages' => 3, 'delivered' => 1600, 'failed' => 0, 'pending' => 0, 'total' => 1600];
    $campaigns[7]['display_name'] = 'الوسيط العقاري';
    $snapshot['marketing']['campaigns'] = $campaigns;
    foreach (['brochure' => 1, 'platform' => 2, 'video' => 3, 'x' => 4, 'whatsapp' => 5, 'brokers' => 8] as $section => $campaignId) {
        $snapshot['evidence'][$section] = [$image(300 + $campaignId, $section) + ['campaign_id' => $campaignId]];
    }
    foreach (['snapchat' => 6, 'tiktok' => 7, 'x' => 4] as $channel => $id) {
        $snapshot['evidence']['social'][] = $image(400 + $id, 'social') + ['campaign_id' => $id, 'channel' => $channel];
    }
    foreach (['board_before', 'board_after'] as $offset => $section) {
        for ($i = 0; $i < 13; $i++) {
            $snapshot['evidence'][$section][] = $image(100 + $offset * 100 + $i, $section, $i);
        }
    }
    if ($scenario === 'sparse') {
        $snapshot['closing_saved'] = false;
        $snapshot['auction']['description'] = '';
        $snapshot['auction']['platform_name'] = '';
        $snapshot['auction']['platform_url'] = null;
        $snapshot['evidence'] = [];
        $snapshot['marketing'] = ['campaigns' => [['platform' => 'x', 'views' => 0]]];
    }
    if (in_array($scenario, ['many', 'long'], true)) {
        $template = $snapshot['properties'][0];
        $snapshot['properties'] = [];
        for ($i = 1; $i <= 47; $i++) {
            $snapshot['properties'][] = array_replace($template, [
                'id' => $i, 'number' => 'PROP-'.$i, 'deed_number' => '3717010026'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'result_status' => $i <= 19 ? 'sold' : 'unsold',
                'unsold_reason' => str_repeat('سبب عدم البيع المسجل للعقار '.$i.'. ', $scenario === 'long' ? 100 : 1),
            ]);
        }
        $snapshot['totals'] = array_replace($snapshot['totals'], [
            'properties_count' => 47, 'sold_count' => 19, 'unsold_count' => 28,
            'recorded_sales_total' => 19 * 600000,
        ]);
        foreach (['brochure' => 1, 'x' => 4, 'brokers' => 8] as $section => $id) {
            for ($i = 1; $i <= 17; $i++) {
                $snapshot['evidence'][$section][] = $image(1000 * $id + $i, $section) + ['campaign_id' => $id, 'document_page' => $i];
            }
        }
        if ($scenario === 'long') {
            $snapshot['marketing']['introduction'] = str_repeat('التقرير التفصيلي لنتائج المزاد والعقارات المعروضة. ', 300);
            $snapshot['marketing']['planning_methodology'] = str_repeat('دراسة الفئات المستهدفة ووسائل الوصول إليها وواقع السوق العقاري. ', 200);
            $snapshot['auction']['project_manager_name'] = str_repeat('مدير المشروع ', 30);
        }
    }

    return $snapshot;
};
