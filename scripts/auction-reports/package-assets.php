<?php

declare(strict_types=1);

// Development-only compiler. Runtime reports consume its local SVG/PHP output.
$directory = $argv[1] ?? getenv('AUCTION_REPORT_REFERENCE_DIR');
if (! is_string($directory) || ! is_dir($directory)) {
    throw new InvalidArgumentException('Provide the reference directory.');
}
$root = dirname(__DIR__, 2).'/resources/auction-reports';
foreach (['fonts', 'haram', 'asir'] as $folder) {
    if (! is_dir($root.'/'.$folder)) {
        mkdir($root.'/'.$folder, 0775, true);
    }
}
$sources = [
    'haram' => 'cbe05b0dae76c5cccdf162bf73fab656b9d79a0773eb9ac4bec5174e557e1fd5',
    'asir' => 'd2c024b60ce31688d14710bc6e53b6dfb634b907a9fe34928d853928fc04d160',
];
$files = [];
$fontCatalog = [];
$write = static function (string $relative, string $contents) use ($root, &$files): void {
    $target = $root.'/'.$relative;
    file_put_contents($target, $contents);
    $files[$relative] = hash('sha256', $contents);
};
$decode = static function (string $uri): string {
    $bytes = base64_decode(explode(',', $uri, 2)[1], true);
    if ($bytes === false) {
        throw new RuntimeException('Invalid embedded asset.');
    }

    return $bytes;
};
foreach ($sources as $name => $hash) {
    $path = $directory.'/'.$name.'.html';
    if (hash_file('sha256', $path) !== $hash) {
        throw new RuntimeException('Reference hash mismatch: '.$name);
    }
    $html = file_get_contents($path);
    $marker = '<script id="template-package" type="application/json">';
    $start = strpos($html, $marker) + strlen($marker);
    $end = strpos($html, '</script>', $start);
    $package = json_decode(substr($html, $start, $end - $start), true, flags: JSON_THROW_ON_ERROR);
    foreach ($package['fonts'] as $font) {
        if (! isset($font['fullData'])) {
            if (in_array($font['name'], ['NizarBBCKurdish', 'STV', 'Arimo-Bold'], true)) {
                $relative = 'fonts/subset-'.strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $font['name'])).'.woff';
                $write($relative, $decode($font['data']));
                $fontCatalog[$font['name']] = ['family' => 'ReportDigits'.count($fontCatalog), 'path' => $relative];
            }
            continue;
        }
        $filename = str_starts_with($font['name'], 'Noto') ? 'noto-sans-arabic.ttf' : 'ibm-plex-sans-arabic.ttf';
        $write('fonts/'.$filename, $decode($font['fullData']));
    }
    $bindings = require $root.'/'.$name.'/bindings.php';
    $pages = [];
    foreach ($package['pages'] as $index => $page) {
        $binding = $bindings[$index];
        $binding['images'] ??= [];
        $document = new DOMDocument;
        $document->loadXML($page['svg'], LIBXML_NONET | LIBXML_COMPACT);
        $xpath = new DOMXPath($document);
        $hidden = array_merge(...array_values($binding['groups']));
        $fields = [];
        foreach ($page['fields'] as $field) {
            if (isset($field['table'])) {
                $hidden[] = $field['id'];
            }
            unset($field['text']);
            $fields[$field['id']] = $field;
        }
        foreach ($xpath->query('//*[@data-field]') as $node) {
            if (in_array($node->getAttribute('data-field'), $hidden, true)) {
                $node->parentNode->removeChild($node);
            }
        }
        $imageDefinitions = [];
        $slots = [];
        foreach ($xpath->query('//*[local-name()="image"]') as $image) {
            if ($name === 'asir') {
                $uri = $image->getAttributeNS('http://www.w3.org/1999/xlink', 'href');
                preg_match('/@@([^@]+)@@/', $uri, $asset);
                $bytes = $decode($package['assets'][$asset[1]]);
                $isWatermark = in_array($asset[1], ['57f9524018b458c61391', '6ebd926c6a1f86696f4d'], true);
                $imageId = $image->getAttribute('id');
                $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');
                $insideDefinitions = $xpath->query('ancestor::*[local-name()="defs"]', $image)->length > 0;
                $insideMask = $xpath->query('ancestor::*[local-name()="mask"]', $image)->length > 0;
                $maskOnly = $insideMask || ($insideDefinitions && $xpath->query('//*[local-name()="use" and @xlink:href="#'.$imageId.'" and not(ancestor::*[local-name()="mask"])]')->length === 0);
                $probe = imagecreatefromstring($bytes);
                $colors = [];
                foreach ([.1, .3, .5, .7, .9] as $px) {
                    foreach ([.1, .3, .5, .7, .9] as $py) {
                        $colors[imagecolorat($probe, (int) ($px * (imagesx($probe) - 1)), (int) ($py * (imagesy($probe) - 1)))] = true;
                    }
                }
                imagedestroy($probe);
                $staticFill = count($colors) === 1 || min((int) $image->getAttribute('width'), (int) $image->getAttribute('height')) <= 8 || $asset[1] === '65f52b26836ac6bf08fb' || in_array($image->getAttribute('data-image-def'), $binding['static_images'] ?? [], true);
                if ((! $maskOnly && ! $staticFill) || $isWatermark) {
                    $section = $isWatermark ? 'report_image' : match ($index) {
                        7, 8 => 'board_before', 16, 17 => 'board_after', 15 => 'brokers',
                        9 => 'brochure', 10 => 'platform', 11 => 'video', 12 => 'x', 13 => 'whatsapp', 14 => 'social',
                        default => 'marketing_evidence',
                    };
                    $slotKey = $section.':'.$asset[1];
                    if (! isset($slots[$slotKey])) {
                        $slots[$slotKey] = count(array_filter(array_keys($slots), static fn (string $key): bool => str_starts_with($key, $section.':')));
                    }
                    $definitionId = $image->getAttribute('data-image-def');
                    $imageDefinitions[$definitionId] = ['section' => $section, 'slot' => $binding['image_slots'][$definitionId] ?? $slots[$slotKey]];
                    $image->setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', '__DYNAMIC_IMAGE__');
                } else {
                    $relative = 'asir/static-'.$asset[1].(str_contains($package['assets'][$asset[1]], 'image/png;') ? '.png' : '.jpg');
                    $write($relative, $bytes);
                    $image->setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', '__ASSET_'.$relative.'__');
                }
                continue;
            }
            if (! $image->hasAttribute('data-backdrop')) {
                $image->parentNode->removeChild($image);
                continue;
            }
            $uri = $image->getAttributeNS('http://www.w3.org/1999/xlink', 'href');
            preg_match('/@@([^@]+)@@/', $uri, $asset);
            $bitmap = imagecreatefromstring($decode($package['assets'][$asset[1]]));
            $scaleX = imagesx($bitmap) / $page['width'];
            $scaleY = imagesy($bitmap) / $page['height'];
            foreach ($binding['images'] as $frame) {
                [$left, $top, $right, $bottom] = $frame['box'];
                $color = imagecolorallocate($bitmap, 244, 245, 250);
                imagefilledrectangle($bitmap, (int) floor($left * $scaleX), (int) floor($top * $scaleY), (int) ceil($right * $scaleX), (int) ceil($bottom * $scaleY), $color);
            }
            ob_start();
            imagepng($bitmap, null, 6);
            $write($name.'/background-'.($index + 1).'.png', ob_get_clean());
            imagedestroy($bitmap);
            $image->setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', '__BACKGROUND__');
        }
        foreach ($xpath->query('//*[@data-text]') as $glyph) {
            $glyph->removeAttribute('data-text');
        }
        $tables = [];
        foreach ($page['tables'] as $table) {
            unset($table['clean'], $table['artwork']);
            foreach ($table['rows'] as &$row) {
                foreach ($row['cells'] as &$cell) {
                    unset($cell['text']);
                }
                unset($cell);
            }
            unset($row);
            $tables[$table['id']] = $table;
        }
        $write($name.'/page-'.($index + 1).'.svg', $document->saveXML($document->documentElement));
        $pages[] = ['width' => $page['width'], 'height' => $page['height'], 'fields' => $fields, 'tables' => $tables, 'image_defs' => $imageDefinitions] + $binding;
    }
    $write($name.'/pages.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($pages, true).";\n");
}
foreach (['NotoSansArabic-OFL.txt', 'IBMPlexSansArabic-OFL.txt'] as $license) {
    $write('fonts/'.$license, file_get_contents($directory.'/'.$license));
}
$ge = $argv[2] ?? null;
if (is_string($ge) && is_file($ge)) {
    $write('fonts/ge-contrast-bold.ttf', file_get_contents($ge));
}
$nizar = $argv[3] ?? null;
if (is_string($nizar) && is_file($nizar)) {
    $write('fonts/nizar-bbc-kurdish.ttf', file_get_contents($nizar));
}
$logoDocument = new DOMDocument;
$logoDocument->loadXML($package['pages'][15]['svg'], LIBXML_NONET);
$logo = (new DOMXPath($logoDocument))->query('//*[@data-image-def="i7"]')->item(0);
preg_match('/@@([^@]+)@@/', $logo->getAttributeNS('http://www.w3.org/1999/xlink', 'href'), $logoAsset);
$logoUri = $package['assets'][$logoAsset[1]];
$write('fallback.svg', '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="523" height="132" viewBox="0 0 523 132"><image width="523" height="132" xlink:href="'.$logoUri.'"/></svg>');
$write('fonts/catalog.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($fontCatalog, true).";\n");
$forms = array_filter($package['forms'], static fn (string $key): bool => mb_strlen($key) === 1, ARRAY_FILTER_USE_KEY);
$write('fonts/arabic-forms.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($forms, true).";\n");
if (is_file($root.'/fonts/numeric-metrics.php')) {
    $write('fonts/numeric-metrics.php', file_get_contents($root.'/fonts/numeric-metrics.php'));
}
ksort($files);
file_put_contents($root.'/manifest.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export(['source_hashes' => $sources, 'files' => $files], true).";\n");
echo count($files).' local assets packaged.'.PHP_EOL;
