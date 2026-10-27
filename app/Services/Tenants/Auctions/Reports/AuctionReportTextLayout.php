<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

final class AuctionReportTextLayout
{
    private ?array $forms = null;

    public function __construct(private readonly string $root = '') {}

    /** @return list<string> */
    public function wrap(string $value, float $width, float $size, string $font): array
    {
        $file = $this->font($font);
        $lines = [];
        foreach (explode("\n", $value) as $paragraph) {
            if ($this->width($paragraph, $size, $file) <= $width * 1.1) {
                $lines[] = $paragraph;
                continue;
            }
            $line = '';
            foreach (preg_split('/\s+/u', trim($paragraph)) ?: [] as $word) {
                $candidate = $line === '' ? $word : $line.' '.$word;
                if ($this->width($candidate, $size, $file) <= $width) {
                    $line = $candidate;
                    continue;
                }
                if ($line !== '') {
                    $lines[] = $line;
                    $line = '';
                }
                foreach (preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $character) {
                    if ($line !== '' && $this->width($line.$character, $size, $file) > $width) {
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
            $width = $layout['max_width'] ?? $right - $left;
            $height = max(array_column($fields, 'y')) - min(array_column($fields, 'y')) + $field['sy'] * 1.5;
            $maxLines = $layout['max_lines'] ?? max(1, (int) floor($height / ($field['sy'] * 1.17)));
            $fieldChunks[$role] = array_map(static fn (array $lines): string => implode("\n", $lines), array_chunk(
                $this->wrap($content['values'][$role] ?? '-', $width, $field['size'], $field['font']), $maxLines,
            ));
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
                $copy['images'] = [];
            }
            $pages[] = $copy;
        }

        $extraRows = [];
        foreach ($pages[0]['tables'] as $id => $rows) {
            $table = $definition['tables'][$id];
            $normal = [];
            foreach ($rows as $row) {
                $slot = $table['rows'][count($normal)];
                $expanded = false;
                $wrapped = [];
                foreach ($row as $column => $value) {
                    $field = $definition['fields'][$slot['cells'][$column]['fields'][0]];
                    $lines = $this->wrap($value, $table['columns'][$column + 1] - $table['columns'][$column] - 8, $field['size'], $field['font']);
                    $expanded = $expanded || count($lines) * $field['sy'] * 1.17 > $slot['bottom'] - $slot['top'];
                    $wrapped[] = implode("\n", $lines);
                }
                if ($expanded) {
                    $extraRows[] = [$id, $wrapped];
                } else {
                    $normal[] = $wrapped;
                }
            }
            $pages[0]['tables'][$id] = $normal;
        }
        foreach ($extraRows as [$id, $row]) {
            $copy = $content;
            $copy['tables'] = array_fill_keys(array_keys($content['tables']), []);
            $copy['tables'][$id] = [$row];
            $copy['table_layouts'] = [$id => ['expanded' => true]];
            $copy['images'] = [];
            foreach ($fieldChunks as $role => $chunks) {
                $copy['values'][$role] = $chunks[0];
            }
            $pages[] = $copy;
        }

        return $pages;
    }

    private function width(string $value, float $size, string $font): float
    {
        if ($value === '') {
            return 0;
        }
        $bounds = imagettfbbox($size * .75, 0, $font, $this->shapeForMeasurement($value));

        return max($bounds[0], $bounds[2], $bounds[4], $bounds[6]) - min($bounds[0], $bounds[2], $bounds[4], $bounds[6]);
    }

    private function font(string $name): string
    {
        $file = match (true) {
            $name === 'NizarBBCKurdish' => 'nizar-bbc-kurdish.ttf',
            str_starts_with($name, 'GEContrast') => 'ge-contrast-bold.ttf',
            str_starts_with($name, 'IBMPlex') => 'ibm-plex-sans-arabic.ttf',
            default => 'noto-sans-arabic.ttf',
        };

        return ($this->root ?: resource_path('auction-reports')).'/fonts/'.$file;
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
