<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

final class AuctionReportLinks
{
    public function safeUrl(?string $url): ?string
    {
        if ($url === null || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        $parts = parse_url($url);

        return ($parts['scheme'] ?? '') === 'https' && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']) ? $url : null;
    }

    public function destinations(array $snapshot): array
    {
        $marketing = $snapshot['marketing'] ?? [];
        $destinations = ['platform' => $snapshot['auction']['platform_url'] ?? null];
        foreach (['brochure' => 'brochure_url', 'video' => 'video_url', 'marketing_evidence' => 'evidence_url', 'boards' => 'boards_url'] as $role => $key) {
            $destinations[$role] = $marketing[$key] ?? null;
        }
        foreach ($marketing['links'] ?? [] as $link) {
            if (isset($link['role']) && $this->safeUrl($link['url'] ?? null) !== null && empty($destinations[$link['role']])) {
                $destinations[$link['role']] = $link['url'];
            }
        }
        foreach ($marketing['campaigns'] ?? [] as $campaign) {
            $channel = AuctionReportChannels::key($campaign['platform'] ?? null);
            if ($channel !== 'other' && empty($destinations[$channel])) {
                $destinations[$channel] = $campaign['url'] ?? null;
            }
        }
        $destinations['social'] = $marketing['contacts']['social'] ?? null;
        $destinations = array_map(fn ($url): ?string => $this->safeUrl(is_string($url) ? $url : null), $destinations);
        foreach (['phone', 'unified_phone'] as $key) {
            $number = strtr((string) ($marketing['contacts'][$key] ?? ''), array_combine(preg_split('//u', '٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY), range(0, 9)));
            $destinations[$key] = preg_match('/^\+?[0-9][0-9 ()-]{4,29}$/D', $number) ? 'tel:'.preg_replace('/[ ()-]/', '', $number) : null;
        }

        return $destinations;
    }

    public function forPage(array $snapshot, array $definition, array $fieldLayouts = [], array $pageDestinations = []): array
    {
        $destinations = $this->destinations($snapshot);
        foreach ($pageDestinations as $role => $url) {
            $destinations[$role] = $this->safeUrl(is_string($url) ? $url : null);
        }
        $links = [];
        foreach ($definition['link_fields'] ?? [] as $role => $fieldRole) {
            if (empty($destinations[$role])) {
                continue;
            }
            $fields = array_map(static fn (string $id): array => $definition['fields'][$id], $definition['groups'][$fieldRole] ?? []);
            if ($fields === []) {
                continue;
            }
            $left = max(0.0, (float) min(array_column($fields, 'x')));
            $top = max(0.0, min(array_map(static fn (array $field): float => $field['y'] - $field['sy'], $fields)));
            $right = min((float) $definition['width'], max(array_map(static fn (array $field): float => $field['x'] + $field['width'], $fields)));
            $bottom = min((float) $definition['height'], max(array_map(static fn (array $field): float => $field['y'] + $field['sy'] * .25, $fields)));
            if (isset($fieldLayouts[$fieldRole], $definition['layouts'][$fieldRole]['box'])) {
                [$left, $top, $right, $bottom] = array_map('floatval', $definition['layouts'][$fieldRole]['box']);
            }
            if ($right > $left && $bottom > $top) {
                $links[$role] = ['url' => $destinations[$role], 'box' => [$left, $top, $right, $bottom]];
            }
        }

        return $links;
    }
}
