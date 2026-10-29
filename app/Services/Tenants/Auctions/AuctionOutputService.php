<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions;

use App\Models\Tenants\Auctions\Auction;
use App\Services\Shared\Pdf\PdfFormFiller;
use App\Services\Shared\Pdf\PdfFormWidgetLocator;
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
        private readonly CentralAuctionTemplatesGateway $gateway,
        private readonly PdfFormFiller $pdfForms,
        private readonly PdfFormWidgetLocator $pdfWidgets,
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
        if (is_array($auction->template_snapshot[$type] ?? null)) {
            $outputDir = storage_path('app/outputs');
            if (! File::isDirectory($outputDir)) File::makeDirectory($outputDir, 0755, true, true);
            return app(AuctionSnapshotExporter::class)->pdf($auction, $type, $outputDir."/auction_{$auction->id}_{$type}_".time().'.pdf');
        }
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

        $outputPath = $outputDir.DIRECTORY_SEPARATOR."auction_{$auction->id}_{$type}_".time().'.pdf';

        if (strtolower(pathinfo($templatePath, PATHINFO_EXTENSION)) === 'pdf') {
            return $this->generateFillablePdf($auction, $type, $template, $templatePath, $outputPath);
        }

        if (in_array(strtolower(pathinfo($templatePath, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'webp'], true)) {
            return $this->generatePlacedImageTemplate($auction, $type, $template, $templatePath, $outputPath);
        }

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
            $auction->loadMissing(['assignment', 'properties.boundaries']);
            $qrCodeBase64 = $this->generateQrCodeDataUri($auction->platform_url ?: 'https://lot.sa');
            $coverImageBase64 = $this->getCoverImageBase64($auction);
            $tenantName = (string) (tenant()?->name ?? 'لوت LOT');

            $propertiesData = $auction->properties->map(function ($property) {
                return [
                    'id' => $property->id,
                    'name' => (string) $property->name,
                    'property_number' => (string) ($property->property_number ?? '-'),
                    'type_label' => method_exists($property->type, 'label') ? $property->type->label() : (string) ($property->type ?? 'عقار'),
                    'city' => (string) ($property->city ?? '-'),
                    'district' => (string) ($property->district ?? '-'),
                    'area' => $property->area ? number_format((float) $property->area, 2).' م²' : '-',
                    'deed_number' => (string) ($property->deed_number ?? '-'),
                    'deed_date' => $property->deed_date ? (string) $property->deed_date : '-',
                    'estimated_value' => $property->estimated_value ? number_format((float) $property->estimated_value, 2).' ر.س' : '-',
                    'main_image' => $this->resolveFileBase64($property->main_image),
                    'aerial_image' => $this->resolveFileBase64($property->aerial_image),
                    'boundaries' => $property->boundaries ?? collect(),
                    'short_description' => (string) ($property->short_description ?? ''),
                ];
            });

            $viewName = "tenant.auctions.outputs.{$type}";
            if (! view()->exists($viewName)) {
                $viewName = 'tenant.auctions.outputs.brochure';
            }

            $html = view($viewName, [
                'auction' => $auction,
                'propertiesData' => $propertiesData,
                'template' => $template,
                'qrCodeBase64' => $qrCodeBase64,
                'coverImageBase64' => $coverImageBase64,
                'tenantName' => $tenantName,
                'templatePath' => $templatePath,
                'isImageTemplate' => in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true),
            ])->render();

            $mpdf->WriteHTML($html);
            $mpdf->Output($outputPath, 'F');

            return $outputPath;
        } catch (Throwable $e) {
            Log::error('PDF generation error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw new RuntimeException('حدث خطأ أثناء تصدير المخرج: '.$e->getMessage());
        }
    }

    public function buildFieldData(Auction $auction, string $type): array
    {
        $auction->loadMissing(['assignment', 'properties']);

        $fieldData = [
            'auction_name' => (string) $auction->name,
            'auction_date' => $auction->start_date ? $auction->start_date->format('Y/m/d') : '-',
            'auction_time' => (string) ($auction->start_time ?? '-'),
            'auction_city' => (string) ($auction->city ?? '-'),
            'platform_name' => (string) ($auction->platform_name ?? '-'),
            'platform_url' => (string) ($auction->platform_url ?? '-'),
            'supervising_entity' => (string) ($auction->supervising_entity ?? '-'),
            'assignment_number' => (string) ($auction->assignment?->assignment_number ?? '-'),
            'issuing_entity' => (string) ($auction->assignment?->issuing_entity ?? '-'),
            'properties_count' => (string) $auction->properties->count(),
            'tenant_name' => (string) (tenant()?->name ?? 'لوت LOT'),
            'auction_type' => (string) ($auction->type_label ?? 'إلكتروني'),
        ];

        $fieldData['auction_category'] = (string) ($auction->category_label ?? '-');
        $fieldData['description'] = (string) ($auction->description ?? '-');
        $fieldData['notes'] = (string) ($auction->notes ?? '-');

        return $fieldData;
    }

    private function generateFillablePdf(
        Auction $auction,
        string $type,
        object $template,
        string $templatePath,
        string $outputPath,
    ): string {
        $temporaryDirectory = storage_path('app/outputs/pdf-forms/'.uniqid('', true));
        File::makeDirectory($temporaryDirectory, 0755, true, true);

        $filledPath = $temporaryDirectory.'/filled.pdf';
        $structurePath = $temporaryDirectory.'/structure.pdf';
        $overlayPath = $temporaryDirectory.'/images.pdf';

        try {
            if (! empty($template->field_definitions)) {
                $this->pdfForms->uncompress($templatePath, $structurePath);
                $this->createTextOverlay(
                    $this->pdfWidgets->pageSizes($structurePath),
                    $template->field_definitions,
                    $this->templateFieldData($auction, $type, $template),
                    $overlayPath,
                );
                $this->pdfForms->stamp($templatePath, $overlayPath, $outputPath);

                return $outputPath;
            }

            $this->pdfForms->fill($templatePath, $this->templateFieldData($auction, $type, $template), $filledPath);

            $imagePaths = $this->imageFieldPaths($auction, $template);
            if ($imagePaths === []) {
                File::copy($filledPath, $outputPath);

                return $outputPath;
            }

            $this->pdfForms->uncompress($templatePath, $structurePath);
            $this->createImageOverlay(
                $this->pdfWidgets->pageSizes($structurePath),
                $this->pdfWidgets->locate($structurePath),
                $imagePaths,
                $overlayPath,
            );
            $this->pdfForms->stamp($filledPath, $overlayPath, $outputPath);

            return $outputPath;
        } finally {
            File::deleteDirectory($temporaryDirectory);
        }
    }

    private function generatePlacedImageTemplate(
        Auction $auction,
        string $type,
        object $template,
        string $templatePath,
        string $outputPath,
    ): string {
        $imageSize = @getimagesize($templatePath);
        if ($imageSize === false || $imageSize[0] <= 0 || $imageSize[1] <= 0) {
            throw new RuntimeException('Unable to read the template image dimensions.');
        }

        $width = 210.0;
        $height = $width * ((float) $imageSize[1] / (float) $imageSize[0]);
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [$width, $height],
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_left' => 0,
            'margin_right' => 0,
        ]);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        $values = $this->templateFieldData($auction, $type, $template);
        $zones = array_filter($template->field_definitions ?? [], static fn (mixed $zone): bool => is_array($zone));
        $html = sprintf(
            '<div style="position:relative;width:%Fmm;height:%Fmm;"><img src="%s" style="position:absolute;left:0;top:0;width:%Fmm;height:%Fmm;" />',
            $width,
            $height,
            e(str_replace('\\', '/', $templatePath)),
            $width,
            $height,
        );

        foreach ($zones as $zone) {
            $key = $zone['key'] ?? null;
            if (! is_string($key) || ! array_key_exists($key, $values)) {
                continue;
            }

            $html .= sprintf(
                '<div style="position:absolute;left:%F%%;top:%F%%;width:%F%%;height:%F%%;overflow:hidden;display:flex;align-items:center;justify-content:center;text-align:center;direction:rtl;font-size:11pt;font-weight:bold;">%s</div>',
                ((float) ($zone['x'] ?? 0)) * 100,
                ((float) ($zone['y'] ?? 0)) * 100,
                ((float) ($zone['width'] ?? 0)) * 100,
                ((float) ($zone['height'] ?? 0)) * 100,
                e($values[$key]),
            );
        }

        $mpdf->WriteHTML($html.'</div>');
        $mpdf->Output($outputPath, 'F');

        return $outputPath;
    }

    /** @return array<string, string> */
    private function templateFieldData(Auction $auction, string $type, object $template): array
    {
        $available = array_flip(array_filter($template->field_names ?? [], 'is_string'));

        return array_intersect_key($this->buildFieldData($auction, $type), $available);
    }

    /** @return array<string, string> */
    private function imageFieldPaths(Auction $auction, object $template): array
    {
        $auction->loadMissing('properties');
        $properties = $auction->properties->values();
        $paths = [];

        foreach (array_filter($template->field_names ?? [], 'is_string') as $fieldName) {
            if ($fieldName === 'cover_image') {
                $path = $auction->cover_image ?: $properties->first()?->main_image;
            } elseif (preg_match('/^property_(\d+)_(?:main_)?image$/', $fieldName, $match)) {
                $path = $properties->get((int) $match[1] - 1)?->main_image;
            } else {
                continue;
            }

            if ($resolved = $this->resolveFilePath($path)) {
                $paths[$fieldName] = $resolved;
            }
        }

        return $paths;
    }

    /**
     * @param  list<array{float, float}>  $pageSizes
     * @param  array<string, list<array{page: int, rect: array{float, float, float, float}, page_size: array{float, float}}>>  $widgets
     * @param  array<string, string>  $imagePaths
     */
    private function createImageOverlay(array $pageSizes, array $widgets, array $imagePaths, string $outputPath): void
    {
        if ($pageSizes === []) {
            throw new RuntimeException('The PDF template does not contain readable pages.');
        }

        $first = $pageSizes[0];
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [$this->pointsToMillimeters($first[0]), $this->pointsToMillimeters($first[1])],
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_left' => 0,
            'margin_right' => 0,
        ]);

        foreach ($pageSizes as $index => [$width, $height]) {
            if ($index > 0) {
                $mpdf->AddPageByArray([
                    'sheet-size' => [$this->pointsToMillimeters($width), $this->pointsToMillimeters($height)],
                    'margin-top' => 0,
                    'margin-bottom' => 0,
                    'margin-left' => 0,
                    'margin-right' => 0,
                ]);
            }

            $html = '<div style="position:relative;width:100%;height:100%;">';
            foreach ($imagePaths as $fieldName => $imagePath) {
                foreach ($widgets[$fieldName] ?? [] as $widget) {
                    if ($widget['page'] !== $index + 1) {
                        continue;
                    }

                    [$left, $bottom, $right, $top] = $widget['rect'];
                    $html .= sprintf(
                        '<img src="%s" style="position:absolute;left:%Fmm;top:%Fmm;width:%Fmm;height:%Fmm;object-fit:cover;" />',
                        e($this->imageDataUri($imagePath)),
                        $this->pointsToMillimeters(min($left, $right)),
                        $this->pointsToMillimeters($height - max($top, $bottom)),
                        $this->pointsToMillimeters(abs($right - $left)),
                        $this->pointsToMillimeters(abs($top - $bottom)),
                    );
                }
            }
            $mpdf->WriteHTML($html.'</div>');
        }

        $mpdf->Output($outputPath, 'F');
    }

    /**
     * @param  list<array{float, float}>  $pageSizes
     * @param  list<array{key: string, page: int, x: float, y: float, width: float, height: float}>  $zones
     * @param  array<string, string>  $values
     */
    private function createTextOverlay(array $pageSizes, array $zones, array $values, string $outputPath): void
    {
        if ($pageSizes === []) {
            throw new RuntimeException('The PDF template does not contain readable pages.');
        }

        $first = $pageSizes[0];
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [$this->pointsToMillimeters($first[0]), $this->pointsToMillimeters($first[1])],
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_left' => 0,
            'margin_right' => 0,
        ]);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        foreach ($pageSizes as $index => [$width, $height]) {
            if ($index > 0) {
                $mpdf->AddPageByArray([
                    'sheet-size' => [$this->pointsToMillimeters($width), $this->pointsToMillimeters($height)],
                    'margin-top' => 0,
                    'margin-bottom' => 0,
                    'margin-left' => 0,
                    'margin-right' => 0,
                ]);
            }

            $html = '<div style="position:relative;width:100%;height:100%;">';
            foreach ($zones as $zone) {
                if (($zone['page'] ?? 1) !== $index + 1 || ! isset($values[$zone['key'] ?? ''])) {
                    continue;
                }

                $html .= sprintf(
                    '<div style="position:absolute;left:%F%%;top:%F%%;width:%F%%;height:%F%%;overflow:hidden;display:flex;align-items:center;justify-content:center;text-align:center;direction:rtl;font-size:11pt;font-weight:bold;">%s</div>',
                    ((float) $zone['x']) * 100,
                    ((float) $zone['y']) * 100,
                    ((float) $zone['width']) * 100,
                    ((float) $zone['height']) * 100,
                    e($values[$zone['key']]),
                );
            }
            $mpdf->WriteHTML($html.'</div>');
        }

        $mpdf->Output($outputPath, 'F');
    }

    private function imageDataUri(string $path): string
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Unable to read the auction image for the PDF output.');
        }

        return 'data:'.(mime_content_type($path) ?: 'image/jpeg').';base64,'.base64_encode($contents);
    }

    private function pointsToMillimeters(float $points): float
    {
        return $points * 25.4 / 72;
    }

    private function getMpdfConfig(string $type): array
    {
        return match ($type) {
            'billboard' => [
                'mode' => 'utf-8',
                'format' => 'A4-L',
                'orientation' => 'L',
                'margin_top' => 12,
                'margin_bottom' => 12,
                'margin_left' => 12,
                'margin_right' => 12,
            ],
            'social' => [
                'mode' => 'utf-8',
                'format' => [210, 210],
                'orientation' => 'P',
                'margin_top' => 10,
                'margin_bottom' => 10,
                'margin_left' => 10,
                'margin_right' => 10,
            ],
            default => [
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_left' => 15,
                'margin_right' => 15,
            ],
        };
    }

    private function getPageDimensions(string $type): array
    {
        return match ($type) {
            'billboard' => ['width' => 297, 'height' => 210],
            'social' => ['width' => 210, 'height' => 210],
            default => ['width' => 210, 'height' => 297],
        };
    }

    private function generateQrCodeDataUri(string $url): string
    {
        try {
            $renderer = new ImageRenderer(
                new RendererStyle(140, 1),
                new SvgImageBackEnd
            );
            $writer = new Writer($renderer);
            $svg = $writer->writeString($url);

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        } catch (Throwable $e) {
            Log::warning('QR generation error: '.$e->getMessage());

            return '';
        }
    }

    public function generateSocialImage(Auction $auction): string
    {
        if (! $auction->social_template_id) {
            throw new RuntimeException('لم يتم ربط قالب منصات التواصل بهذا المزاد.');
        }

        $template = $this->gateway->findActive($auction->social_template_id, 'social');
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

        $outputPath = $outputDir.DIRECTORY_SEPARATOR."auction_{$auction->id}_social_".time().'.png';

        // 1080x1080 canvas
        $canvas = imagecreatetruecolor(1080, 1080);
        $emerald = imagecolorallocate($canvas, 29, 79, 68); // #1D4F44
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $emerald);

        // Blend template image if image format
        $ext = strtolower(pathinfo($templatePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            $src = match ($ext) {
                'png' => @imagecreatefrompng($templatePath),
                'webp' => @imagecreatefromwebp($templatePath),
                default => @imagecreatefromjpeg($templatePath),
            };
            if ($src) {
                imagecopyresampled($canvas, $src, 0, 0, 0, 0, 1080, 1080, imagesx($src), imagesy($src));
                imagedestroy($src);
            }
        }

        // Blend cover image if present
        $coverPath = $this->resolveFilePath($auction->cover_image);
        if ($coverPath && file_exists($coverPath)) {
            $coverExt = strtolower(pathinfo($coverPath, PATHINFO_EXTENSION));
            $coverImg = match ($coverExt) {
                'png' => @imagecreatefrompng($coverPath),
                'webp' => @imagecreatefromwebp($coverPath),
                default => @imagecreatefromjpeg($coverPath),
            };
            if ($coverImg) {
                // Card border/background
                imagefilledrectangle($canvas, 80, 220, 1000, 780, $white);
                imagecopyresampled($canvas, $coverImg, 95, 235, 0, 0, 890, 530, imagesx($coverImg), imagesy($coverImg));
                imagedestroy($coverImg);
            }
        }

        imagepng($canvas, $outputPath, 9);
        imagedestroy($canvas);

        return $outputPath;
    }

    private function getCoverImageBase64(Auction $auction): string
    {
        return $this->resolveFileBase64($auction->cover_image);
    }

    private function resolveFileBase64(?string $path): string
    {
        if (! $path) {
            return '';
        }

        $fullPath = $this->resolveFilePath($path);
        if ($fullPath && file_exists($fullPath)) {
            try {
                $data = file_get_contents($fullPath);
                $mime = mime_content_type($fullPath) ?: 'image/jpeg';

                return 'data:'.$mime.';base64,'.base64_encode($data);
            } catch (Throwable $e) {
                Log::warning('Image read error: '.$e->getMessage());
            }
        }

        return '';
    }

    private function resolveFilePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $fullPath = public_path($path);
        if (file_exists($fullPath)) {
            return $fullPath;
        }

        $fullPath = Storage::disk('public')->path($path);
        if (file_exists($fullPath)) {
            return $fullPath;
        }

        $fullPath = Storage::disk('central_public')->path($path);
        if (file_exists($fullPath)) {
            return $fullPath;
        }

        return null;
    }
}
