<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Auctions;

enum SocialTemplate: string
{
    case POST = 'social_post';
    case STORY = 'social_story';

    public function label(): string
    {
        return match ($this) {
            self::POST => 'منشور مربع (Instagram / X)', self::STORY => 'ستوري عمودي (Snapchat / Story)'
        };
    }
}
