<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use InvalidArgumentException;

final class AuctionReportRenderer
{
    public function __construct(
        private readonly AuctionTemplateDataBuilder $dataBuilder = new AuctionTemplateDataBuilder(),
    ) {}

    /**
     * Render the high-fidelity reverse-engineered HTML for the specified report type.
     *
     * @param array<string, mixed> $snapshot
     */
    public function html(array $snapshot, string $type): string
    {
        ini_set('memory_limit', '512M');

        if (! in_array($type, ['outputs', 'report'], true)) {
            throw new InvalidArgumentException('Unsupported auction report type.');
        }

        $engineName = $type === 'outputs' ? 'haram' : 'asir';
        $enginePath = resource_path('views/tenant/auctions/reports/engines/' . $engineName . '.html');

        if (! file_exists($enginePath)) {
            // Fallback to blade view if engine file is missing
            return view('tenant.auctions.reports.' . $type, ['data' => $snapshot])->render();
        }

        $html = file_get_contents($enginePath);

        // Build the dynamic data payload
        $payload = $type === 'outputs'
            ? $this->dataBuilder->buildOutputsPayload($snapshot)
            : $this->dataBuilder->buildReportPayload($snapshot);

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // Inject the saved-data into the engine placeholder
        $html = str_replace(
            '<script id="saved-data" type="application/json">null</script>',
            '<script id="saved-data" type="application/json">' . $jsonPayload . '</script>',
            $html
        );

        // Inject the automated print-preparation script before </body>
        $readyScript = '
<script>
window.addEventListener("load", async () => {
    try {
        if (window.AuctionTemplate) {
            await window.AuctionTemplate.preparePrint();
            document.body.setAttribute("data-report-ready", "true");
        }
    } catch (e) {
        console.error("Print preparation error:", e);
        document.body.setAttribute("data-report-ready", "error");
    }
});
</script>
';
        $html = str_replace('</body>', $readyScript . '</body>', $html);

        return $html;
    }
}
