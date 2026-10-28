<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use InvalidArgumentException;

final class AuctionReportTextLayout
{
    private ?array $forms = null;

    private ?array $numericMetrics = null;

    public function __construct(private readonly string $root = '') {}

    /** @return list<string> */
    public function wrap(string $value, float $width, float $size, string $font, bool $breakWords = true): array
    {
        $file = $this->font($font);
        $lines = [];
        foreach (explode("\n", $value) as $paragraph) {
            if ($this->width($paragraph, $size, $file, $font) <= $width * ($breakWords ? 1.1 : 1)) {
                $lines[] = $paragraph;

                continue;
            }
            $line = '';
            foreach (preg_split('/\s+/u', trim($paragraph)) ?: [] as $word) {
                $candidate = $line === '' ? $word : $line.' '.$word;
                if ($this->width($candidate, $size, $file, $font) <= $width) {
                    $line = $candidate;

                    continue;
                }
                if ($line !== '') {
                    $lines[] = $line;
                    $line = '';
                }
                if (! $breakWords) {
                    $line = $word;

                    continue;
                }
                foreach (preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $character) {
                    if ($line !== '' && $this->width($line.$character, $size, $file, $font) > $width) {
                        $lines[] = $line;
                        $line = '';
                    }
                    $line .= $character;
                }
            }
            $lines[] = $line;
        }

        return $lines;
    }

    /** @return list<array<string, mixed>> */
    public function flow(array $content, array $definition): array
    {
        $fieldChunks = [];
        foreach ($definition['groups'] as $role => $ids) {
            $fields = array_map(static fn (string $id): array => $definition['fields'][$id], $ids);
            $field = $fields[0];
            $left = min(array_column($fields, 'x'));
            $right = max(array_map(static fn (array $field): float => $field['x'] + $field['width'], $fields));
            $layout = $definition['layouts'][$role] ?? [];
            if (($layout['fit'] ?? null) === 'shrink') {
                $fitted = $this->fitTitle((string) ($content['values'][$role] ?? '-'), $field, $layout);
                $content['field_layouts'][$role] = $fitted;
                $fieldChunks[$role] = [implode("\n", $fitted['lines'])];

                continue;
            }
            $width = $layout['max_width'] ?? $right - $left;
            $height = max(array_column($fields, 'y')) - min(array_column($fields, 'y')) + $field['sy'] * 1.5;
            $maxLines = $layout['max_lines'] ?? max(1, (int) floor($height / ($field['sy'] * 1.17)));
            $height = max($height, $field['sy'] * (1.5 + ($maxLines - 1) * 1.17));
            $lines = $this->wrap($content['values'][$role] ?? '-', $layout['wrap_width'] ?? $width, $field['size'], $field['font']);
            if (count($lines) > $maxLines && ! ($layout['continue'] ?? false)) {
                // Atomic fields may fit or reject; only explicitly narrative fields paginate.
                try {
                    $fitted = $this->fitTitle((string) ($content['values'][$role] ?? '-'), $field, $layout + [
                        'box' => [$left, $field['y'] - $field['sy'], $left + $width, $field['y'] - $field['sy'] + $height],
                        'max_lines' => $maxLines,
                    ]);
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException($role.': '.$exception->getMessage(), previous: $exception);
                }
                $content['field_layouts'][$role] = $fitted;
                $lines = $fitted['lines'];
            }
            $fieldChunks[$role] = array_map(static fn (array $chunk): string => implode("\n", $chunk), array_chunk($lines, $maxLines));
        }
        $pages = [];
        $count = max(array_merge([1], array_values(array_map('count', $fieldChunks))));
        for ($index = 0; $index < $count; $index++) {
            $copy = $content;
            foreach ($fieldChunks as $role => $chunks) {
                $copy['values'][$role] = count($chunks) === 1 ? $chunks[0] : ($chunks[$index] ?? '');
            }
            if ($index > 0) {
                $copy['tables'] = array_fill_keys(array_keys($content['tables']), []);
                $copy['images'] = array_intersect_key($content['images'], ['report_image' => true]);
            }
            $pages[] = $copy;
        }

        $tables = [];
        foreach ($pages[0]['tables'] as $id => $rows) {
            $tables[$id] = $this->tableChunks($rows, $definition['tables'][$id], $definition);
        }
        $tableCount = max(array_merge([1], array_map('count', $tables)));
        for ($index = 0; $index < $tableCount; $index++) {
            $copy = $index === 0 ? $pages[0] : $content;
            $copy['tables'] = [];
            $copy['table_layouts'] = [];
            foreach ($tables as $id => $chunks) {
                $copy['tables'][$id] = $chunks[$index]['rows'] ?? [];
                $copy['table_layouts'][$id] = $chunks[$index]['layout'] ?? ['rows' => [], 'dynamic' => false];
            }
            if ($index === 0) {
                $pages[0] = $copy;
            } else {
                foreach ($fieldChunks as $role => $chunks) {
                    $copy['values'][$role] = $chunks[0];
                }
                $copy['images'] = array_intersect_key($content['images'], ['report_image' => true]);
                $pages[] = $copy;
            }
        }
        return $pages;
    }

    private function tableChunks(array $rows, array $table, array $definition): array
    {
        $top = $table['rows'][0]['top'];
        $bottom = $table['body_bottom'] ?? $table['rows'][array_key_last($table['rows'])]['bottom'];
        $chunks = [];
        $chunk = ['rows' => [], 'layout' => ['rows' => [], 'dynamic' => false]];
        $cursor = $top;
        foreach ($rows as $index => $row) {
            $slot = $table['rows'][min($index, count($table['rows']) - 1)];
            $height = $slot['bottom'] - $slot['top'];
            $wrapped = [];
            foreach ($row as $column => $value) {
                $field = $definition['fields'][$slot['cells'][$column]['fields'][0]];
                $lines = $this->wrap((string) $value, $table['columns'][$column + 1] - $table['columns'][$column] - 8, $field['size'], $field['font']);
                $height = max($height, $this->height($lines, $field['size'], $field['sy'], $field['font']) + 4);
                $wrapped[] = implode("\n", $lines);
            }
            if ($height > $bottom - $top) {
                throw new InvalidArgumentException('A report row exceeds the available table body height.');
            }
            if ($cursor + $height > $bottom + .01 && $chunk['rows'] !== []) {
                $chunks[] = $chunk;
                $chunk = ['rows' => [], 'layout' => ['rows' => [], 'dynamic' => false]];
                $cursor = $top;
            }
            $chunk['rows'][] = $wrapped;
            $chunk['layout']['rows'][] = ['top' => $cursor, 'bottom' => $cursor + $height];
            $chunk['layout']['dynamic'] = $chunk['layout']['dynamic'] || $height > $slot['bottom'] - $slot['top'] + .01;
            $cursor += $height;
        }
        $chunks[] = $chunk;

        return $chunks;
    }

    private function fitTitle(string $value, array $field, array $layout): array
    {
        [$left, $top, $right, $bottom] = $layout['box'];
        $maximum = (float) ($layout['max_font_size'] ?? $field['size']);
        $minimum = (float) ($layout['min_font_size'] ?? $maximum * .7);
        $file = $this->font($field['font']);
        for ($size = $maximum; $size >= $minimum - .001; $size = max($minimum, $size - .25)) {
            $verticalSize = $field['sy'] * $size / $field['size'];
            // Reserve 1pt per edge for FreeType/Chromium shaping differences.
            $availableWidth = max(1.0, $right - $left - 2);
            $lines = $this->wrap($value, $availableWidth, $size, $field['font'], false);
            $height = $this->height($lines, $size, $verticalSize, $field['font']);
            $width = max(array_map(fn (string $line): float => $this->width($line, $size, $file, $field['font']), $lines));
            if (count($lines) <= ($layout['max_lines'] ?? 2) && $width <= $availableWidth && $height <= $bottom - $top) {
                $bounds = imagettfbbox($size * .75, 0, $file, $this->shapeForMeasurement($lines[0]));
                $ascent = -min($bounds[1], $bounds[3], $bounds[5], $bounds[7]) * $verticalSize / $size;

                return ['font_size' => $size, 'lines' => $lines, 'y' => count($lines) === 1 ? $field['y'] : $top + ($bottom - $top - $height) / 2 + $ascent];
            }
            if ($size <= $minimum) {
                break;
            }
        }

        throw new InvalidArgumentException('العنوان أطول من المساحة المخصصة لسطرين عند الحد الأدنى المقروء. يرجى اختصار العنوان.');
    }

    private function width(string $value, float $size, string $font, string $name): float
    {
        if ($value === '') {
            return 0;
        }
        $this->numericMetrics ??= require ($this->root ?: resource_path('auction-reports')).'/fonts/numeric-metrics.php';
        $metrics = $name === 'NizarBBCKurdish' ? [] : ($this->numericMetrics[$name] ?? []);
        $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($metrics !== [] && array_diff($characters, array_keys($metrics)) === []) {
            return array_sum(array_map(static fn (string $character): float => $metrics[$character], $characters)) * $size;
        }
        $bounds = imagettfbbox($size * .75, 0, $font, $this->shapeForMeasurement($value));

        return max($bounds[0], $bounds[2], $bounds[4], $bounds[6]) - min($bounds[0], $bounds[2], $bounds[4], $bounds[6]);
    }

    /** @param list<string> $lines */
    public function height(array $lines, float $size, float $verticalSize, string $font): float
    {
        $ink = 0;
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $bounds = imagettfbbox($size * .75, 0, $this->font($font), $this->shapeForMeasurement($line));
            $ink = max($ink, (max($bounds[1], $bounds[3], $bounds[5], $bounds[7]) - min($bounds[1], $bounds[3], $bounds[5], $bounds[7])) * $verticalSize / $size);
        }

        return $ink + max(0, count($lines) - 1) * $verticalSize * 1.17;
    }

    private function font(string $name): string
    {
        return (new AuctionReportFonts($this->root))->resolve($name)['file'];
    }

    private function shapeForMeasurement(string $value): string
    {
        $this->forms ??= require ($this->root ?: resource_path('auction-reports')).'/fonts/arabic-forms.php';
        $letters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $result = '';
        foreach ($letters as $index => $letter) {
            $forms = $this->forms[$letter] ?? null;
            if ($forms === null) {
                $result .= $letter;

                continue;
            }
            $previous = $index - 1;
            while ($previous >= 0 && preg_match('/\p{Mn}/u', $letters[$previous])) {
                $previous--;
            }
            $next = $index + 1;
            while ($next < count($letters) && preg_match('/\p{Mn}/u', $letters[$next])) {
                $next++;
            }
            $before = $this->forms[$letters[$previous] ?? ''] ?? [];
            $after = $this->forms[$letters[$next] ?? ''] ?? [];
            $joinsBefore = isset($forms['final']) && isset($before['initial']);
            $joinsAfter = isset($forms['initial']) && isset($after['final']);
            $position = $joinsBefore ? ($joinsAfter ? 'medial' : 'final') : ($joinsAfter ? 'initial' : 'isolated');
            $result .= $forms[$position] ?? $letter;
        }

        return $result;
    }
}
