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
        if ($campaigns !== []) {
            return $this->campaignContent($snapshot, $campaigns[0], $definition, $haram);
        }
        $groups = $haram ? ['gallery_header' => ['f0', 'f1', 'f2', 'f3'], 'gallery_title' => ['f4', 'f5'], 'footer' => ['f8']] : ['gallery_title' => ['f2']];
        $values = ['gallery_header' => __('auction_reports.evidence_appendix', [], 'ar').' - '.$title, 'gallery_title' => $title, 'footer' => 'مخرجات المزاد - '.$snapshot['auction']['name']];
        $fields = $definition['fields'];
        $layouts = $haram ? [
            'gallery_header' => ['box' => [150, 5, 555, 43], 'fit' => 'shrink', 'min_font_size' => 9, 'max_lines' => 2],
            'gallery_title' => ['max_width' => 300],
            'footer' => ['max_width' => 84.749, 'max_lines' => 2],
        ] : [
            'gallery_title' => ['box' => [80, 170, 520, 255], 'fit' => 'shrink', 'min_font_size' => 18, 'max_lines' => 2, 'center' => true],
        ];
        $frames = [];
        $count = count($items);
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
            $item = $items[$slot];
            $frames[] = ['section' => 'gallery', 'slot' => $slot, 'box' => [$x, $y, $x + $width, $y + $height]];
            $values[$id] = (string) (($item['display_name'] ?? '') ?: __('auction_reports.attachment', [], 'ar').' '.($slot + 1));
            if (! empty($item['channel'])) {
                $channel = AuctionReportChannels::key($item['channel']);
                $values[$id] = __('auction_reports.channels.'.$channel, [], 'ar').' - '.$values[$id];
            }
            if (isset($item['document_page'])) {
                $values[$id] .= ' - '.(int) $item['document_page'];
            }
        }
        $hide = array_values(array_diff(array_keys($definition['fields']), $haram ? ['f9', 'f10'] : ['f0', 'f1']));

        return [
            'index' => $index, 'values' => $values, 'tables' => [],
            'images' => ['gallery' => $items, 'report_image' => array_fill(0, 2, ['uri' => $snapshot['auction']['report_image'] ?? null])],
            'artwork_overrides' => ['fields' => $fields, 'groups' => $groups, 'layouts' => $layouts, 'images' => $frames, 'link_fields' => [], 'hide_fields' => $hide, 'hide_sections' => array_values(array_diff(array_unique(array_column($definition['image_defs'], 'section')), ['report_image']))],
        ];
    }

    private function campaignContent(array $snapshot, array $campaign, array $definition, bool $haram): array
    {
        $channel = AuctionReportChannels::key($campaign['platform'] ?? null);
        $label = $campaign['platform_label'] ?? ($channel === 'other' ? ($campaign['platform'] ?? __('auction_reports.channels.other', [], 'ar')) : __('auction_reports.channels.'.$channel, [], 'ar'));
        $left = $haram ? 45 : 100;
        $right = $haram ? 550 : 500;
        $top = $haram ? 145 : 345;
        $fields = $definition['fields'];
        $groups = [];
        $layouts = [];
        $values = [];
        $add = static function (string $role, string $value, float $y, float $size = 11, int $lines = 1) use (&$fields, &$groups, &$layouts, &$values, $haram, $left, $right): void {
            $fields[$role] = ['id' => $role, 'x' => $left, 'y' => $y, 'width' => $right - $left, 'size' => $size, 'sy' => $size, 'font' => 'NotoSansArabic-Regular', 'color' => $haram ? '#214570' : '#ffffff', 'rtl' => true];
            $groups[$role] = [$role];
            $layouts[$role] = ['box' => [$left, $y - $size, $right, $y + ($lines - 1) * $size * 1.17 + 5], 'fit' => 'shrink', 'min_font_size' => 8, 'max_lines' => $lines];
            $values[$role] = $value;
        };
        $add('campaign_heading', (string) $label, $top, 16, 2);
        $position = 0;
        foreach (__('auction_reports.metrics', [], 'ar') as $metric => $metricLabel) {
            if (! array_key_exists($metric, $campaign) || $campaign[$metric] === null) {
                continue;
            }
            $precision = in_array($metric, ['watch_hours', 'profile_change', 'reach_change', 'views_change'], true) ? 2 : 0;
            $value = (new AuctionReportFormat)->number(is_numeric($campaign[$metric]) ? (float) $campaign[$metric] : null, $precision);
            $add('campaign_metric_'.$metric, $metricLabel.': '.$value, $top + 40 + $position++ * 20);
        }
        if ($position === 0) {
            $add('campaign_empty', __('auction_reports.not_available', [], 'ar'), $top + 40);
        }
        $link = (new AuctionReportLinks)->safeUrl($campaign['url'] ?? null);
        if ($link !== null) {
            $add('campaign_url', __('auction_reports.campaign_link', [], 'ar'), $haram ? 730 : 755);
        }
        if ($haram) {
            $groups['footer'] = ['f8'];
            $values['footer'] = 'مخرجات المزاد - '.$snapshot['auction']['name'];
            $layouts['footer'] = ['max_width' => 84.749, 'max_lines' => 2];
        }

        return [
            'index' => $haram ? 4 : 9, 'values' => $values, 'tables' => [],
            'images' => ['report_image' => array_fill(0, 2, ['uri' => $snapshot['auction']['report_image'] ?? null])],
            'link_destinations' => ['campaign' => $link],
            'artwork_overrides' => [
                'fields' => $fields, 'groups' => $groups, 'layouts' => $layouts, 'images' => [],
                'link_fields' => ['campaign' => 'campaign_url'],
                'hide_fields' => array_values(array_diff(array_keys($definition['fields']), $haram ? ['f9', 'f10'] : ['f0', 'f1'])),
                'hide_sections' => array_values(array_diff(array_unique(array_column($definition['image_defs'], 'section')), ['report_image'])),
            ],
        ];
    }
}
