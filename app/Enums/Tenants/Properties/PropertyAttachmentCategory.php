<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Properties;

enum PropertyAttachmentCategory: string
{
    case DEED_DOCUMENT = 'property_deed';
    case CADASTRAL_PLAN = 'property_cadastral_plan';
    case BUILDING_PERMIT = 'property_building_permit';
    case COORDINATES_STATEMENT = 'property_coordinates';
    case ADDITIONAL_DOCUMENT = 'property_additional_document';
    case ASSIGNMENT_LETTER_IMAGE = 'assignment_letter_image';
    case MAIN_IMAGE = 'property_main_image';
    case GALLERY_IMAGE = 'property_gallery';

    public function label(): string
    {
        return match ($this) {
            self::DEED_DOCUMENT => 'صك الملكية',
            self::CADASTRAL_PLAN => 'مخطط العقار',
            self::BUILDING_PERMIT => 'رخصة البناء',
            self::COORDINATES_STATEMENT => 'كشف الإحداثيات',
            self::ADDITIONAL_DOCUMENT => 'مستند إضافي',
            self::ASSIGNMENT_LETTER_IMAGE => 'صورة الإسناد',
            self::MAIN_IMAGE => 'الصورة الرئيسية للعقار',
            self::GALLERY_IMAGE => 'صورة العقار',
        };
    }
}
