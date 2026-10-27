<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use InvalidArgumentException;

final class AuctionReportPaginator
{
    /** @param array<string, mixed> $templates */
    public function __construct(private readonly array $templates = [])
    {
    }

    /**
     * @param array<string, mixed> $snapshot
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
        $marketingEvidence = [];
        foreach (['marketing_evidence', 'brochure', 'platform', 'video', 'x', 'whatsapp', 'social'] as $section) {
            $marketingEvidence = array_merge($marketingEvidence, $snapshot['evidence'][$section] ?? []);
        }
        $pages = [$this->page('haram-cover')];

        foreach ($this->chunks($properties, $capacities['property_index']) as $index => $chunk) {
            $pages[] = $this->page($index === 0 ? 'haram-index' : 'haram-index-continuation', ['properties' => $chunk]);
        }

        foreach ($this->pairedResultPages('haram', $properties, $capacities) as $page) {
            $pages[] = $page;
        }

        $pages = array_merge($pages,
            $this->mediaPages('haram-marketing', [
                'campaigns' => $snapshot['marketing']['campaigns'] ?? [],
                'marketing_evidence' => $marketingEvidence,
            ], ['campaigns' => 3, 'marketing_evidence' => 2]),
            $this->mediaPages('haram-board-before', [
                'board_before' => $snapshot['evidence']['board_before'] ?? [],
                'board_after' => $snapshot['evidence']['board_after'] ?? [],
            ], ['board_before' => 6, 'board_after' => 2]),
            $this->mediaPages('haram-brokers', ['brokers' => $snapshot['evidence']['brokers'] ?? []], ['brokers' => 3]),
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
        foreach (['brochure' => 24, 'platform' => 3, 'video' => 5, 'x' => 8, 'whatsapp' => 2] as $section => $capacity) {
            $pages = array_merge($pages, $this->mediaPages('asir-'.$section, [$section => $evidence[$section] ?? []], [$section => $capacity]));
        }
        $pages = array_merge($pages,
            $this->mediaPages('asir-social', [
                'social' => array_merge($evidence['social'] ?? [], $evidence['marketing_evidence'] ?? []),
                'campaigns' => $snapshot['marketing']['campaigns'] ?? [],
            ], ['social' => 3, 'campaigns' => 3]),
            $this->mediaPages('asir-brokers', ['brokers' => $evidence['brokers'] ?? []], ['brokers' => 1]),
            $this->boardPages('asir-board-removal', $evidence['board_after'] ?? [], 'board_after'),
        );

        foreach ($this->separateResultPages('asir', $properties, $capacities) as $page) {
            $pages[] = $page;
        }

        $pages[] = $this->page('asir-closing');

        return $pages;
    }

    /** @param list<array<string, mixed>> $properties
     * @param array{sold_results: int, unsold_results: int} $capacities
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
     * @param array{sold_results: int, unsold_results: int} $capacities
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

    /** @param array<string, list<array<string, mixed>>> $sections
     * @param array<string, int> $capacities
     * @return list<array{page_type: string, payload: array<string, mixed>}>
     */
    private function mediaPages(string $type, array $sections, array $capacities): array
    {
        $chunks = [];
        foreach ($sections as $section => $items) {
            $chunks[$section] = $this->chunks(array_values($items), $capacities[$section]);
        }
        $count = max(array_map('count', $chunks));
        $pages = [];
        for ($index = 0; $index < $count; $index++) {
            $payload = [];
            foreach ($chunks as $section => $sectionChunks) {
                $payload[$section] = $sectionChunks[$index] ?? [];
            }
            $pages[] = $this->page($index === 0 ? $type : $type.'-continuation', $payload);
        }

        return $pages;
    }

    private function boardPages(string $type, array $items, string $section): array
    {
        $pages = [$this->page($type.'-1', [$section => array_slice($items, 0, 6), 'offset' => 0])];
        foreach ($this->chunks(array_slice($items, 6), 7) as $index => $chunk) {
            $pages[] = $this->page($type.'-2'.($index === 0 ? '' : '-continuation'), [$section => $chunk, 'offset' => 6 + $index * 7]);
        }

        return $pages;
    }

    /** @param array<string, mixed> $payload
     * @return array{page_type: string, payload: array<string, mixed>}
     */
    private function page(string $type, array $payload = []): array
    {
        return ['page_type' => $type, 'payload' => $payload];
    }
}
