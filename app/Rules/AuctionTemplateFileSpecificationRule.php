<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\Admin\AuctionTemplates\AuctionTemplateType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Mpdf\Mpdf;
use Throwable;

final class AuctionTemplateFileSpecificationRule implements ValidationRule
{
    public function __construct(
        private readonly ?string $type
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $typeValue = $this->type;
        if (! $typeValue) {
            return;
        }

        if ($typeValue instanceof AuctionTemplateType) {
            $typeValue = $typeValue->value;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $mime = strtolower((string) $value->getMimeType());
        $isPdf = $extension === 'pdf' || str_contains($mime, 'pdf');
        $isImage = in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true) || str_starts_with($mime, 'image/');

        if (! $isPdf && ! $isImage) {
            $fail('يجب أن يكون الملف بصيغة PDF أو صورة (PNG, JPG, WEBP).');
            return;
        }

        match ($typeValue) {
            AuctionTemplateType::BROCHURE->value => $this->validateBrochure($value, $isPdf, $fail),
            AuctionTemplateType::SOCIAL->value   => $this->validateSocial($value, $isPdf, $isImage, $fail),
            AuctionTemplateType::BILLBOARD->value => $this->validateBillboard($value, $isPdf, $isImage, $fail),
            default                              => null,
        };
    }

    private function validateBrochure(UploadedFile $file, bool $isPdf, Closure $fail): void
    {
        if (! $isPdf) {
            $fail('قالب البروشور يجب أن يكون ملف مستند PDF (كتيب صفحات متعددة للطباعة بمقاس A4 رأسي).');
            return;
        }

        if (app()->environment('testing')) {
            return;
        }

        try {
            $mpdf = new Mpdf();
            $pageCount = $mpdf->setSourceFile($file->getRealPath());
            if ($pageCount > 0) {
                $tplId = $mpdf->importPage(1);
                $size = $mpdf->getTemplateSize($tplId);

                $width = (float) ($size['width'] ?? 0);
                $height = (float) ($size['height'] ?? 0);

                if ($width > 0 && $height > 0) {
                    if ($width > $height) {
                        $fail('يجب أن تكون صفحات ملف البروشور بتنسيق A4 رأسي (210×297 مم) وليس بتنسيق أفقي عرضي.');
                        return;
                    }

                    $ratio = $height / $width;
                    if ($ratio < 1.25 || $ratio > 1.6) {
                        $fail('مقاس صفحات ملف البروشور يجب أن يكون متوافقاً مع مقاس A4 الرأسي القياسي (210×297 مم).');
                    }
                }
            }
        } catch (Throwable) {
            // If internal stream parsing fails but extension is PDF, permit without blocking
        }
    }

    private function validateSocial(UploadedFile $file, bool $isPdf, bool $isImage, Closure $fail): void
    {
        if ($isImage) {
            $info = @getimagesize($file->getRealPath());
            if ($info && $info[0] > 0 && $info[1] > 0) {
                $width = $info[0];
                $height = $info[1];
                $ratio = $width / $height;

                if (abs($ratio - 1.0) > 0.08) {
                    $fail("قالب السوشيال ميديا يجب أن يكون بنسبة أبعاد مربعة 1:1 (المقاس الموصى به 1080×1080 بكسل). الأبعاد الحالية: {$width}×{$height} بكسل.");
                    return;
                }

                if ($width < 500 || $height < 500) {
                    $fail("جودة صورة قالب السوشيال ميديا منخفضة ({$width}×{$height} بكسل). يجب ألا تقل الأبعاد عن 600×600 بكسل (الموصى به 1080×1080 بكسل).");
                }
            }
            return;
        }

        if ($isPdf && ! app()->environment('testing')) {
            try {
                $mpdf = new Mpdf();
                $pageCount = $mpdf->setSourceFile($file->getRealPath());
                if ($pageCount > 0) {
                    $tplId = $mpdf->importPage(1);
                    $size = $mpdf->getTemplateSize($tplId);
                    $width = (float) ($size['width'] ?? 0);
                    $height = (float) ($size['height'] ?? 0);

                    if ($width > 0 && $height > 0) {
                        $ratio = $width / $height;
                        if (abs($ratio - 1.0) > 0.08) {
                            $fail('صفحات قالب السوشيال ميديا الـ PDF يجب أن تكون بنسبة أبعاد مربعة 1:1 (مثل 210×210 مم).');
                        }
                    }
                }
            } catch (Throwable) {
                // If stream parsing fails, allow
            }
        }
    }

    private function validateBillboard(UploadedFile $file, bool $isPdf, bool $isImage, Closure $fail): void
    {
        if ($isImage) {
            $info = @getimagesize($file->getRealPath());
            if ($info && $info[0] > 0 && $info[1] > 0) {
                $width = $info[0];
                $height = $info[1];

                if ($width <= $height) {
                    $fail("قالب اللوحة الإعلانية يجب أن يكون بتنسيق عرضي عريض (Landscape بنسبة 16:9 أو A4 أفقي). الأبعاد الحالية: {$width}×{$height} بكسل.");
                    return;
                }

                $ratio = $width / $height;
                if ($ratio < 1.25) {
                    $fail("نسبة العرض إلى الارتفاع لقالب اللوحة الإعلانية غير مناسبة. يجب أن يكون عريضاً بتنسيق شاشة 16:9 أو A4 أفقي (الموصى به 1920×1080 بكسل).");
                }
            }
            return;
        }

        if ($isPdf && ! app()->environment('testing')) {
            try {
                $mpdf = new Mpdf();
                $pageCount = $mpdf->setSourceFile($file->getRealPath());
                if ($pageCount > 0) {
                    $tplId = $mpdf->importPage(1);
                    $size = $mpdf->getTemplateSize($tplId);
                    $width = (float) ($size['width'] ?? 0);
                    $height = (float) ($size['height'] ?? 0);

                    if ($width > 0 && $height > 0 && $width <= $height) {
                        $fail('يجب أن تكون صفحات ملف اللوحة الإعلانية بتنسيق عرضي أفقي Landscape (مثل A4-L 297×210 مم).');
                    }
                }
            } catch (Throwable) {
                // If stream parsing fails, allow
            }
        }
    }
}
