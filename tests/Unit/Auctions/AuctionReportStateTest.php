<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportState;
use Tests\TestCase;

final class AuctionReportStateTest extends TestCase
{
    public function test_one_campaign_never_completes_all_configured_sections(): void
    {
        self::assertSame('incomplete', (new AuctionReportState)->fromData([['channel' => 'google', 'metrics' => ['views' => 0]]], false, [])->value);
    }

    public function test_closure_does_not_change_marketing_completeness(): void
    {
        self::assertSame('incomplete', (new AuctionReportState)->fromData([], true, ['sold'])->value);
    }

    public function test_all_required_fields_complete_sections_without_optional_evidence(): void
    {
        $campaigns = [];
        foreach (config('auction-marketing-campaigns') as $channel => $fields) {
            $metrics = [];
            foreach ($fields as $field) {
                $metrics[$field] = isset(config('auction-marketing-fields.text')[$field]) ? 'Campaign details' : ($field === 'property_id' ? 1 : 0);
            }
            $campaigns[] = ['channel' => $channel, 'metrics' => $metrics, 'url' => 'https://example.com', 'campaign_date' => '2026-11-09'];
        }
        $state = new AuctionReportState;
        self::assertSame('complete', $state->fromData($campaigns, false, [])->value);
        $campaigns[0]['metrics']['planning_methodology'] = ' ';
        self::assertSame('incomplete', $state->fromData($campaigns, false, [])->value);
    }
}
