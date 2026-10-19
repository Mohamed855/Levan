<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions;

use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Shared\CentralAuctionTemplatesGateway;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use RuntimeException;
use Throwable;

final class AuctionOutputService
{
    public function __construct(
        private readonly CentralAuctionTemplatesGateway $gateway
    ) {}

    public function generateBrochure(Auction $auction): string
    {
        return $this->generate($auction, 'brochure', $auction->brochure_template_id);
    }

    public function generateBillboard(Auction $auction): string
    {
        return $this->generate($auction, 'billboard', $auction->billboard_template_id);
    }

    public function generateSocial(Auction $auction): string
    {
        return $this->generate($auction, 'social', $auction->social_template_id);
    }

    public function generate(Auction $auction, string $type, ?int $templateId): string
    {
        if (! $templateId) {
            throw new RuntimeException("لم يتم ربط قالب {$type} بهذا المزاد.");
        }

        $template = $this->gateway->findActive($templateId, $type);
        if (! $template) {
            throw new RuntimeException('القالب المرتبط غير نشط أو غير موجود في النظام.');
        }

        $templatePath = Storage::disk('central_public')->path($template->file_path);
        if (! file_exists($templatePath)) {
            throw new RuntimeException('ملف القالب غير موجود على الخادم.');
        }

        $outputDir = storage_path('app/outputs');
        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true, true);
        }

        $outputPath = $outputDir . DIRECTORY_SEPARATOR . "auction_{$auction->id}_{$type}_" . time() . '.pdf';

        try {
            $config = $this->getMpdfConfig($type);
            $mpdf = new Mpdf($config);
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;

            $ext = strtolower(pathinfo($templatePath, PATHINFO_EXTENSION));

            // If template is a PDF, import first page as background layer via FPDI
            if ($ext === 'pdf') {
                $mpdf->setSourceFile($templatePath);
                $tplId = $mpdf->importPage(1);
                $dimensions = $this->getPageDimensions($type);
                $mpdf->useTemplate($tplId, 0, 0, $dimensions['width'], $dimensions['height']);
            }

            // Prepare dynamic auction data
            $auction->loadMissing(['assignment', 'properties']);
            $qrCodeBase64 = $this->generateQrCodeDataUri($auction->platform_url ?: 'https://lot.sa');
            $coverImageBase64 = $this->getCoverImageBase64($auction);
            $tenantName = (string) (tenant()?->name ?? 'لوت LOT');

            $viewName = "tenants.auctions.outputs.{$type}";
            if (! view()->exists($viewName)) {
                $viewName = 'tenants.auctions.outputs.brochure';
            }

            $html = view($viewName, [
                'auction'          => $auction,
                'template'         => $template,
                'qrCodeBase64'     => $qrCodeBase64,
                'coverImageBase64' => $coverImageBase64,
                'tenantName'       => $tenantName,
                'templatePath'     => $templatePath,
                'isImageTemplate'  => in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true),
            ])->render();

            $mpdf->WriteHTML($html);
            $mpdf->Output($outputPath, 'F');

            return $outputPath;
        } catch (Throwable $e) {
            Log::error('PDF generation error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw new RuntimeException('حدث خطأ أثناء تصدير المخرج: ' . $e->getMessage());
        }
    }

    public function buildFieldData(Auction $auction, string $type): array
    {
        $auction->loadMissing(['assignment', 'properties']);

        return [
            'auction_name'       => (string) $auction->name,
            'auction_date'       => $auction->start_date ? $auction->start_date->format('Y/m/d') : '-',
            'auction_time'       => (string) ($auction->start_time ?? '-'),
            'auction_city'       => (string) ($auction->city ?? '-'),
            'platform_name'      => (string) ($auction->platform_name ?? '-'),
            'platform_url'       => (string) ($auction->platform_url ?? '-'),
            'supervising_entity' => (string) ($auction->supervising_entity ?? '-'),
            'assignment_number'  => (string) ($auction->assignment?->assignment_number ?? '-'),
            'properties_count'   => (string) $auction->properties->count(),
            'tenant_name'        => (string) (tenant()?->name ?? 'لوت LOT'),
            'auction_type'       => (string) ($auction->type_label ?? 'إلكتروني'),
        ];
    }

    private function getMpdfConfig(string $type): array
    {
        return match ($type) {
            'billboard' => [
                'mode'          => 'utf-8',
                'format'        => 'A4-L',
                'orientation'   => 'L',
                'margin_top'    => 12,
                'margin_bottom' => 12,
                'margin_left'   => 12,
                'margin_right'  => 12,
            ],
            'social' => [
                'mode'          => 'utf-8',
                'format'        => [210, 210],
                'orientation'   => 'P',
                'margin_top'    => 10,
                'margin_bottom' => 10,
                'margin_left'   => 10,
                'margin_right'  => 10,
            ],
            default => [
                'mode'          => 'utf-8',
                'format'        => 'A4',
                'orientation'   => 'P',
                'margin_top'    => 15,
                'margin_bottom' => 15,
                'margin_left'   => 15,
                'margin_right'  => 15,
            ],
        };
    }

    private function getPageDimensions(string $type): array
    {
        return match ($type) {
            'billboard' => ['width' => 297, 'height' => 210],
            'social'    => ['width' => 210, 'height' => 210],
            default     => ['width' => 210, 'height' => 297],
        };
    }

    private function generateQrCodeDataUri(string $url): string
    {
        try {
            $renderer = new ImageRenderer(
                new RendererStyle(140, 1),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $svg = $writer->writeString($url);

            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (Throwable $e) {
            Log::warning('QR generation error: ' . $e->getMessage());
            return '';
        }
    }

    private function getCoverImageBase64(Auction $auction): string
    {
        if (! $auction->cover_image) {
            return '';
        }

        try {
            $path = public_path($auction->cover_image);
            if (! file_exists($path)) {
                $path = Storage::disk('public')->path($auction->cover_image);
            }

            if (file_exists($path)) {
                $data = file_get_contents($path);
                $mime = mime_content_type($path) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode($data);
            }
        } catch (Throwable $e) {
            Log::warning('Cover image read error: ' . $e->getMessage());
        }

        return '';
    }
}
