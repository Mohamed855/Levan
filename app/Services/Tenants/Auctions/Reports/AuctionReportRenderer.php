<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use InvalidArgumentException;

final class AuctionReportRenderer
{
    /** @param array<string, mixed> $snapshot */
    public function html(array $snapshot, string $type): string
    {
        if (! in_array($type, ['outputs', 'report'], true)) {
            throw new InvalidArgumentException('Unsupported auction report type.');
        }

        return view('tenant.auctions.reports.'.$type, ['data' => $snapshot])->render();
    }
}
