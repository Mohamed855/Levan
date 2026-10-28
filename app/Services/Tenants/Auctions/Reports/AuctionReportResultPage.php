<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

final class AuctionReportResultPage
{
    public function continuation(array $content, string $section): array
    {
        $definition = (new AuctionReportArtwork)->page('haram', 2);
        $table = $definition['tables'][$section];
        $shift = 140 - $table['bounds'][0];
        $table['bounds'] = array_map(static fn ($value) => $value + $shift, $table['bounds']);
        foreach ($table['rows'] as &$row) {
            $row['top'] += $shift;
            $row['bottom'] += $shift;
        }
        unset($row);
        $table['redraw'] = true;
        $table['body_bottom'] = 730;
        $table['row_fills'] = $section === 'unsold' ? ['#ffffff', '#ba3e30'] : ['#ffffff', '#e8f5ee'];
        $table['header'] = [
            'top' => $table['bounds'][0], 'bottom' => $table['bounds'][1],
            'fill' => $section === 'unsold' ? '#ba3e30' : '#196243',
            'labels' => $section === 'unsold' ? ['أسباب عدم البيع', 'نوع العقار', 'م'] : ['سعر البيع', 'سعر الافتتاح', 'عدد المشاركين', 'عدد المزايدات', 'نوع العقار', 'م'],
            'field' => $definition['fields'][$section === 'unsold' ? 'f50' : 'f15'],
        ];
        $heading = $definition['fields'][$section === 'unsold' ? 'f49' : 'f14'];
        $heading['x'] = 46;
        $heading['y'] = 100;
        $heading['width'] = 507;
        $content['values'] = ['footer' => $content['values']['footer'], 'continuation_heading' => $table['name'].' - تابع'];
        $content['tables'] = [$section => $content['tables'][$section]];
        $content['artwork_overrides'] = [
            'fields' => $definition['fields'] + ['continuation_heading' => $heading],
            'groups' => ['footer' => ['f84'], 'continuation_heading' => ['continuation_heading']],
            'layouts' => ['footer' => ['max_width' => 230], 'continuation_heading' => ['box' => [46, 75, 553, 115], 'fit' => 'shrink', 'min_font_size' => 8, 'max_lines' => 2]],
            'tables' => [$section => $table], 'clear_boxes' => [[25, 65, 575, 770]],
            'hide_fields' => array_values(array_diff(array_keys($definition['fields']), ['f84'])),
        ];

        return $content;
    }
}
