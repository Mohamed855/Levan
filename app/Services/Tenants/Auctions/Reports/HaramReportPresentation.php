<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

/** Layout changes specific to the Haram output, including its optional sections. */
final class HaramReportPresentation
{
    public function prepare(array $content, array $snapshot): array
    {
        $index = $content['index'];
        $definition = (new AuctionReportArtwork)->page('haram', $index);
        $overrides = $content['artwork_overrides'] ?? [];
        if ($index === 0) {
            $content['cards'] = [
                'project' => ['اسم المشروع', $content['values']['project']],
                'manager' => ['مدير المشروع', $content['values']['manager']],
                'property_count' => ['عدد العقارات', $content['values']['property_count']],
                'date_range' => ['تاريخ المزاد', $content['values']['date_range']],
            ];
            foreach (array_keys($content['cards']) as $role) {
                unset($definition['groups'][$role]);
            }
            $overrides['groups'] = $definition['groups'];
            $overrides['cover_panels'] = [];
            $overrides['hide_fields'] = array_map(static fn (int $id): string => 'f'.$id, range(5, 12));
            $overrides['backdrop_patches'] = [[[40, 535, 558, 581], -162]];
        }
        if ($index === 6) {
            $content['contacts'] = array_intersect_key($content['values'], array_flip(['phone', 'unified_phone', 'social']));
            $content['contact_links'] = (new AuctionReportLinks)->destinations($snapshot);
            $overrides['groups'] = ['footer' => ['f6']];
            $overrides['hide_fields'] = ['f2', 'f3', 'f4'];
            $overrides['backdrop_patches'] = [[[155, 517, 194, 560], [-54, 0]], [[155, 572, 194, 615], [-54, 0]]];
        }
        if (in_array($index, [0, 6], true)) {
            $overrides['backdrop_patches'][] = [[38, 43, 110, 105], -162];
            $overrides['backdrop_patches'][] = $index === 0
                ? [[495, 44, 555, 105], -162]
                : [[205, 170, 390, 360], [-195, 0]];
        }
        if (in_array($index, [1, 2], true)) {
            $content['values']['sold_heading'] = 'ملخص البيع';
            $content['values']['unsold_heading'] = 'أسباب عدم البيع';
            $overrides = $this->tables($content, $definition, $overrides);
            if ($index === 2 && empty($content['values']['narrative']) && ! isset($content['values']['continuation_heading'])) {
                $overrides['clear_boxes'][] = [25, 80, 575, 195];
                $overrides['hide_fields'][] = 'f2';
                $overrides['groups'] ??= $definition['groups'];
                unset($overrides['groups']['narrative']);
            }
        }
        if ($index === 3) {
            $content['marketing_sections'] = $this->marketingSections($content, $snapshot);
            $overrides['groups'] = ['footer' => ['f69']];
            $overrides['hide_fields'] = array_map(static fn (int $id): string => 'f'.$id, range(1, 68));
            $overrides['clear_boxes'] = [[25, 65, 575, 780]];
            $overrides['images'] = [];
            $overrides['hide_sections'] = ['x', 'snapchat'];
        }
        if ($index === 4) {
            $content['boards'] = array_intersect_key($content['images'], array_flip(['board_before', 'board_after']));
            $content['removal_date'] = $content['values']['removal_date'] ?? '';
            $title = $definition['fields']['f0'];
            $title['x'] = 200;
            $title['width'] = 355;
            $overrides['fields'] = $definition['fields'] + ['page_title' => $title];
            $content['values']['page_title'] = 'صور تركيب وفك اللوحات';
            $overrides['groups'] = ['footer' => ['f8'], 'page_title' => ['page_title']];
            $overrides['hide_fields'] = array_map(static fn (int $id): string => 'f'.$id, range(0, 7));
            $overrides['clear_boxes'] = [[25, 65, 575, 780]];
            $overrides['images'] = [];
            $overrides['hide_sections'] = ['board_before', 'board_after'];
        }
        $content['artwork_overrides'] = $overrides;

        return $content;
    }

    private function tables(array $content, array $definition, array $overrides): array
    {
        $overrides['tables'] = $overrides['tables'] ?? $definition['tables'];
        $overrides['clear_boxes'] = $overrides['clear_boxes'] ?? [];
        $overrides['hide_fields'] = $overrides['hide_fields'] ?? [];
        foreach ($overrides['tables'] as $id => &$table) {
            $rows = $table['rows'];
            $overrides['clear_boxes'][] = [$table['columns'][0] - 2, $table['bounds'][0] - 2, end($table['columns']) + 2, end($rows)['bottom'] + 2];
            $table['redraw'] = true;
            $table['padding'] = 8;
            $table['align_right'] = true;
            $table['text_color'] = '#214570';
            $table['row_fills'] = $id === 'sold' ? ['#ffffff', '#e8f5ee'] : ['#ffffff', '#eef1f5'];
            $table['header'] ??= [
                'top' => $table['bounds'][0], 'bottom' => $table['bounds'][1],
                'fill' => match ($id) {
                    'sold' => '#196243', 'unsold' => '#ba3e30', default => '#205883'
                },
                'labels' => match ($id) {
                    'sold' => ['سعر البيع', 'سعر الافتتاح', 'عدد المشاركين', 'عدد المزايدات', 'العقار', 'م'],
                    'unsold' => ['سبب عدم البيع', 'العقار', 'م'],
                    default => ['مقاس اللوحات', 'رقم الأصل', 'رقم الصك', 'نوع العقار', 'م'],
                },
                'field' => $definition['fields'][match ($id) {
                    'sold' => 'f15', 'unsold' => 'f50', default => 'f14'
                }],
            ];
            foreach ($rows as $row) {
                foreach ($row['cells'] as $cell) {
                    $overrides['hide_fields'] = array_merge($overrides['hide_fields'], $cell['fields']);
                }
            }
        }
        unset($table);
        if ($content['index'] === 1) {
            $overrides['clear_boxes'][] = [46, 143, 558, 193, '#ffffff'];
            $overrides['hide_fields'] = array_merge($overrides['hide_fields'], array_map(static fn (int $id): string => 'f'.$id, range(14, 22)));
        } elseif (! isset($overrides['tables']['sold']['body_bottom']) && ! isset($overrides['tables']['unsold']['body_bottom'])) {
            $overrides['clear_boxes'][] = [25, 200, 575, 770];
            $overrides['groups'] = $definition['groups'];
            $overrides['fields'] = $definition['fields'];
            foreach (['sold' => ['f14', 218.061], 'unsold' => ['f49', 424.148]] as $id => [$fieldId, $baseline]) {
                if (! empty($content['tables'][$id])) {
                    $field = $definition['fields'][$fieldId];
                    $field['x'] = 46;
                    $field['y'] = $baseline;
                    $field['width'] = 507;
                    $overrides['fields'][$id.'_heading'] = $field;
                    $overrides['groups'][$id.'_heading'] = [$id.'_heading'];
                }
            }
            $overrides['hide_fields'] = array_merge($overrides['hide_fields'], ['f13', 'f14', 'f48', 'f49']);
            $overrides['hide_fields'] = array_merge($overrides['hide_fields'], array_map(static fn (int $id): string => 'f'.$id, range(15, 20)), ['f50', 'f51', 'f52', 'f53']);
            if (empty($content['tables']['unsold'])) {
                $overrides['hide_fields'] = array_merge($overrides['hide_fields'], ['f48', 'f49', 'f50', 'f51', 'f52', 'f53']);
                $overrides['clear_boxes'][] = [25, 395, 575, 705];
            }
            if (empty($content['tables']['sold'])) {
                $overrides['hide_fields'] = array_merge($overrides['hide_fields'], array_map(static fn (int $id): string => 'f'.$id, range(13, 20)));
                $overrides['clear_boxes'][] = [25, 200, 575, 385];
            }
        }

        return $overrides;
    }

    private function marketingSections(array $content, array $snapshot): array
    {
        $sections = [];
        $labels = ['snapchat' => 'سناب شات', 'tiktok' => 'تيك توك', 'x' => 'X'];
        $metrics = [
            'impressions' => 'مرات الظهور', 'views' => 'المشاهدات', 'posts' => 'المنشورات',
            'profile_views' => 'مشاهدات الملف الشخصي', 'reach' => 'الوصول',
            'profile_change' => 'تغير مشاهدات الملف', 'reach_change' => 'تغير الوصول', 'views_change' => 'تغير المشاهدات',
        ];
        $links = new AuctionReportLinks;
        foreach ($snapshot['marketing']['campaigns'] ?? [] as $campaign) {
            $channel = AuctionReportChannels::key($campaign['platform'] ?? null);
            $values = [];
            foreach ($metrics as $key => $label) {
                if (isset($campaign[$key]) && $campaign[$key] !== '') {
                    $values[$key] = [$label, (new AuctionReportFormat)->number($campaign[$key])];
                }
            }
            $url = $links->safeUrl($campaign['url'] ?? null);
            $images = $content['images'][$channel] ?? [];
            if ($values !== [] || $url !== null || $images !== []) {
                $sections[$channel] = ['label' => $labels[$channel] ?? 'أدلة التسويق', 'metrics' => $values, 'url' => $url, 'images' => $images];
            }
        }
        foreach (['x', 'snapchat'] as $channel) {
            if (! empty($content['images'][$channel]) && ! isset($sections[$channel])) {
                $sections[$channel] = ['label' => $labels[$channel], 'metrics' => [], 'url' => null, 'images' => $content['images'][$channel]];
            }
        }
        foreach (['evidence_url' => 'أدلة التسويق', 'boards_url' => 'اللوحات'] as $key => $label) {
            $url = $links->safeUrl($snapshot['marketing'][$key] ?? null);
            if ($url !== null) {
                $sections[$key] = ['label' => $label, 'metrics' => [], 'url' => $url, 'images' => []];
            }
        }

        return $sections;
    }
}
