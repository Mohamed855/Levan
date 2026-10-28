<?php

declare(strict_types=1);

namespace Tests\Support;

use LogicException;

final class AuctionReportDatabaseGuard
{
    /** @param array{central: string, tenant: string, tenant_id: string, url: string} $settings */
    public static function assertSafe(array $settings): void
    {
        if ($settings['central'] !== 'levan_report_ci_central'
            || $settings['tenant'] !== 'levan_report_ci_tenant'
            || $settings['tenant_id'] !== 'auction_report_ci'
            || $settings['url'] !== '') {
            throw new LogicException('Auction report tests require explicitly isolated databases (levan_report_ci_central / levan_report_ci_tenant), tenant auction_report_ci, and no DB_URL override.');
        }
    }
}
