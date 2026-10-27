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

    private array $uris = [];

    private ?AuctionReportTextLayout $textLayout = null;

    public function __construct(private readonly string $root = '') {}

    /** @param array<string, string> $values
     * @param  array<string, list<list<string>>>  $tables
     * @param  array<string, list<array<string, mixed>>>  $images
     */
    public function render(string $design, int $index, array $values, array $tables, array $images, int $pageNumber, array $tableLayouts = []): string
    {
        $page = $this->page($design, $index);
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
        foreach ($xpath->query('//*[@data-image-def]') as $definition) {
            $id = $definition->getAttribute('data-image-def');
            if (! isset($page['image_defs'][$id])) {
                continue;
            }
            $frame = $page['image_defs'][$id];
            $item = $images[$frame['section']][$frame['slot']] ?? [];
            $uri = $item['uri'] ?? null;
            $valid = $this->isImage($uri);
            $definition->setAttribute('preserveAspectRatio', $valid && ($item['fit'] ?? 'cover') !== 'contain' ? 'xMidYMid slice' : 'xMidYMid meet');
            $definition->setAttributeNS(self::XLINK, 'xlink:href', $valid ? $uri : $this->uri('fallback.svg', 'image/svg+xml'));
            $definition->setAttribute('data-report-media', $frame['section'].':'.$frame['slot']);
            foreach ($xpath->query('//*[@data-image="'.$id.'"]') as $usage) {
                $usage->setAttribute('data-report-media', $frame['section'].':'.$frame['slot']);
            }
            if (! $valid || ($item['fit'] ?? 'cover') === 'contain') {
                $this->fitInFrame($document, $xpath, $page['fallback_frames'][$id] ?? [], $id, $definition->getAttributeNS(self::XLINK, 'href'), $frame['section'].':'.$frame['slot'], $pageNumber);
            }
        }

        foreach ($page['images'] as $frame) {
            $item = $images[$frame['section']][$frame['slot']] ?? [];
            $uri = $item['uri'] ?? null;
            $valid = $this->isImage($uri);
            [$x, $y, $right, $bottom] = $frame['box'];
            $image = $document->createElementNS(self::SVG, 'image');
            foreach (['x' => $x, 'y' => $y, 'width' => $right - $x, 'height' => $bottom - $y] as $key => $value) {
                $image->setAttribute($key, (string) $value);
            }
            $image->setAttribute('preserveAspectRatio', $valid && ($item['fit'] ?? 'cover') !== 'contain' ? 'xMidYMid slice' : 'xMidYMid meet');
            $image->setAttribute('data-report-media', $frame['section'].':'.$frame['slot']);
            $image->setAttributeNS(self::XLINK, 'xlink:href', $valid ? $uri : $this->uri('fallback.svg', 'image/svg+xml'));
            $svg->appendChild($image);
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
            $this->text($document, $svg, $field, $values[$role] ?? '-', ['data-report-field' => $role], $layout['center'] ?? false);
        }

        foreach ($tables as $id => $rows) {
            $table = $page['tables'][$id] ?? null;
            if ($table === null || count($rows) > count($table['rows'])) {
                throw new InvalidArgumentException('Invalid report table or capacity exceeded: '.$id);
            }
            $expanded = $tableLayouts[$id]['expanded'] ?? false;
            if ($expanded && $rows !== []) {
                $this->expandedBody($document, $svg, $table, $page, $id);
            }
            foreach ($rows as $rowIndex => $cells) {
                $slot = $table['rows'][$rowIndex];
                if ($expanded) {
                    $slot['bottom'] = max($table['rows'][array_key_last($table['rows'])]['bottom'], $page['height'] - 60);
                }
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

    private function fitInFrame(DOMDocument $document, DOMXPath $xpath, array $frames, string $id, string $uri, string $media, int $pageNumber): void
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
                        $node->setAttributeNS(self::XLINK, 'xlink:href', $uri);
                    }
                    $container->appendChild($node);
                }
            }
        }
    }

    private function expandedBody(DOMDocument $document, DOMElement $svg, array $table, array $page, string $id): void
    {
        $fieldId = $table['rows'][0]['cells'][0]['fields'][0];
        $color = $page['fields'][$fieldId]['color'];
        $left = $table['columns'][0];
        $right = $table['columns'][array_key_last($table['columns'])];
        $top = $table['rows'][0]['top'];
        $bottom = max($table['rows'][array_key_last($table['rows'])]['bottom'], $page['height'] - 60);
        $body = $document->createElementNS(self::SVG, 'rect');
        foreach (['data-expanded-table' => $id, 'x' => $left, 'y' => $top, 'width' => $right - $left, 'height' => $bottom - $top, 'fill' => strtolower($color) === '#ffffff' ? '#205883' : '#ffffff', 'stroke' => $color, 'stroke-width' => .5] as $attribute => $value) {
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
    }

    public function fontCss(): string
    {
        $css = '';
        foreach (['NotoReport' => 'noto-sans-arabic.ttf', 'IBMReport' => 'ibm-plex-sans-arabic.ttf', 'GEReport' => 'ge-contrast-bold.ttf', 'NizarReport' => 'nizar-bbc-kurdish.ttf'] as $family => $file) {
            $css .= '@font-face{font-family:'.$family.';font-weight:100 900;src:url("'.$this->uri('fonts/'.$file, 'font/ttf').'") format("truetype");font-display:block;}';
        }
        foreach (require $this->path('fonts/catalog.php') as $font) {
            $css .= '@font-face{font-family:'.$font['family'].';src:url("'.$this->uri($font['path'], 'font/woff').'");font-display:block;}';
        }

        return $css;
    }

    public function page(string $design, int $index): array
    {
        if (! in_array($design, ['haram', 'asir'], true) || $index < 0) {
            throw new InvalidArgumentException('Invalid report artwork.');
        }
        $this->documents[$design] ??= require $this->path($design.'/pages.php');

        return $this->documents[$design][$index] ?? throw new InvalidArgumentException('Unknown report page.');
    }

    private function text(DOMDocument $document, DOMElement $parent, array $field, string $value, array $attributes, bool $center = false, bool $centerVertically = false): void
    {
        if ($value === '') {
            return;
        }
        $size = $field['sy'];
        $x = $field['x'] + ($center ? $field['width'] / 2 : ($field['rtl'] ? $field['width'] : 0));
        $font = $field['font'];
        $family = str_starts_with($font, 'GEContrast') ? 'GEReport' : (str_starts_with($font, 'IBMPlex') ? 'IBMReport' : 'NotoReport');
        if ($font === 'NizarBBCKurdish') {
            $family = 'NizarReport';
        } elseif (preg_match('/^[\p{N}\s.,:\/(\)%×*+\-]+$/u', $value)) {
            $catalog = require $this->path('fonts/catalog.php');
            $family = $catalog[$font]['family'] ?? $family;
        }
        $text = $document->createElementNS(self::SVG, 'text');
        foreach ($attributes + [
            'x' => $x, 'y' => $field['y'], 'fill' => $field['color'], 'font-family' => $family,
            'font-size' => $size, 'font-weight' => str_contains($font, 'Bold') ? 700 : 400,
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
