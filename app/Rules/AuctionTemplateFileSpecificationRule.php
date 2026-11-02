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
        $isImage = in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true) || str_starts_with($mime, 'image/');

        if (! $isImage) {
            $fail('يجب أن يكون الملف صورة حصراً بصيغة (PNG, JPG, WEBP). لا يُقبل ملف PDF.');

            return;
        }

        match ($typeValue) {
            AuctionTemplateType::SOCIAL->value => $this->validateSocial($value, $fail),
            AuctionTemplateType::BILLBOARD->value => $this->validateBillboard($value, $fail),
            default => null,
        };
    }

    private function validateSocial(UploadedFile $file, Closure $fail): void
    {
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
    }

    private function validateBillboard(UploadedFile $file, Closure $fail): void
    {
        $info = @getimagesize($file->getRealPath());
        if ($info && $info[0] > 0 && $info[1] > 0) {
            $width = $info[0];
            $height = $info[1];

            if ($width <= $height) {
                $fail("قالب اللوحة الإعلانية يجب أن يكون بتنسيق عرضي عريض (Landscape بنسبة 16:9). الأبعاد الحالية: {$width}×{$height} بكسل.");

                return;
            }

            $ratio = $width / $height;
            if ($ratio < 1.25) {
                $fail('نسبة العرض إلى الارتفاع لقالب اللوحة الإعلانية غير مناسبة. يجب أن يكون عريضاً بتنسيق شاشة 16:9 (الموصى به 1200×628 أو 1920×1080 بكسل).');
            }
        }
    }
}
