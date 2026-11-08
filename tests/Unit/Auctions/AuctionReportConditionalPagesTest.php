<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportPaginator;
use PHPUnit\Framework\TestCase;

final class AuctionReportConditionalPagesTest extends TestCase
{
    public function test_internal_documents_never_become_legacy_campaign_evidence(): void
    {
        $snapshot = require dirname(__DIR__, 2).'/Fixtures/AuctionReports/haram.php';
        $snapshot['cycle_version'] = 1;
        $snapshot['marketing']['campaigns'] = [['id' => 1, 'platform' => 'x', 'views' => 0]];
        $snapshot['evidence'][''] = [['id' => 101, 'name' => 'INTERNAL DOCUMENT', 'uri' => 'data:image/png;base64,aW50ZXJuYWw=']];
        foreach (['outputs', 'report'] as $type) {
            self::assertStringNotContainsString('INTERNAL DOCUMENT', json_encode((new AuctionReportPaginator(require dirname(__DIR__, 3).'/config/auction-report-templates.php'))->paginate($snapshot, $type)));
        }
    }

    private function snapshot(array $campaigns = [], bool $closed = false): array
    {
        return ['cycle_version' => 1, 'auction' => ['name' => 'مزاد'], 'properties' => [], 'marketing' => ['campaigns' => $campaigns], 'evidence' => [], 'closing_saved' => $closed];
    }

    public function test_unsaved_campaigns_and_results_do_not_get_placeholder_pages(): void
    {
        $paginator = new AuctionReportPaginator(require dirname(__DIR__, 3).'/config/auction-report-templates.php');
        foreach (['outputs', 'report'] as $type) {
            $pages = $paginator->paginate($this->snapshot(), $type);
            foreach ($pages as $page) {
                self::assertDoesNotMatchRegularExpression('/marketing|campaigns|sold-assets|unsold-assets|results|whatsapp|video|social|brokers|board-/', $page['page_type']);
            }
        }
    }

    public function test_each_saved_campaign_keeps_its_own_zero_metrics_and_url(): void
    {
        $paginator = new AuctionReportPaginator(require dirname(__DIR__, 3).'/config/auction-report-templates.php');
        $campaigns = [['id' => 1, 'platform' => 'x', 'views' => 0, 'url' => 'https://example.com/a'], ['id' => 2, 'platform' => 'x', 'views' => 7, 'url' => 'https://example.com/b']];
        foreach (['outputs', 'report'] as $type) {
            $pages = $paginator->paginate($this->snapshot($campaigns), $type);
            $campaignPages = array_values(array_filter($pages, fn ($p) => $p['page_type'] === ($type === 'outputs' ? 'haram-marketing' : 'asir-x')));
            self::assertCount(2, $campaignPages);
            self::assertSame($campaigns[0], $campaignPages[0]['payload']['campaigns'][0]);
            self::assertSame($campaigns[1], $campaignPages[1]['payload']['campaigns'][0]);
        }
    }
}
