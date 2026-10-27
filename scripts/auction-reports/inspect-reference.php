<?php

declare(strict_types=1);

$directory = $argv[1] ?? getenv('AUCTION_REPORT_REFERENCE_DIR');
if (! is_string($directory) || ! is_dir($directory)) {
    throw new InvalidArgumentException('Provide the reference directory.');
}
$destination = dirname(__DIR__, 2).'/tmp/pdfs/source-inventory';
if (! is_dir($destination)) {
    mkdir($destination, 0775, true);
}
foreach (['haram', 'asir'] as $name) {
    $html = file_get_contents($directory.'/'.$name.'.html');
    $marker = '<script id="template-package" type="application/json">';
    $start = strpos($html, $marker);
    if ($start === false) {
        throw new RuntimeException('Missing package: '.$name);
    }
    $start += strlen($marker);
    $end = strpos($html, '</script>', $start);
    $package = json_decode(substr($html, $start, $end - $start), true, flags: JSON_THROW_ON_ERROR);
    $inventory = ['fonts' => [], 'pages' => []];
    foreach ($package['fonts'] as $font) {
        $inventory['fonts'][] = array_diff_key($font, array_flip(['data', 'fullData', 'coverage']));
    }
    foreach ($package['pages'] as $index => $page) {
        $inventory['pages'][] = array_diff_key($page, ['svg' => true]);
        foreach ($inventory['pages'][$index]['tables'] as &$table) {
            unset($table['artwork'], $table['clean']);
        }
        unset($table);
        if ($name === 'asir') {
            $xml = new DOMDocument;
            $xml->loadXML($page['svg'], LIBXML_NONET);
            foreach ((new DOMXPath($xml))->query('//*[local-name()="image"][@data-image-def]') as $image) {
                preg_match('/@@([^@]+)@@/', $image->getAttributeNS('http://www.w3.org/1999/xlink', 'href'), $asset);
                $uri = $package['assets'][$asset[1]];
                [$header, $encoded] = explode(',', $uri, 2);
                $extension = str_contains($header, 'png') ? 'png' : 'jpg';
                $imageId = $image->getAttribute('data-image-def');
                $inventory['pages'][$index]['asset_types'][$imageId] = $header;
                if ($index === 0 || $index === 7 || $index === 15) {
                    file_put_contents($destination.'/asir-'.($index + 1).'-'.$imageId.'.'.$extension, base64_decode($encoded, true));
                }
            }
        }
        if (preg_match('~<image[^>]+href="@@([^@]+)@@"[^>]+data-backdrop="true"~', $page['svg'], $backdrop)) {
            $uri = $package['assets'][$backdrop[1]];
            [$header, $encoded] = explode(',', $uri, 2);
            $extension = str_contains($header, 'png') ? 'png' : 'jpg';
            file_put_contents($destination.'/'.$name.'-backdrop-'.($index + 1).'.'.$extension, base64_decode($encoded, true));
        }
    }
    file_put_contents($destination.'/'.$name.'.json', json_encode($inventory, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo $name.': '.count($package['pages']).' pages, '.count($package['assets']).' assets, '.count($package['fonts']).' fonts'.PHP_EOL;
}
