<style>
    {!! $artwork->fontCss() !!}
    @page { size: {{ $dimensions['width'] }}pt {{ $dimensions['height'] }}pt; margin: 0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; background: #fff; }
    .auction-print-page { width: {{ $dimensions['width'] }}pt; height: {{ $dimensions['height'] }}pt; position: relative; break-after: page; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    .auction-print-page:last-child { break-after: auto; }
    .auction-print-page > svg { display: block; width: 100%; height: 100%; }
    .auction-report-link { position: absolute; display: block; z-index: 2; }
</style>
