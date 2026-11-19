@include('tenant.auctions.reports.partials.asir-header')
<main class="asir-marketing-main asir-boards-main" data-asir-flow>
    @if ($content['index'] < 16 && ($content['values']['board_context'] ?? '') !== '')
        <section data-asir-section><p class="asir-context" data-asir-paragraph data-report-field="board_context">{{ $content['values']['board_context'] }}</p></section>
    @endif
    @if ($content['gallery_items'] !== [])
        <section data-asir-section data-asir-gap-min="35"><div class="asir-board-grid" data-asir-grid>
            @foreach ($content['gallery_items'] as $offset => $image)
                <div class="asir-board-item" data-asir-item>
                    <span class="asir-board-number">{{ (new \App\Services\Tenants\Auctions\Reports\AuctionReportFormat)->arabicDigits(sprintf('%02d', $content['board_offset'] + $offset + 1)) }}</span>
                    @include('tenant.auctions.reports.partials.asir-image', ['image' => $image, 'frameClass' => 'asir-board-image', 'showCaption' => isset($image['document_page'])])
                </div>
            @endforeach
        </div></section>
    @endif
</main>
