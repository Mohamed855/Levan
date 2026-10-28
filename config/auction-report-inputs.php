<?php

declare(strict_types=1);

return [
    'channels' => ['x', 'tiktok', 'snapchat', 'youtube', 'whatsapp', 'google', 'other'],
    'sections' => ['brochure', 'platform', 'video', 'x', 'whatsapp', 'social', 'board_before', 'board_after', 'brokers', 'marketing_evidence', 'design_asset'],
    'kinds' => ['document', 'screenshot', 'photo', 'artwork'],
    'link_roles' => ['brochure', 'video', 'marketing_evidence', 'boards', 'platform', 'other'],
    'metadata' => ['channel', 'kind', 'display_name', 'link_role', 'fit', 'focal_x', 'focal_y'],
    'integer_metrics' => ['reach', 'impressions', 'engagements', 'clicks', 'views', 'posts', 'subscribers', 'messages', 'delivered', 'failed', 'pending', 'total', 'profile_views'],
    'decimal_metrics' => ['watch_hours'],
    'signed_metrics' => ['profile_change', 'reach_change', 'views_change'],
];
