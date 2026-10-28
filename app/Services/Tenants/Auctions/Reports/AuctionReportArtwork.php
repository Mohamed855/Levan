<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;

final class AuctionReportArtwork
{
    private const SVG = 'http://www.w3.org/2000/svg';

    private const XLINK = 'http://www.w3.org/1999/xlink';

    private array $documents = [];

    private array $bindings = [];

    private array $uris = [];

    private ?AuctionReportTextLayout $textLayout = null;

    private ?AuctionReportFonts $fonts = null;

    public function __construct(private readonly string $root = '') {}

    /** @param array<string, string> $values
     * @param  array<string, list<list<string>>>  $tables
     * @param  array<string, list<array<string, mixed>>>  $images
     */
    public function render(string $design, int $index, array $values, array $tables, array $images, int $pageNumber, array $tableLayouts = [], array $fieldLayouts = [], array $overrides = []): string
    {
        $page = array_replace($this->page($design, $index), $overrides);
        $source = file_get_contents($this->path($design.'/page-'.($index + 1).'.svg'));
        if (str_contains($source, '__BACKGROUND__')) {
            $source = str_replace('__BACKGROUND__', $this->uri($design.'/background-'.($index + 1).'.png', 'image/png'), $source);
        }
        $source = preg_replace_callback('~__ASSET_([a-z0-9/.-]+)__~', fn (array $match): string => $this->uri($match[1], str_ends_with($match[1], '.png') ? 'image/png' : 'image/jpeg'), $source);
        $document = new DOMDocument;
        $document->loadXML($source, LIBXML_NONET | LIBXML_COMPACT);
        $this->prefixIds($document, 'report_'.$pageNumber.'_');
        $svg = $document->documentElement;
        $svg->setAttribute('data-report-page', (string) $pageNumber);
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//*[@data-field]') as $node) {
            if (in_array($node->getAttribute('data-field'), $page['hide_fields'] ?? [], true)) {
                $node->parentNode?->removeChild($node);
            }
        }
        foreach ($page['hide_clips'] ?? [] as $clip) {
            foreach ($xpath->query('//*[@clip-path="url(#report_'.$pageNumber.'_'.$clip.')"]') as $node) {
                $node->parentNode?->removeChild($node);
            }
        }
        foreach ($xpath->query('//*[@data-image-def]') as $definition) {
            $id = $definition->getAttribute('data-image-def');
            if (! isset($page['image_defs'][$id])) {
                continue;
            }
            $frame = $page['image_defs'][$id];
            $item = $images[$frame['section']][$frame['slot']] ?? [];
            if (in_array($frame['section'], $page['hide_sections'] ?? [], true) || ($item === [] && $frame['section'] !== 'report_image')) {
                $definition->setAttributeNS(self::XLINK, 'xlink:href', 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"/>'));
                $definition->setAttribute('visibility', 'hidden');
                foreach ($xpath->query('//*[@data-image="'.$id.'"]') as $usage) {
                    $usage->setAttribute('visibility', 'hidden');
                }

                continue;
            }
            $uri = $item['uri'] ?? null;
            $valid = $this->isImage($uri);
            $fit = $valid ? $this->imageFit($item, $frame['section']) : 'contain';
            $definition->setAttribute('preserveAspectRatio', $fit === 'cover' ? 'xMidYMid slice' : 'xMidYMid meet');
            $definition->setAttributeNS(self::XLINK, 'xlink:href', $valid ? $uri : $this->uri('fallback.svg', 'image/svg+xml'));
            $definition->setAttribute('data-report-media', $frame['section'].':'.$frame['slot']);
            $definition->setAttribute('data-report-attachment', (string) ($item['id'] ?? ''));
            $definition->setAttribute('data-document-page', (string) ($item['document_page'] ?? ''));
            foreach ($xpath->query('//*[@data-image="'.$id.'"]') as $usage) {
                $usage->setAttribute('data-report-media', $frame['section'].':'.$frame['slot']);
            }
            if (isset($page['fallback_frames'][$id])) {
                $this->fitInFrame($document, $xpath, $page['fallback_frames'][$id], $id, $definition->getAttributeNS(self::XLINK, 'href'), $frame['section'].':'.$frame['slot'], $pageNumber, $fit, $item);
            }
        }

        foreach ($page['images'] as $frame) {
            $item = $images[$frame['section']][$frame['slot']] ?? [];
            if ($item === []) {
                continue;
            }
            $uri = $item['uri'] ?? null;
            $valid = $this->isImage($uri);
            [$x, $y, $right, $bottom] = $frame['box'];
            $image = $document->createElementNS(self::SVG, 'image');
            foreach (['x' => $x, 'y' => $y, 'width' => $right - $x, 'height' => $bottom - $y] as $key => $value) {
                $image->setAttribute($key, (string) $value);
            }
            $fit = $valid ? $this->imageFit($item, $frame['section']) : 'contain';
            $image->setAttribute('preserveAspectRatio', 'xMidYMid meet');
            $image->setAttribute('data-report-media', $frame['section'].':'.$frame['slot']);
            $image->setAttribute('data-report-attachment', (string) ($item['id'] ?? ''));
            $image->setAttribute('data-document-page', (string) ($item['document_page'] ?? ''));
            $image->setAttributeNS(self::XLINK, 'xlink:href', $valid ? $uri : $this->uri('fallback.svg', 'image/svg+xml'));
            if ($fit === 'cover') {
                $this->coverImage($document, $image, $frame['box'], $item, 'report_'.$pageNumber.'_manual_'.$frame['section'].'_'.$frame['slot']);
            }
            $svg->appendChild($image);
        }

        foreach ($page['empty_regions'] ?? [] as $box) {
            [$left, $top, $right, $bottom] = $box;
            $rect = $document->createElementNS(self::SVG, 'rect');
            foreach (['x' => $left, 'y' => $top, 'width' => $right - $left, 'height' => $bottom - $top, 'fill' => '#ffffff'] as $key => $value) {
                $rect->setAttribute($key, (string) $value);
            }
            $svg->appendChild($rect);
            $this->text($document, $svg, ['x' => $left, 'y' => ($top + $bottom) / 2, 'width' => $right - $left, 'max_width' => $right - $left, 'size' => 10, 'sy' => 10, 'font' => 'NotoSansArabic-Regular', 'color' => '#757575', 'rtl' => true], 'لا تتوفر إحصاءات', ['data-report-empty-statistics' => 'true'], true);
        }
        foreach ($page['charts'] ?? [] as $chart) {
            $chartDocument = new DOMDocument;
            if ($chartDocument->loadXML($chart, LIBXML_NONET | LIBXML_COMPACT)) {
                $node = $document->importNode($chartDocument->documentElement, true);
                $node->setAttribute('width', (string) $page['width']);
                $node->setAttribute('height', (string) $page['height']);
                $svg->appendChild($node);
            }
        }

        foreach ($page['groups'] as $role => $ids) {
            $fields = array_map(static fn (string $id): array => $page['fields'][$id], $ids);
            $field = $fields[0];
            $left = min(array_column($fields, 'x'));
            $right = max(array_map(static fn (array $field): float => $field['x'] + $field['width'], $fields));
            $field['x'] = $left;
            $field['width'] = $right - $left;
            $field['y'] = min(array_column($fields, 'y'));
            $layout = $page['layouts'][$role] ?? [];
            $field['max_width'] = $layout['max_width'] ?? $field['width'];
            if (isset($fieldLayouts[$role])) {
                $fitted = $fieldLayouts[$role];
                $field['sy'] *= $fitted['font_size'] / $field['size'];
                $field['size'] = $fitted['font_size'];
                $field['y'] = $fitted['y'];
                if (isset($layout['box'])) {
                    $field['x'] = $layout['box'][0];
                    $field['width'] = $layout['box'][2] - $layout['box'][0];
                }
            }
            $this->text($document, $svg, $field, $values[$role] ?? '-', ['data-report-field' => $role], $layout['center'] ?? false);
        }

        foreach ($tables as $id => $rows) {
            $table = $page['tables'][$id] ?? null;
            if ($table === null || count($rows) > count($table['rows'])) {
                throw new InvalidArgumentException('Invalid report table or capacity exceeded: '.$id);
            }
            $geometry = $tableLayouts[$id]['rows'] ?? [];
            if (($tableLayouts[$id]['dynamic'] ?? false) && $rows !== []) {
                $this->tableBody($document, $svg, $table, $page, $id, $geometry);
            }
            foreach ($rows as $rowIndex => $cells) {
                $slot = array_replace($table['rows'][$rowIndex], $geometry[$rowIndex] ?? []);
                foreach ($cells as $column => $value) {
                    $fieldId = $slot['cells'][$column]['fields'][0] ?? null;
                    if ($fieldId === null) {
                        continue;
                    }
                    $field = $page['fields'][$fieldId];
                    $field['x'] = $table['columns'][$column] + 4;
                    $field['width'] = $table['columns'][$column + 1] - $table['columns'][$column] - 8;
                    $field['y'] = $slot['top'] + ($slot['bottom'] - $slot['top']) / 2 + $field['sy'] * .32;
                    $field['rtl'] = preg_match('/[\x{0600}-\x{06ff}]/u', $value) === 1;
                    $this->textLayout ??= new AuctionReportTextLayout($this->root);
                    if ($this->textLayout->height(explode("\n", $value), $field['size'], $field['sy'], $field['font']) > $slot['bottom'] - $slot['top'] + 1) {
                        throw new InvalidArgumentException('A report field exceeds the available continuation page height.');
                    }
                    $this->text($document, $svg, $field, $value, ['data-report-cell' => $id.':'.$rowIndex.':'.$column], true, true);
                }
            }
        }

        return $document->saveXML($svg);
    }

    private function imageFit(array $item, string $section): string
    {
        if (in_array($item['fit'] ?? null, ['contain', 'cover'], true)) {
            return $item['fit'];
        }

        return $section === 'report_image' || in_array($item['kind'] ?? null, ['photo', 'artwork'], true) ? 'cover' : 'contain';
    }

    private function fitInFrame(DOMDocument $document, DOMXPath $xpath, array $frames, string $id, string $uri, string $media, int $pageNumber, string $fit = 'contain', array $item = []): void
    {
        foreach ($frames as $frame) {
            $query = $frame['clip'] === null ? '/*' : '//*[@clip-path="url(#report_'.$pageNumber.'_'.$frame['clip'].')"]';
            foreach ($xpath->query($query) as $container) {
                $sources = $xpath->query('.//*[@data-image="'.$id.'" and not(ancestor::*[local-name()="defs"])]', $container);
                if ($sources->length === 0) {
                    continue;
                }
                foreach ($sources as $source) {
                    $source->setAttribute('visibility', 'hidden');
                }
                [$left, $top, $right, $bottom] = $frame['box'];
                $attributes = ['x' => $left, 'y' => $top, 'width' => $right - $left, 'height' => $bottom - $top];
                foreach (['rect', 'image'] as $tag) {
                    $node = $document->createElementNS(self::SVG, $tag);
                    foreach ($attributes as $attribute => $value) {
                        $node->setAttribute($attribute, (string) $value);
                    }
                    if ($tag === 'rect') {
                        $node->setAttribute('fill', '#ffffff');
                    } else {
                        $node->setAttribute('preserveAspectRatio', 'xMidYMid meet');
                        $node->setAttribute('data-report-contained', $media);
                        $node->setAttribute('data-report-media', $media);
                        $node->setAttribute('data-report-attachment', (string) ($item['id'] ?? ''));
                        $node->setAttribute('data-document-page', (string) ($item['document_page'] ?? ''));
                        $node->setAttributeNS(self::XLINK, 'xlink:href', $uri);
                        if ($fit === 'cover') {
                            $node->removeAttribute('data-report-contained');
                            $node->setAttribute('data-report-cover', $media);
                            $this->coverImage($document, $node, $frame['box'], $item, 'report_'.$pageNumber.'_frame_'.$id.'_'.substr(hash('sha256', json_encode($frame)), 0, 8));
                        }
                    }
                    $container->appendChild($node);
                }
            }
        }
    }

    private function coverImage(DOMDocument $document, DOMElement $image, array $box, array $item, string $clipId): void
    {
        $uri = $item['uri'] ?? '';
        $bytes = str_contains($uri, ',') ? base64_decode(explode(',', $uri, 2)[1], true) : false;
        $size = $bytes === false ? false : @getimagesizefromstring($bytes);
        if ($size === false) {
            $image->setAttribute('preserveAspectRatio', 'xMidYMid slice');
            return;
        }
        [$left, $top, $right, $bottom] = $box;
        $width = $right - $left;
        $height = $bottom - $top;
        $scale = max($width / $size[0], $height / $size[1]);
        $focalX = max(0, min(100, (float) ($item['focal_x'] ?? 50))) / 100;
        $focalY = max(0, min(100, (float) ($item['focal_y'] ?? 50))) / 100;
        foreach (['x' => $left - ($size[0] * $scale - $width) * $focalX, 'y' => $top - ($size[1] * $scale - $height) * $focalY, 'width' => $size[0] * $scale, 'height' => $size[1] * $scale] as $key => $value) {
            $image->setAttribute($key, (string) $value);
        }
        $clip = $document->createElementNS(self::SVG, 'clipPath');
        $clip->setAttribute('id', $clipId);
        $rect = $document->createElementNS(self::SVG, 'rect');
        foreach (['x' => $left, 'y' => $top, 'width' => $width, 'height' => $height] as $key => $value) {
            $rect->setAttribute($key, (string) $value);
        }
        $clip->appendChild($rect);
        $document->documentElement->appendChild($clip);
        $image->setAttribute('clip-path', 'url(#'.$clipId.')');
        $image->setAttribute('preserveAspectRatio', 'none');
    }

    private function tableBody(DOMDocument $document, DOMElement $svg, array $table, array $page, string $id, array $rows): void
    {
        $fieldId = $table['rows'][0]['cells'][0]['fields'][0];
        $color = $page['fields'][$fieldId]['color'];
        $left = $table['columns'][0];
        $right = $table['columns'][array_key_last($table['columns'])];
        $top = $table['rows'][0]['top'];
        $bottom = $table['rows'][array_key_last($table['rows'])]['bottom'];
        $body = $document->createElementNS(self::SVG, 'rect');
        foreach (['data-reflowed-table' => $id, 'x' => $left, 'y' => $top, 'width' => $right - $left, 'height' => $bottom - $top, 'fill' => strtolower($color) === '#ffffff' ? '#205883' : '#ffffff', 'stroke' => $color, 'stroke-width' => .5] as $attribute => $value) {
            $body->setAttribute($attribute, (string) $value);
        }
        $svg->appendChild($body);
        foreach (array_slice($table['columns'], 1, -1) as $x) {
            $line = $document->createElementNS(self::SVG, 'line');
            foreach (['x1' => $x, 'x2' => $x, 'y1' => $top, 'y2' => $bottom, 'stroke' => $color, 'stroke-width' => .5] as $attribute => $value) {
                $line->setAttribute($attribute, (string) $value);
            }
            $svg->appendChild($line);
        }
        foreach ($rows as $row) {
            $line = $document->createElementNS(self::SVG, 'line');
            foreach (['x1' => $left, 'x2' => $right, 'y1' => $row['bottom'], 'y2' => $row['bottom'], 'stroke' => $color, 'stroke-width' => .5] as $attribute => $value) {
                $line->setAttribute($attribute, (string) $value);
            }
            $svg->appendChild($line);
        }
    }

    public function fontCss(): string
    {
        $this->fonts ??= new AuctionReportFonts($this->root);

        return $this->fonts->css();
    }

    public function page(string $design, int $index): array
    {
        if (! in_array($design, ['haram', 'asir'], true) || $index < 0) {
            throw new InvalidArgumentException('Invalid report artwork.');
        }
        $this->documents[$design] ??= require $this->path($design.'/pages.php');
        $this->bindings[$design] ??= require $this->path($design.'/bindings.php');

        $page = $this->documents[$design][$index] ?? throw new InvalidArgumentException('Unknown report page.');

        return array_replace($page, $this->bindings[$design][$index] ?? []);
    }

    private function text(DOMDocument $document, DOMElement $parent, array $field, string $value, array $attributes, bool $center = false, bool $centerVertically = false): void
    {
        if ($value === '') {
            return;
        }
        $size = $field['sy'];
        $x = $field['x'] + ($center ? $field['width'] / 2 : ($field['rtl'] ? $field['width'] : 0));
        $font = $field['font'];
        $this->fonts ??= new AuctionReportFonts($this->root);
        $face = $this->fonts->resolve($font);
        $text = $document->createElementNS(self::SVG, 'text');
        foreach ($attributes + [
            'x' => $x, 'y' => $field['y'], 'fill' => $field['color'], 'font-family' => $face['family'],
            'font-size' => $size, 'font-weight' => $face['weight'],
            'text-anchor' => $center ? 'middle' : 'start',
            'direction' => $field['rtl'] ? 'rtl' : 'ltr', 'unicode-bidi' => 'plaintext', 'font-kerning' => 'none',
            'data-report-width' => $field['max_width'] ?? $field['width'],
        ] as $key => $attribute) {
            $text->setAttribute($key, (string) $attribute);
        }
        if ($field['size'] !== $size) {
            $text->setAttribute('transform', 'translate('.$x.' 0) scale('.($field['size'] / $size).' 1) translate('.(-$x).' 0)');
        }
        $lines = explode("\n", $value);
        $text->setAttribute('y', (string) ($field['y'] - ($centerVertically ? (count($lines) - 1) * $size * .58 : 0)));
        foreach ($lines as $lineIndex => $line) {
            $span = $document->createElementNS(self::SVG, 'tspan');
            $span->setAttribute('x', (string) $x);
            $span->setAttribute('dy', (string) ($lineIndex === 0 ? 0 : $size * 1.17));
            $span->appendChild($document->createTextNode($line));
            $text->appendChild($span);
        }
        $parent->appendChild($text);
    }

    private function prefixIds(DOMDocument $document, string $prefix): void
    {
        $xpath = new DOMXPath($document);
        $ids = [];
        foreach ($xpath->query('//*[@id]') as $node) {
            $id = $node->getAttribute('id');
            $ids[$id] = $prefix.$id;
            $node->setAttribute('id', $ids[$id]);
        }
        foreach ($xpath->query('//*') as $node) {
            foreach ($node->attributes as $attribute) {
                if ($attribute->name === 'id') {
                    continue;
                }
                $value = preg_replace_callback('/url\(#([^)]+)\)/', static fn (array $match): string => 'url(#'.($ids[$match[1]] ?? $match[1]).')', $attribute->value);
                if (str_starts_with($value, '#') && isset($ids[substr($value, 1)])) {
                    $value = '#'.$ids[substr($value, 1)];
                }
                $attribute->value = $value;
            }
        }
    }

    private function uri(string $relative, string $mime): string
    {
        return $this->uris[$relative] ??= 'data:'.$mime.';base64,'.base64_encode(file_get_contents($this->path($relative)));
    }

    private function isImage(mixed $uri): bool
    {
        return is_string($uri) && preg_match('~^data:image/(?:png|jpeg|webp|gif);base64,[A-Za-z0-9+/=\r\n]+$~D', $uri) === 1;
    }

    private function path(string $relative): string
    {
        return ($this->root ?: resource_path('auction-reports')).'/'.$relative;
    }
}
