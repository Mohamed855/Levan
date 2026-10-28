<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

final class AuctionReportEvidencePage
{
    public function content(array $snapshot, array $page, string $design): array
    {
        $haram = $design === 'haram';
        $index = $haram ? 4 : 9;
        $definition = (new AuctionReportArtwork)->page($design, $index);
        $payload = $page['payload'];
        $section = $payload['gallery_section'];
        $title = __('auction_reports.sections.'.$section, [], 'ar');
        $campaigns = $payload['campaigns'] ?? [];
        $items = $payload['gallery'];
        $groups = $haram ? ['gallery_header' => ['f0', 'f1', 'f2', 'f3'], 'gallery_title' => ['f4', 'f5'], 'footer' => ['f8']] : ['gallery_title' => ['f2']];
        $values = ['gallery_header' => __('auction_reports.evidence_appendix', [], 'ar').' - '.$title, 'gallery_title' => $title, 'footer' => 'مخرجات المزاد - '.$snapshot['auction']['name']];
        $fields = $definition['fields'];
        $layouts = $haram ? [
            'gallery_header' => ['box' => [150, 5, 555, 43], 'fit' => 'shrink', 'min_font_size' => 9, 'max_lines' => 2],
            'gallery_title' => ['max_width' => 300],
            'footer' => ['max_width' => 230],
        ] : [
            'gallery_title' => ['box' => [80, 170, 520, 255], 'fit' => 'shrink', 'min_font_size' => 18, 'max_lines' => 2, 'center' => true],
        ];
        $frames = [];
        $count = count($campaigns ?: $items);
        foreach (range(0, max(0, $count - 1)) as $slot) {
            if ($count === 0) {
                break;
            }
            $single = $count === 1 && $section === 'brokers';
            $x = $single ? ($haram ? 45 : 100) : ($haram ? 45 + ($slot % 2) * 265 : 95 + ($slot % 2) * 215);
            $y = $haram ? 135 + intdiv($slot, 2) * 215 : 350 + intdiv($slot, 2) * 135;
            $width = $single ? ($haram ? 505 : 400) : ($haram ? 240 : 190);
            $height = $single ? ($haram ? 620 : 400) : ($haram ? 175 : 100);
            $id = 'gallery_caption_'.$slot;
            $groups[$id] = [$id];
            $fields[$id] = ['id' => $id, 'x' => $x, 'y' => $y + $height + 16, 'width' => $width, 'size' => 11.0, 'sy' => 11.0, 'font' => 'NotoSansArabic-Regular', 'color' => $haram ? '#214570' : '#ffffff', 'rtl' => true];
            $layouts[$id] = ['box' => [$x, $y + $height + 2, $x + $width, $y + $height + 30], 'fit' => 'shrink', 'min_font_size' => 7, 'max_lines' => 2, 'center' => true];
            if ($campaigns !== []) {
                $campaign = $campaigns[$slot];
                $channel = AuctionReportChannels::key($campaign['platform'] ?? null);
                $label = $campaign['platform_label'] ?? ($channel === 'other' ? ($campaign['platform'] ?? __('auction_reports.channels.other', [], 'ar')) : __('auction_reports.channels.'.$channel, [], 'ar'));
                $values[$id] = $label.' - '.__('auction_reports.impressions', [], 'ar').': '.(isset($campaign['impressions']) ? number_format((float) $campaign['impressions']) : __('auction_reports.not_available', [], 'ar'));
            } else {
                $item = $items[$slot];
                $frames[] = ['section' => 'gallery', 'slot' => $slot, 'box' => [$x, $y, $x + $width, $y + $height]];
                $values[$id] = (string) (($item['display_name'] ?? '') ?: __('auction_reports.attachment', [], 'ar').' '.($slot + 1));
                if (isset($item['document_page'])) {
                    $values[$id] .= ' - '.(int) $item['document_page'];
                }
            }
        }
        $hide = array_values(array_diff(array_keys($definition['fields']), $haram ? ['f9', 'f10'] : ['f0', 'f1']));

        return [
            'index' => $index, 'values' => $values, 'tables' => [],
            'images' => ['gallery' => $items, 'report_image' => array_fill(0, 2, ['uri' => $snapshot['auction']['report_image'] ?? null])],
            'artwork_overrides' => ['fields' => $fields, 'groups' => $groups, 'layouts' => $layouts, 'images' => $frames, 'link_fields' => [], 'hide_fields' => $hide, 'hide_sections' => array_values(array_diff(array_unique(array_column($definition['image_defs'], 'section')), ['report_image']))],
        ];
    }
}
