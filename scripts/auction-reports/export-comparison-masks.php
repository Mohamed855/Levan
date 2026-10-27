<?php

declare(strict_types=1);

// Development-only masks: source field envelopes, table cells and media frames.
$root = dirname(__DIR__, 2);
foreach (['haram', 'asir'] as $design) {
    $pages = require $root.'/resources/auction-reports/'.$design.'/pages.php';
    $geometry = json_decode(file_get_contents($root.'/output/pdf/reference-fixtures/'.$design.'.geometry.json'), true, flags: JSON_THROW_ON_ERROR);
    $masks = [];
    foreach ($pages as $index => $page) {
        $boxes = [];
        foreach ($page['groups'] as $role => $ids) {
            foreach ($ids as $id) {
                $field = $page['fields'][$id];
                $width = max($field['width'], $page['layouts'][$role]['max_width'] ?? 0);
                $center = $field['x'] + $field['width'] / 2;
                $boxes[] = ['reason' => 'text:'.$role, 'box' => [$center - $width / 2 - 4, $field['y'] - $field['sy'] * 1.5, $center + $width / 2 + 4, $field['y'] + $field['sy'] * .7]];
            }
        }
        foreach ($page['tables'] as $id => $table) {
            foreach ($table['rows'] as $row) {
                foreach ($row['cells'] as $column => $cell) {
                    $boxes[] = ['reason' => 'cell:'.$id, 'box' => [$table['columns'][$column] + 3, $row['top'] + 2, $table['columns'][$column + 1] - 3, $row['bottom'] - 2]];
                }
            }
        }
        foreach ($geometry[$index]['regions'] as $region) {
            // The fixture uses the original landscape to verify the watermark.
            if ($region['kind'] === 'dynamic_image' && (! str_starts_with($region['id'], 'report_image:') || ($design === 'asir' && $index === 0 && $region['id'] === 'report_image:1'))) {
                $boxes[] = ['reason' => 'media:'.$region['id'], 'box' => $region['visibleBox']];
            }
        }
        $masks[] = ['page' => $index + 1, 'width' => $page['width'], 'height' => $page['height'], 'boxes' => $boxes];
    }
    file_put_contents($root.'/output/pdf/reference-fixtures/'.$design.'.masks.json', json_encode($masks, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo $design.': '.count($masks).' page masks'.PHP_EOL;
}
