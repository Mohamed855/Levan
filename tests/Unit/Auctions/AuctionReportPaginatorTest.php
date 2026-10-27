<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportPaginator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AuctionReportPaginatorTest extends TestCase
{
    public function test_it_emits_the_seven_haram_reference_families_in_order(): void
    {
        $pages = $this->paginator()->paginate($this->snapshot(11, 4), 'outputs');

        self::assertSame([
            'haram-cover',
            'haram-index',
            'haram-results',
            'haram-marketing',
            'haram-board-before',
            'haram-brokers',
            'haram-closing',
        ], array_column($pages, 'page_type'));
        self::assertSame(range(1, 7), array_column($pages, 'page_number'));
        self::assertSame(range(1, 11), array_column($pages[1]['payload']['properties'], 'id'));
    }

    public function test_it_adds_a_haram_index_continuation_without_losing_or_duplicating_properties(): void
    {
        $pages = $this->paginator()->paginate($this->snapshot(12, 4), 'outputs');

        self::assertSame('haram-index-continuation', $pages[2]['page_type']);
        self::assertSame(range(1, 12), array_merge(
            array_column($pages[1]['payload']['properties'], 'id'),
            array_column($pages[2]['payload']['properties'], 'id'),
        ));
    }

    public function test_it_emits_the_twenty_one_asir_reference_families_for_the_reference_index_capacity(): void
    {
        $pages = $this->paginator()->paginate($this->snapshot(13), 'report');

        self::assertCount(21, $pages);
        self::assertSame('asir-cover', $pages[0]['page_type']);
        self::assertSame('asir-index', $pages[1]['page_type']);
        self::assertSame('asir-sold-assets', $pages[18]['page_type']);
        self::assertSame('asir-unsold-assets', $pages[19]['page_type']);
        self::assertSame('asir-closing', $pages[20]['page_type']);
        self::assertSame(range(1, 21), array_column($pages, 'page_number'));
    }

    public function test_it_rejects_an_unknown_document_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->paginator()->paginate($this->snapshot(0), 'editor');
    }

    public function test_outputs_preserve_all_evidence_and_campaigns_across_same_family_continuations(): void
    {
        $snapshot = $this->snapshot(0);
        $snapshot['marketing']['campaigns'] = array_map(static fn (int $id): array => ['id' => $id], range(1, 4));
        foreach (['board_before' => 7, 'board_after' => 3, 'marketing_evidence' => 3, 'brokers' => 4] as $section => $count) {
            $snapshot['evidence'][$section] = array_map(static fn (int $id): array => ['id' => $id], range(1, $count));
        }
        $pages = $this->paginator()->paginate($snapshot, 'outputs');
        foreach (['board_before' => 7, 'board_after' => 3, 'marketing_evidence' => 3, 'brokers' => 4] as $section => $count) {
            $ids = [];
            foreach ($pages as $page) {
                $ids = array_merge($ids, array_column($page['payload'][$section] ?? [], 'id'));
            }
            self::assertSame(range(1, $count), $ids, $section);
        }
        $campaignIds = [];
        foreach ($pages as $page) {
            $campaignIds = array_merge($campaignIds, array_column($page['payload']['campaigns'] ?? [], 'id'));
        }
        self::assertSame(range(1, 4), $campaignIds);
        self::assertSame(range(1, count($pages)), array_column($pages, 'page_number'));
    }

    public function test_report_media_boundaries_preserve_every_item_in_order(): void
    {
        foreach (['brochure' => 24, 'platform' => 3, 'video' => 5, 'x' => 8, 'whatsapp' => 2, 'social' => 3, 'brokers' => 1, 'board_before' => 13, 'board_after' => 13] as $section => $capacity) {
            foreach ([0, $capacity - 1, $capacity, $capacity + 1] as $count) {
                $snapshot = $this->snapshot(0);
                $items = $count === 0 ? [] : array_map(static fn (int $id): array => ['id' => $id], range(1, $count));
                $snapshot['evidence'][$section] = $items;
                $pages = $this->paginator()->paginate($snapshot, 'report');
                $actual = [];
                foreach ($pages as $page) {
                    $actual = array_merge($actual, $page['payload'][$section] ?? []);
                }
                self::assertSame($items, $actual, $section.':'.$count);
            }
        }
    }

    public function test_property_index_boundaries_preserve_order_in_both_designs(): void
    {
        foreach (['outputs' => ['haram-index', 11], 'report' => ['asir-index', 13]] as $type => [$family, $capacity]) {
            foreach ([0, $capacity - 1, $capacity, $capacity + 1] as $count) {
                $snapshot = $this->snapshot($count);
                $pages = array_values(array_filter($this->paginator()->paginate($snapshot, $type), static fn (array $page): bool => str_starts_with($page['page_type'], $family)));
                $actual = [];
                foreach ($pages as $page) {
                    $actual = array_merge($actual, $page['payload']['properties']);
                }
                self::assertSame($snapshot['properties'], $actual, $type.':'.$count);
                self::assertCount(max(1, (int) ceil($count / $capacity)), $pages);
            }
        }
    }

    public function test_outputs_include_evidence_classified_by_its_marketing_channel(): void
    {
        $snapshot = $this->snapshot(0);
        $snapshot['evidence']['platform'] = [['id' => 'platform']];
        $snapshot['evidence']['x'] = [['id' => 'x']];
        $snapshot['evidence']['social'] = [['id' => 'social']];
        $snapshot['evidence']['design_asset'] = [['id' => 'ornament']];
        $actual = [];
        foreach ($this->paginator()->paginate($snapshot, 'outputs') as $page) {
            $actual = array_merge($actual, $page['payload']['marketing_evidence'] ?? []);
        }
        self::assertSame(['platform', 'x', 'social'], array_column($actual, 'id'));
    }

    /** @return array<string, mixed> */
    private function snapshot(int $propertyCount, int $soldCount = 3): array
    {
        $properties = [];
        for ($id = 1; $id <= $propertyCount; $id++) {
            $properties[] = [
                'id' => $id,
                'number' => 'P-'.$id,
                'result_status' => $id <= $soldCount ? 'sold' : 'unsold',
            ];
        }

        return [
            'auction' => ['name' => 'مزاد اختبار'],
            'properties' => $properties,
            'marketing' => ['campaigns' => []],
            'evidence' => ['marketing_evidence' => [], 'board_before' => [], 'board_after' => [], 'brokers' => []],
        ];
    }

    private function paginator(): AuctionReportPaginator
    {
        /** @var array<string, mixed> $templates */
        $templates = require dirname(__DIR__, 3).'/config/auction-report-templates.php';

        return new AuctionReportPaginator($templates);
    }
}
