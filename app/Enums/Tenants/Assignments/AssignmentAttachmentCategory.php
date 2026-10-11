<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Assignments;

enum AssignmentAttachmentCategory: string
{
    case IMAGE = 'assignment_image';
    case LETTER = 'assignment_letter';
    case GENERAL_DOCUMENT = 'general_document';
    case ADDITIONAL_ATTACHMENT = 'additional_attachment';

    public function label(): string
    {
        return match ($this) {
            self::IMAGE => 'صورة الإسناد',
            self::LETTER => 'خطاب الإسناد',
            self::GENERAL_DOCUMENT => 'مستند عام',
            self::ADDITIONAL_ATTACHMENT => 'مرفق إضافي',
        };
    }
}
