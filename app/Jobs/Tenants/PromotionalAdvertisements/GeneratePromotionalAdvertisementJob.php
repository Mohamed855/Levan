<?php

declare(strict_types=1);

namespace App\Jobs\Tenants\PromotionalAdvertisements;

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\PromotionalAdvertisements\PromotionalAdvertisement;
use App\Services\Tenants\Auctions\AuctionTemplateRenderer;
use App\Services\Tenants\Shared\CentralAuctionTemplatesGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GeneratePromotionalAdvertisementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries = 2;

    public function __construct(
        private readonly string $tenantId,
        private readonly int $advertisementId,
    ) {}

    public function handle(
        CentralAuctionTemplatesGateway $centralGateway,
        AuctionTemplateRenderer $renderer,
        BrowserDocumentRenderer $browserRenderer,
    ): void {
        $previousTenant = tenancy()->initialized ? tenant() : null;
        $alreadyInitialized = $previousTenant && (string) $previousTenant->getTenantKey() === $this->tenantId;

        if (! $alreadyInitialized) {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            tenancy()->initialize($this->tenantId);
        }

        try {
            $this->generate($centralGateway, $renderer, $browserRenderer);
        } catch (Throwable $e) {
            Log::error('Promotional Advertisement generation failed: '.$e->getMessage(), [
                'advertisement_id' => $this->advertisementId,
                'trace' => $e->getTraceAsString(),
            ]);

            $ad = PromotionalAdvertisement::query()->find($this->advertisementId);
            $ad?->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        } finally {
            if (! $alreadyInitialized) {
                tenancy()->end();
                if ($previousTenant) {
                    tenancy()->initialize($previousTenant);
                }
            }
        }
    }

    private function generate(
        CentralAuctionTemplatesGateway $centralGateway,
        AuctionTemplateRenderer $renderer,
        BrowserDocumentRenderer $browserRenderer,
    ): void {
        /** @var PromotionalAdvertisement|null $ad */
        $ad = PromotionalAdvertisement::query()->find($this->advertisementId);
        if (! $ad) {
            return;
        }

        $ad->update(['status' => 'processing', 'error_message' => null]);

        $auction = Auction::query()->findOrFail($ad->auction_id);
        $templateData = $centralGateway->findActiveWithSchema($ad->auction_template_id, $ad->template_type);

        if (! $templateData || empty($templateData['schema'])) {
            throw new \RuntimeException('قالب المزاد المعتمد غير متوفر أو لم يُنشر له مخطط بعد.');
        }

        $schema = $templateData['schema'];
        $bindings = $renderer->bindings($auction);

        $html = view('components.shared.auctions.design-document', [
            'schema' => $schema,
            'bindings' => $bindings,
        ])->render();

        $tmpDir = storage_path('app/temp/promo_ads');
        File::ensureDirectoryExists($tmpDir);
        $tmpFile = $tmpDir.'/promo_'.$ad->id.'_'.uniqid().'.png';

        $generated = false;
        try {
            $browserRenderer->png($html, $tmpFile);
            $generated = file_exists($tmpFile) && filesize($tmpFile) > 0;
        } catch (Throwable $renderException) {
            Log::warning('Browsershot PNG export fallback: '.$renderException->getMessage());
        }

        if (! $generated) {
            // Fallback for environments where Chromium is not configured
            $width = (int) ($schema['canvas']['width'] ?? 1080);
            $height = (int) ($schema['canvas']['height'] ?? 1080);
            $this->createFallbackImage($tmpFile, $width, $height, $auction->name);
        }

        $relPath = 'auctions/advertisements/ad_'.$ad->id.'_'.uniqid().'.png';
        Storage::disk('tenant_public')->put($relPath, file_get_contents($tmpFile));
        @unlink($tmpFile);

        $ad->update([
            'status' => 'ready',
            'output_path' => $relPath,
            'template_name' => $templateData['name'] ?? $ad->template_name,
            'error_message' => null,
        ]);
    }

    private function createFallbackImage(string $path, int $width, int $height, string $title): void
    {
        if (function_exists('imagecreatetruecolor')) {
            $img = imagecreatetruecolor(max(100, $width), max(100, $height));
            $bg = imagecolorallocate($img, 18, 62, 51);
            $text = imagecolorallocate($img, 245, 215, 127);
            imagefill($img, 0, 0, $bg);
            imagestring($img, 5, 50, 50, 'LOT - '.mb_substr($title, 0, 30), $text);
            imagepng($img, $path);
            imagedestroy($img);
        } else {
            // Minimal 1x1 transparent PNG binary fallback
            file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
        }
    }
}
