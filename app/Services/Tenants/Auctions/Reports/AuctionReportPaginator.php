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
        $pages = ($snapshot['cycle_version'] ?? 0) >= 1 ? $this->cyclePages($snapshot, $type) : match ($type) {
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
                $channel = $item['channel'] ?? match ($section) {
                    'x' => 'x', 'video' => 'youtube', 'whatsapp' => 'whatsapp', default => 'other'
                };
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

    private function cyclePages(array $snapshot, string $type): array
    {
        $prefix = match ($type) {
            'outputs' => 'haram', 'report' => 'asir', default => throw new InvalidArgumentException('Unsupported auction report type.')
        };
        $properties = $this->properties($snapshot);
        $capacities = $this->capacities($type);
        $pages = [$this->page($prefix.'-cover')];
        foreach ($this->chunks($properties, $capacities['property_index']) as $index => $chunk) {
            $pages[] = $this->page($prefix.'-index'.($index ? '-continuation' : ''), ['properties' => $chunk]);
        }
        $evidence = array_intersect_key($snapshot['evidence'] ?? [], array_flip(['brochure', 'platform', 'video', 'x', 'whatsapp', 'social', 'marketing_evidence', 'brokers', 'board_before', 'board_after']));
        $campaigns = $snapshot['marketing']['campaigns'] ?? [];
        $closed = ! empty($snapshot['closing_saved']);
        if ($type === 'outputs') {
            if ($closed && ($this->filterByStatus($properties, 'sold') !== [] || $this->filterByStatus($properties, 'unsold') !== [])) {
                $pages = array_merge($pages, $this->pairedResultPages($prefix, $properties, $capacities));
            }
            $groups = $this->channelRounds($campaigns, ['snapchat', 'tiktok', 'x']);
            foreach ($groups as $index => $group) {
                $items = $this->campaignEvidence($evidence, $group, false);
                $payload = ['campaigns' => $group, 'x' => [], 'snapchat' => []];
                foreach ($items as $section => $files) {
                    foreach ($files as $file) {
                        $channel = $file['channel'] ?? ($section === 'x' ? 'x' : null);
                        if (in_array($channel, ['x', 'snapchat'], true)) {
                            $payload[$channel][] = $file;
                        }
                    }
                }
                for ($slot = 0; $slot < max(1, count($payload['x']), count($payload['snapchat'])); $slot++) {
                    $pages[] = $this->page('haram-marketing', ['campaigns' => $group, 'x' => array_slice($payload['x'], $slot, 1), 'snapchat' => array_slice($payload['snapchat'], $slot, 1)]);
                }
            }
            $legacy = $this->campaignEvidence($evidence, [], true);
            $legacyX = $legacy['x'] ?? [];
            $legacySnap = array_values(array_filter($legacy['social'] ?? [], fn ($item) => ($item['channel'] ?? '') === 'snapchat'));
            $legacyCount = max(count($legacyX), count($legacySnap));
            $linkCampaigns = array_values(array_filter($campaigns, fn ($campaign) => in_array($campaign['platform'] ?? '', ['marketing_evidence', 'board_before'], true) && ! empty($campaign['url'])));
            foreach ($linkCampaigns as $campaign) {
                $pages[] = $this->page('haram-marketing', ['campaigns' => [$campaign], 'x' => [], 'snapchat' => []]);
            }
            if ($groups === [] && $linkCampaigns === [] && (! empty($snapshot['marketing']['evidence_url']) || ! empty($snapshot['marketing']['boards_url']))) {
                $legacyCount = max(1, $legacyCount);
            }
            for ($slot = 0; $slot < $legacyCount; $slot++) {
                $pages[] = $this->page('haram-marketing', ['campaigns' => [], 'x' => array_slice($legacyX, $slot, 1), 'snapchat' => array_slice($legacySnap, $slot, 1)]);
            }
            $before = array_chunk($evidence['board_before'] ?? [], 6);
            $after = $closed ? array_chunk($evidence['board_after'] ?? [], 2) : [];
            if ($closed && $after === [] && ! empty($snapshot['marketing']['board_removal_date'])) {
                $after = [[]];
            }
            for ($index = 0; $index < max(count($before), count($after)); $index++) {
                $pages[] = $this->page('haram-board-before'.($index ? '-continuation' : ''), ['board_before' => $before[$index] ?? [], 'board_after' => $after[$index] ?? []]);
            }
            $pages = array_merge($pages, $this->campaignMediaPages('haram-brokers', 'brokers', $campaigns, $evidence, 3, fn ($campaign) => ($campaign['platform'] ?? '') === 'brokers'));

            return array_merge($pages, [$this->page('haram-closing')]);
        }
        if (! empty($snapshot['marketing']['introduction']) || ! empty($snapshot['auction']['description'])) {
            $pages[] = $this->page('asir-introduction');
        }
        foreach (['summary' => ['city_context'], 'planning' => ['planning_methodology'], 'design' => ['name_context', 'brochure_methodology'], 'promotion' => ['audience_methodology', 'messages_methodology']] as $type => $fields) {
            foreach ($campaigns as $campaign) {
                if (($campaign['platform'] ?? '') === $type && array_filter(array_intersect_key($campaign, array_flip($fields)), fn ($value) => $value !== null && $value !== '') !== []) {
                    $pages[] = $this->page('asir-'.$type, ['campaigns' => [$campaign]]);
                }
            }
            if (array_filter(array_intersect_key($snapshot['marketing'] ?? [], array_flip($fields))) !== []) {
                $pages[] = $this->page('asir-'.$type, ['campaigns' => []]);
            }
        }
        foreach ($campaigns as $campaign) {
            if (($campaign['platform'] ?? '') !== 'board_before') {
                continue;
            }
            $items = $this->campaignEvidence($evidence, [$campaign], false)['board_before'] ?? [];
            if ($items !== [] || ! empty($campaign['board_context'])) {
                foreach ($this->boardPages('asir-board-installation', $items, 'board_before') as $page) {
                    $page['payload']['campaigns'] = [$campaign];
                    $pages[] = $page;
                }
            }
        }
        $legacyBoards = array_values(array_filter($evidence['board_before'] ?? [], fn ($file) => empty($file['campaign_id'])));
        if ($legacyBoards !== []) {
            $pages = array_merge($pages, $this->boardPages('asir-board-installation', $legacyBoards, 'board_before'));
        }
        $pages = array_merge($pages, $this->campaignMediaPages('asir-brochure', 'brochure', $campaigns, $evidence, 24, fn ($campaign) => ($campaign['platform'] ?? '') === 'brochure' || ($campaign['link_role'] ?? '') === 'brochure'));
        $platformCampaigns = array_values(array_filter($campaigns, fn ($campaign) => ($campaign['platform'] ?? '') === 'platform'));
        $pages = array_merge($pages, $this->campaignMediaPages('asir-platform', 'platform', $campaigns, $evidence, 2, fn ($campaign) => ($campaign['platform'] ?? '') === 'platform'));
        if ($platformCampaigns === [] && empty($evidence['platform']) && ((! empty($snapshot['auction']['platform_name']) && $snapshot['auction']['platform_name'] !== '-') || ! empty($snapshot['auction']['platform_url']))) {
            $pages[] = $this->page('asir-platform');
        }
        foreach (['youtube' => ['video', 2], 'x' => ['x', 7], 'whatsapp' => ['whatsapp', 1]] as $channel => [$section, $capacity]) {
            $pages = array_merge($pages, $this->campaignMediaPages('asir-'.$section, $section, $campaigns, $evidence, $capacity, fn ($campaign) => AuctionReportChannels::key($campaign['platform'] ?? null) === $channel));
        }
        $socialGroups = $this->channelRounds($campaigns, ['snapchat', 'tiktok', 'x']);
        if (array_filter($evidence['social'] ?? [], fn ($file) => empty($file['campaign_id'])) !== []) {
            $socialGroups[] = [];
        }
        foreach ($socialGroups as $index => $group) {
            if (count(array_filter($group, fn ($campaign) => ! empty($campaign['social_context']))) > 1) {
                foreach ($group as $campaign) {
                    $socialGroups[] = [$campaign];
                }
                unset($socialGroups[$index]);
            }
        }
        foreach ($socialGroups as $index => $group) {
            $items = $this->campaignEvidence($evidence, $group, $group === []);
            $bySlot = [];
            foreach ($items['social'] ?? [] as $file) {
                $slot = array_search($file['channel'] ?? null, ['snapchat', 'tiktok', 'x'], true);
                if ($slot !== false) {
                    $bySlot[$slot][] = $file;
                }
            }
            $hasMetric = count(array_filter($group, fn ($campaign) => ($campaign['views'] ?? $campaign['impressions'] ?? null) !== null)) > 0;
            $hasDescriptionOrLink = count(array_filter($group, fn ($campaign) => ! empty($campaign['social_context']) || ! empty($campaign['url']))) > 0;
            if (! $hasMetric && ! $hasDescriptionOrLink && $bySlot === []) {
                continue;
            }
            $count = max([1, ...array_map('count', $bySlot)]);
            for ($offset = 0; $offset < $count; $offset++) {
                $social = [];
                foreach ($bySlot as $slot => $files) {
                    if (isset($files[$offset])) {
                        $social[$slot] = $files[$offset];
                    }
                }
                $pages[] = $this->page('asir-social', ['campaigns' => $group, 'social' => $social]);
            }
        }
        $pages = array_merge($pages, $this->campaignMediaPages('asir-brokers', 'brokers', $campaigns, $evidence, 1, fn ($campaign) => ($campaign['platform'] ?? '') === 'brokers'));
        $googleCampaigns = array_values(array_filter($campaigns, fn ($campaign) => ($campaign['platform'] ?? '') === 'google'));
        foreach ($googleCampaigns as $campaign) {
            foreach ($this->boardPages('asir-board-removal', $closed ? ($evidence['board_after'] ?? []) : [], 'board_after') as $page) {
                $page['payload']['campaigns'] = [$campaign];
                $pages[] = $page;
            }
        }
        if ($googleCampaigns === [] && $closed && ! empty($evidence['board_after'])) {
            $pages = array_merge($pages, $this->boardPages('asir-board-removal', $evidence['board_after'], 'board_after'));
        }
        if ($closed) {
            foreach ($this->separateResultPages('asir', $properties, $capacities) as $page) {
                if ($page['payload']['properties'] !== []) {
                    $pages[] = $page;
                }
            }
        }

        return array_merge($pages, [$this->page('asir-closing')]);
    }

    private function channelRounds(array $campaigns, array $channels): array
    {
        $rounds = [];
        $counts = [];
        foreach ($campaigns as $campaign) {
            $channel = AuctionReportChannels::key($campaign['platform'] ?? null);
            if (! in_array($channel, $channels, true)) {
                continue;
            }
            $index = $counts[$channel] ?? 0;
            $counts[$channel] = $index + 1;
            $rounds[$index][] = $campaign;
        }
        ksort($rounds);

        return array_values($rounds);
    }

    private function campaignEvidence(array $evidence, array $campaigns, bool $includeLegacy): array
    {
        $ids = array_column($campaigns, 'id');
        $result = [];
        foreach ($evidence as $section => $files) {
            $result[$section] = array_values(array_filter($files, fn ($file) => ! empty($file['campaign_id']) ? in_array($file['campaign_id'], $ids, true) : $includeLegacy));
        }

        return $result;
    }

    private function campaignMediaPages(string $type, string $section, array $campaigns, array $evidence, int $capacity, callable $matches): array
    {
        $pages = [];
        $matched = array_values(array_filter($campaigns, $matches));
        foreach ($matched as $index => $campaign) {
            $items = $this->campaignEvidence($evidence, [$campaign], false)[$section] ?? [];
            if ($section === 'brochure' && ($campaign['link_role'] ?? '') === 'brochure') {
                foreach ($evidence as $files) {
                    foreach ($files as $file) {
                        if (isset($campaign['id']) && ($file['campaign_id'] ?? null) === $campaign['id']) {
                            $items[] = $file;
                        }
                    }
                }
                $items = array_values(array_unique($items, SORT_REGULAR));
            }
            $chunks = array_chunk($items, $capacity) ?: [[]];
            foreach ($chunks as $chunk) {
                $pages[] = $this->page($type, [$section => $chunk, 'campaigns' => [$campaign]]);
            }
        }
        $legacy = array_values(array_filter($evidence[$section] ?? [], fn ($file) => empty($file['campaign_id'])));
        foreach (array_chunk($legacy, $capacity) as $chunk) {
            $pages[] = $this->page($type, [$section => $chunk, 'campaigns' => []]);
        }

        return $pages;
    }

    private function repeatNativeMedia(string $type, string $section, array $items, int $capacity): array
    {
        return array_map(fn ($chunk) => $this->page($type, [$section => $chunk]), array_chunk($items, $capacity));
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
