<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use InvalidArgumentException;

final class AuctionReportRenderer
{
    public function __construct(
        private readonly AuctionReportPaginator $paginator,
        private readonly AuctionReportArtwork $artwork,
        private readonly HaramReportData $haramData,
        private readonly AsirReportData $asirData,
    ) {}

    /** @param array<string, mixed> $snapshot */
    public function html(array $snapshot, string $type): string
    {
        ini_set('memory_limit', '512M');

        if (! in_array($type, ['outputs', 'report'], true)) {
            throw new InvalidArgumentException('Unsupported auction report type.');
        }

        return view('tenant.auctions.reports.'.$type, [
            'data' => $snapshot,
            'pages' => $this->paginator->paginate($snapshot, $type),
            'artwork' => $this->artwork,
            'haramData' => $this->haramData,
            'asirData' => $this->asirData,
        ])->render();
    }
}
