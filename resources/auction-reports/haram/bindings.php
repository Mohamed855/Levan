<?php

declare(strict_types=1);

return [
    0 => [
        'cover_overlay_color' => '#211727',
        'cover_panels' => [
            [496, 45, 552, 102],
            [58.5, 542, 194.5, 569], [201, 542, 259.5, 569],
            [268, 542, 401.5, 569], [411.5, 542, 545.5, 569],
        ],
        'groups' => [
            'name' => ['f3'], 'subtitle' => ['f4'], 'date_range' => ['f5'],
            'property_count' => ['f6'], 'manager' => ['f7'], 'project' => ['f8'], 'footer' => ['f14'],
        ],
        'images' => [],
        'layouts' => [
            'name' => ['box' => [70, 335, 535, 433], 'fit' => 'shrink', 'min_font_size' => 36, 'max_lines' => 2, 'center' => true],
            'subtitle' => ['max_width' => 350, 'max_lines' => 2],
            'manager' => ['max_width' => 85, 'max_lines' => 2],
            'project' => ['max_width' => 85, 'max_lines' => 2],
            'property_count' => ['max_width' => 35],
            'footer' => ['max_width' => 145],
        ],
    ],
    1 => [
        'groups' => ['manager' => ['f5'], 'name' => ['f6'], 'project' => ['f7'], 'end_date' => ['f11'], 'start_date' => ['f12'], 'footer' => ['f89']],
        'layouts' => ['manager' => ['max_width' => 150, 'center' => true], 'name' => ['max_width' => 150, 'center' => true], 'project' => ['max_width' => 150, 'center' => true], 'footer' => ['max_width' => 84.749, 'wrap_width' => 78.749, 'max_lines' => 2]],
        'images' => [],
    ],
    2 => [
        'groups' => ['narrative' => ['f3', 'f4', 'f5', 'f6', 'f7', 'f8', 'f9', 'f10', 'f11', 'f12'], 'footer' => ['f84']],
        'layouts' => ['narrative' => ['continue' => true], 'footer' => ['max_width' => 84.749, 'wrap_width' => 78.749, 'max_lines' => 2]],
        'images' => [],
    ],
    3 => [
        'link_fields' => ['x' => 'x_url', 'tiktok' => 'tiktok_url', 'snapchat' => 'snapchat_label', 'marketing_evidence' => 'marketing_url', 'boards' => 'boards_url'],
        'groups' => [
            'snapchat_label' => ['f3', 'f4', 'f5', 'f6'], 'tiktok_label' => ['f7', 'f8', 'f9', 'f10'], 'x_label' => ['f11', 'f12'],
            'snapchat_impressions' => ['f13'], 'tiktok_impressions' => ['f14'], 'x_impressions' => ['f15'],
            'x_url' => ['f22', 'f23'], 'tiktok_profile_views' => ['f40'], 'tiktok_reach' => ['f41'],
            'tiktok_views' => ['f42'], 'tiktok_profile_change' => ['f43'], 'tiktok_reach_change' => ['f44'],
            'tiktok_views_change' => ['f45'], 'date_range' => ['f47', 'f48'], 'tiktok_url' => ['f49', 'f50'],
            'snapchat_views' => ['f63'], 'marketing_url' => ['f65'], 'boards_url' => ['f68'], 'footer' => ['f69'],
        ],
        'layouts' => [
            'footer' => ['max_width' => 84.749, 'wrap_width' => 78.749, 'max_lines' => 2],
            'snapchat_impressions' => ['max_width' => 80], 'tiktok_impressions' => ['max_width' => 80], 'x_impressions' => ['max_width' => 80],
            'tiktok_profile_views' => ['max_width' => 50], 'tiktok_reach' => ['max_width' => 50], 'tiktok_views' => ['max_width' => 50],
            'tiktok_profile_change' => ['max_width' => 50], 'tiktok_reach_change' => ['max_width' => 50], 'tiktok_views_change' => ['max_width' => 50],
            'x_url' => ['max_width' => 150, 'max_lines' => 2], 'tiktok_url' => ['max_width' => 150, 'max_lines' => 2],
            'marketing_url' => ['max_width' => 150], 'boards_url' => ['max_width' => 100],
        ],
        'images' => [
            ['section' => 'x', 'slot' => 0, 'box' => [243.34, 196.87, 383.49, 339.20]],
            ['section' => 'snapchat', 'slot' => 0, 'box' => [371.29, 601.79, 466.72, 777.36]],
        ],
    ],
    4 => [
        'groups' => ['removal_date' => ['f6', 'f7'], 'footer' => ['f8']],
        'layouts' => ['footer' => ['max_width' => 84.749, 'wrap_width' => 78.749, 'max_lines' => 2]],
        'images' => [
            ['section' => 'board_before', 'slot' => 0, 'box' => [302.66, 119.78, 550.26, 244.33]],
            ['section' => 'board_before', 'slot' => 1, 'box' => [45.0, 119.78, 292.0, 244.33]],
            ['section' => 'board_before', 'slot' => 2, 'box' => [302.66, 252.5, 550.26, 377.2]],
            ['section' => 'board_before', 'slot' => 3, 'box' => [45.0, 252.5, 292.0, 377.2]],
            ['section' => 'board_before', 'slot' => 4, 'box' => [387.74, 388.58, 549.80, 492.12]],
            ['section' => 'board_before', 'slot' => 5, 'box' => [216.5, 388.58, 378.6, 492.12]],
            ['section' => 'board_after', 'slot' => 0, 'box' => [302.66, 540.5, 550.26, 665.2]],
            ['section' => 'board_after', 'slot' => 1, 'box' => [45.0, 540.5, 292.0, 665.2]],
        ],
    ],
    5 => [
        'groups' => ['broker_1' => ['f3', 'f4'], 'broker_2' => ['f1', 'f2'], 'broker_3' => ['f5'], 'footer' => ['f6']],
        'layouts' => ['footer' => ['max_width' => 84.749, 'wrap_width' => 78.749, 'max_lines' => 2]],
        'images' => [
            ['section' => 'brokers', 'slot' => 0, 'box' => [372.08, 148.35, 547.83, 365.97]],
            ['section' => 'brokers', 'slot' => 1, 'box' => [47.0, 148.35, 222.74, 365.97]],
            ['section' => 'brokers', 'slot' => 2, 'box' => [372.08, 497.60, 547.83, 715.22]],
        ],
    ],
    6 => [
        'link_fields' => ['phone' => 'phone', 'unified_phone' => 'unified_phone', 'social' => 'social'],
        'layouts' => [
            'footer' => ['max_width' => 145, 'max_lines' => 2],
            'phone' => ['box' => [130, 510, 470, 565], 'fit' => 'shrink', 'max_lines' => 1, 'min_font_size' => 28, 'center' => true],
            'unified_phone' => ['box' => [130, 570, 470, 625], 'fit' => 'shrink', 'max_lines' => 1, 'min_font_size' => 28, 'center' => true],
            'social' => ['box' => [140, 695, 455, 738], 'fit' => 'shrink', 'max_lines' => 2, 'min_font_size' => 10, 'center' => true],
        ],
        'groups' => ['phone' => ['f2'], 'unified_phone' => ['f3'], 'social' => ['f4'], 'footer' => ['f6']],
        'images' => [],
    ],
];
