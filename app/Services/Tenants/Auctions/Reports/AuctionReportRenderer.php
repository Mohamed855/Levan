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
        private readonly AuctionReportTextLayout $textLayout,
    ) {}

    /** @param array<string, mixed> $snapshot */
    public function html(array $snapshot, string $type): string
    {
        ini_set('memory_limit', '512M');

        if (! in_array($type, ['outputs', 'report'], true)) {
            throw new InvalidArgumentException('Unsupported auction report type.');
        }

        $design = $type === 'outputs' ? 'haram' : 'asir';
        $pageData = $type === 'outputs' ? $this->haramData : $this->asirData;
        $pages = [];
        foreach ($this->paginator->paginate($snapshot, $type) as $page) {
            $content = $pageData->page($snapshot, $page);
            foreach ($this->textLayout->flow($content, $this->artwork->page($design, $content['index'])) as $index => $part) {
                $pages[] = [
                    'page_type' => $page['page_type'].($index > 0 && ! str_ends_with($page['page_type'], '-continuation') ? '-continuation' : ''),
                    'page_number' => count($pages) + 1,
                    'content' => $part,
                ];
            }
        }

        return view('tenant.auctions.reports.'.$type, [
            'data' => $snapshot,
            'pages' => $pages,
            'artwork' => $this->artwork,
            'haramData' => $this->haramData,
            'asirData' => $this->asirData,
        ])->render();
    }
}
