<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use InvalidArgumentException;

final class AuctionReportPaginator
{
    /** @param array<string, mixed> $templates */
    public function __construct(private readonly array $templates = []) {}

    /**
     * @param  array<string, mixed>  $snapshot
     * @return list<array{page_type: string, page_number: int, payload: array<string, mixed>}>
     */
    public function paginate(array $snapshot, string $type): array
    {
        $pages = match ($type) {
            'outputs' => $this->outputsPages($snapshot),
            'report' => $this->reportPages($snapshot),
            default => throw new InvalidArgumentException("Unsupported auction report type [{$type}]."),
        };

        return array_map(
            static fn (array $page, int $index): array => $page + ['page_number' => $index + 1],
            $pages,
            array_keys($pages),
        );
    }

    /** @param array<string, mixed> $snapshot
     * @return list<array{page_type: string, payload: array<string, mixed>}>
     */
    private function outputsPages(array $snapshot): array
    {
        $properties = $this->properties($snapshot);
        $capacities = $this->capacities('outputs');
        $pages = [$this->page('haram-cover')];
        foreach ($this->chunks($properties, $capacities['property_index']) as $index => $chunk) {
            $pages[] = $this->page($index === 0 ? 'haram-index' : 'haram-index-continuation', ['properties' => $chunk]);
        }
        $pages = array_merge($pages, $this->pairedResultPages('haram', $properties, $capacities));
        [$campaigns, $extraCampaigns] = $this->campaignParts($snapshot['marketing']['campaigns'] ?? []);
        $payload = ['campaigns' => $campaigns, 'x' => [], 'snapchat' => []];
        $remaining = [];
        foreach (['brochure', 'platform', 'video', 'x', 'whatsapp', 'social', 'marketing_evidence'] as $section) {
            foreach ($snapshot['evidence'][$section] ?? [] as $item) {
                $channel = $item['channel'] ?? match ($section) { 'x' => 'x', 'video' => 'youtube', 'whatsapp' => 'whatsapp', default => 'other' };
                if (in_array($channel, ['x', 'snapchat'], true) && $payload[$channel] === []) {
                    $payload[$channel] = [$item];
                    if ($section !== $channel) {
                        $payload[$section][] = $item;
                    }
                } else {
                    $remaining[$section][] = $item;
                }
            }
        }
        $pages[] = $this->page('haram-marketing', $payload);
        foreach ($remaining as $section => $items) {
            $pages = array_merge($pages, $this->galleryPages('haram-marketing-evidence-continuation', $section, $items));
        }
        $pages = array_merge($pages, $this->campaignPages('haram', $extraCampaigns));
        $before = $snapshot['evidence']['board_before'] ?? [];
        $after = $snapshot['evidence']['board_after'] ?? [];
        $pages[] = $this->page('haram-board-before', ['board_before' => array_slice($before, 0, 6), 'board_after' => array_slice($after, 0, 2)]);
        $pages = array_merge($pages,
            $this->galleryPages('haram-board-before-continuation', 'board_before', array_slice($before, 6)),
            $this->galleryPages('haram-board-after-continuation', 'board_after', array_slice($after, 2)),
            $this->nativeMediaPages('haram-brokers', 'brokers', $snapshot['evidence']['brokers'] ?? [], 3),
            [$this->page('haram-closing')],
        );
        return $pages;
    }

    /** @param array<string, mixed> $snapshot
     * @return list<array{page_type: string, payload: array<string, mixed>}>
     */
    private function reportPages(array $snapshot): array
    {
        $properties = $this->properties($snapshot);
        $capacities = $this->capacities('report');
        $pages = [$this->page('asir-cover')];

        foreach ($this->chunks($properties, $capacities['property_index']) as $index => $chunk) {
            $pages[] = $this->page($index === 0 ? 'asir-index' : 'asir-index-continuation', ['properties' => $chunk]);
        }

        foreach (['asir-introduction', 'asir-summary', 'asir-planning', 'asir-design', 'asir-promotion'] as $pageType) {
            $pages[] = $this->page($pageType);
        }

        $evidence = $snapshot['evidence'] ?? [];
        $pages = array_merge($pages, $this->boardPages('asir-board-installation', $evidence['board_before'] ?? [], 'board_before'));
        foreach (['brochure' => 24, 'platform' => 2, 'video' => 2, 'x' => 7, 'whatsapp' => 1] as $section => $capacity) {
            $pages = array_merge($pages, $this->nativeMediaPages('asir-'.$section, $section, $evidence[$section] ?? [], $capacity));
        }
        [$campaigns, $extraCampaigns] = $this->campaignParts($snapshot['marketing']['campaigns'] ?? [], ['snapchat', 'tiktok', 'x', 'youtube', 'whatsapp', 'google']);
        $social = [];
        $extraSocial = [];
        foreach ($evidence['social'] ?? [] as $item) {
            $slot = array_search($item['channel'] ?? null, ['snapchat', 'tiktok', 'x'], true);
            if ($slot !== false && ! isset($social[$slot])) {
                $social[$slot] = $item;
            } else {
                $extraSocial[] = $item;
            }
        }
        $pages = array_merge($pages,
            [$this->page('asir-social', ['social' => $social, 'campaigns' => $campaigns])],
            $this->galleryPages('asir-social-continuation', 'social', $extraSocial),
            $this->galleryPages('asir-marketing-evidence-continuation', 'marketing_evidence', $evidence['marketing_evidence'] ?? []),
            $this->campaignPages('asir', $extraCampaigns),
            $this->nativeMediaPages('asir-brokers', 'brokers', $evidence['brokers'] ?? [], 1),
            $this->boardPages('asir-board-removal', $evidence['board_after'] ?? [], 'board_after'),
        );

        foreach ($this->separateResultPages('asir', $properties, $capacities) as $page) {
            $pages[] = $page;
        }

        $pages[] = $this->page('asir-closing');

        return $pages;
    }

    /** @param list<array<string, mixed>> $properties
     * @param  array{sold_results: int, unsold_results: int}  $capacities
     * @return list<array{page_type: string, payload: array<string, mixed>}>
     */
    private function pairedResultPages(string $prefix, array $properties, array $capacities): array
    {
        $sold = $this->chunks($this->filterByStatus($properties, 'sold'), $capacities['sold_results']);
        $unsold = $this->chunks($this->filterByStatus($properties, 'unsold'), $capacities['unsold_results']);
        $count = max(count($sold), count($unsold));
        $pages = [];

        for ($index = 0; $index < $count; $index++) {
            $pages[] = $this->page($index === 0 ? "{$prefix}-results" : "{$prefix}-results-continuation", [
                'sold_properties' => $sold[$index] ?? [],
                'unsold_properties' => $unsold[$index] ?? [],
            ]);
        }

        return $pages;
    }

    /** @param list<array<string, mixed>> $properties
     * @param  array{sold_results: int, unsold_results: int}  $capacities
     * @return list<array{page_type: string, payload: array<string, mixed>}>
     */
    private function separateResultPages(string $prefix, array $properties, array $capacities): array
    {
        $pages = [];

        foreach ($this->chunks($this->filterByStatus($properties, 'sold'), $capacities['sold_results']) as $index => $chunk) {
            $pages[] = $this->page($index === 0 ? "{$prefix}-sold-assets" : "{$prefix}-sold-assets-continuation", ['properties' => $chunk]);
        }

        foreach ($this->chunks($this->filterByStatus($properties, 'unsold'), $capacities['unsold_results']) as $index => $chunk) {
            $pages[] = $this->page($index === 0 ? "{$prefix}-unsold-assets" : "{$prefix}-unsold-assets-continuation", ['properties' => $chunk]);
        }

        return $pages;
    }

    /** @param array<string, mixed> $snapshot
     * @return list<array<string, mixed>>
     */
    private function properties(array $snapshot): array
    {
        return array_values(is_array($snapshot['properties'] ?? null) ? $snapshot['properties'] : []);
    }

    /** @param list<array<string, mixed>> $properties
     * @return list<array<string, mixed>>
     */
    private function filterByStatus(array $properties, string $status): array
    {
        return array_values(array_filter($properties, static fn (array $property): bool => ($property['result_status'] ?? 'pending') === $status));
    }

    /** @param list<array<string, mixed>> $items
     * @return list<list<array<string, mixed>>>
     */
    private function chunks(array $items, int $capacity): array
    {
        $chunks = array_chunk($items, $capacity);

        return $chunks === [] ? [[]] : $chunks;
    }

    /** @return array{property_index: int, sold_results: int, unsold_results: int} */
    private function capacities(string $type): array
    {
        /** @var array{property_index: int, sold_results: int, unsold_results: int} $capacities */
        $templates = $this->templates ?: config('auction-report-templates');
        $capacities = $templates[$type]['capacities'];

        return $capacities;
    }

    private function boardPages(string $type, array $items, string $section): array
    {
        $pages = [$this->page($type.'-1', [$section => array_slice($items, 0, 6), 'offset' => 0])];
        foreach (array_chunk(array_slice($items, 6), 7) as $index => $chunk) {
            $pages[] = $this->page($type.'-2'.($index === 0 ? '' : '-continuation'), [$section => $chunk, 'offset' => 6 + $index * 7]);
        }

        return $pages;
    }

    private function nativeMediaPages(string $type, string $section, array $items, int $capacity): array
    {
        return array_merge(
            [$this->page($type, [$section => array_slice($items, 0, $capacity)])],
            $this->galleryPages($type.'-continuation', $section, array_slice($items, $capacity), $section === 'brokers' ? 1 : 6),
        );
    }

    private function galleryPages(string $type, string $section, array $items, int $capacity = 6): array
    {
        return array_map(fn (array $chunk): array => $this->page($type, [$section => $chunk, 'gallery' => $chunk, 'gallery_section' => $section]), array_chunk($items, $capacity));
    }

    private function campaignParts(array $campaigns, array $nativeChannels = ['snapchat', 'tiktok', 'x']): array
    {
        $counts = array_count_values(array_map(static fn (array $campaign): string => AuctionReportChannels::key($campaign['platform'] ?? null), $campaigns));
        $native = [];
        $extra = [];
        foreach ($campaigns as $campaign) {
            $channel = AuctionReportChannels::key($campaign['platform'] ?? null);
            if (in_array($channel, $nativeChannels, true) && $counts[$channel] === 1) {
                $native[] = $campaign;
            } else {
                $extra[] = $campaign;
            }
        }

        return [$native, $extra];
    }

    private function campaignPages(string $prefix, array $campaigns): array
    {
        // One campaign per appendix keeps every supplied metric and its own URL readable.
        return array_map(fn (array $campaign): array => $this->page($prefix.'-campaigns-continuation', ['gallery' => [], 'gallery_section' => 'campaigns', 'campaigns' => [$campaign]]), $campaigns);
    }

    /** @param array<string, mixed> $payload
     * @return array{page_type: string, payload: array<string, mixed>}
     */
    private function page(string $type, array $payload = []): array
    {
        return ['page_type' => $type, 'payload' => $payload];
    }
}
