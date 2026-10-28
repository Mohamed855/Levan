<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

final class AuctionReportFonts
{
    private array $catalog;

    public function __construct(private readonly string $root = '')
    {
        $this->catalog = require $this->root().'/fonts/catalog.php';
    }

    public function resolve(string $name): array
    {
        $face = $this->catalog[$name] ?? null;
        $matched = $face !== null && is_file($this->root().'/'.$face['path']);
        if (! $matched) {
            $face = $this->catalog[str_contains($name, 'Bold') ? 'NotoSansArabic-Bold' : 'NotoSansArabic-Regular'];
        }

        return $face + ['file' => $this->root().'/'.$face['path'], 'matched' => $matched];
    }

    public function faces(): array
    {
        return array_filter($this->catalog, fn (array $face): bool => is_file($this->root().'/'.$face['path']));
    }

    public function width(string $value, float $size, string $name): float
    {
        if ($value === '') {
            return 0;
        }
        $bounds = imagettfbbox($size * .75, 0, $this->resolve($name)['file'], $value);

        return max($bounds[0], $bounds[2], $bounds[4], $bounds[6]) - min($bounds[0], $bounds[2], $bounds[4], $bounds[6]);
    }

    public function css(): string
    {
        $css = '';
        foreach ($this->faces() as $face) {
            $otf = str_ends_with($face['path'], '.otf');
            $uri = 'data:font/'.($otf ? 'otf' : 'ttf').';base64,'.base64_encode(file_get_contents($this->root().'/'.$face['path']));
            $css .= '@font-face{font-family:'.$face['family'].';font-weight:'.$face['weight'].';src:url("'.$uri.'") format("'.($otf ? 'opentype' : 'truetype').'");font-display:block;}';
        }

        return $css;
    }

    private function root(): string
    {
        return $this->root ?: resource_path('auction-reports');
    }
}
