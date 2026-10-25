<?php

declare(strict_types=1);

namespace App\Support\Auctions;

final class AuctionFontRegistry
{
    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return config('auction-template-fonts.families', []);
    }

    /** @return array<string, mixed> */
    public function find(string $slug): array
    {
        $font = $this->all()[$slug] ?? null;
        return is_array($font) ? $font + ['slug' => $slug] : [];
    }

    public function supportsWeight(string $slug, int $weight): bool
    {
        $font = $this->find($slug);
        return in_array($weight, $font['weights'] ?? [], true);
    }

    /** @param list<string> $slugs */
    public function cssFor(array $slugs): string
    {
        $css = [];
        foreach (array_values(array_unique($slugs)) as $slug) {
            $font = $this->find($slug);
            if ($font === []) {
                continue;
            }
            foreach ($font['weights'] as $weight) {
                $path = $font['files'][$weight] ?? null;
                if (! is_string($path)) {
                    continue;
                }
                $url = '/'.ltrim(str_replace('resources/assets/', 'assets/', $path), '/');
                $family = addslashes((string) $font['css_family']);
                $css[] = "@font-face{font-family: '{$family}';font-style: normal;font-weight: {$weight};font-display: swap;src: url('{$url}') format('woff2');}";
            }
        }
        return implode('', $css);
    }
}
