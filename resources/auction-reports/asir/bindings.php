<?php

declare(strict_types=1);

return [
    0 => ['groups' => ['name' => ['f0'], 'subtitle' => ['f1']]],
    1 => ['groups' => [
        'manager' => ['f3'], 'name' => ['f4'], 'project' => ['f5'],
        'end_weekday' => ['f9'], 'start_weekday' => ['f11'], 'end_month' => ['f12'], 'start_month' => ['f13'],
        'end_hijri' => ['f14'], 'start_hijri' => ['f15'], 'start_mark' => ['f16'],
        'end_year' => ['f17'], 'start_year' => ['f18'], 'end_time' => ['f19', 'f23'],
        'start_time' => ['f20'], 'end_day' => ['f21'], 'start_day' => ['f22'], 'duplicate_header' => ['f24'],
    ]],
    2 => ['groups' => ['introduction' => ['f0', 'f1', 'f2', 'f3', 'f4', 'f5', 'f6']]],
    3 => ['groups' => ['city_context' => ['f6']]],
    4 => ['groups' => []],
    5 => ['groups' => ['name_context' => ['f2']]],
    6 => ['groups' => []],
    7 => ['groups' => ['board_2' => ['f6'], 'board_1' => ['f7'], 'board_4' => ['f8'], 'board_3' => ['f9'], 'board_6' => ['f10'], 'board_5' => ['f11']]],
    8 => ['groups' => ['board_8' => ['f6'], 'board_7' => ['f7'], 'board_10' => ['f8'], 'board_9' => ['f9'], 'board_12' => ['f10'], 'board_11' => ['f11'], 'board_13' => ['f12']],
        'image_slots' => ['i8' => 0, 'i9' => 1, 'i2' => 2, 'i3' => 3, 'i4' => 4, 'i5' => 5, 'i6' => 5, 'i7' => 6]],
    9 => ['groups' => ['brochure_url' => ['f7']]],
    10 => ['groups' => [
        'platform_context' => ['f5', 'f6'], 'auction_type' => ['f7'], 'name' => ['f9', 'f10'],
        'duration' => ['f12'], 'property_count' => ['f13'], 'date_range' => ['f14'], 'end_time_short' => ['f15'],
        'status' => ['f16'], 'platform_url' => ['f17'],
    ], 'layouts' => ['auction_type' => ['max_width' => 80], 'property_count' => ['max_width' => 80], 'end_time_short' => ['max_width' => 110]]],
    11 => ['groups' => [
        'youtube_views' => ['f5'], 'auction_type' => ['f7'], 'name' => ['f9'],
        'youtube_subscribers' => ['f14'], 'youtube_watch_hours' => ['f15'], 'youtube_views_2' => ['f16'],
        'chart_1' => ['f17'], 'chart_2' => ['f18'], 'chart_3' => ['f19'], 'video_url' => ['f20'],
    ], 'layouts' => [
        'auction_type' => ['max_width' => 80], 'name' => ['max_width' => 120], 'youtube_views' => ['max_width' => 70],
        'youtube_subscribers' => ['max_width' => 60], 'youtube_watch_hours' => ['max_width' => 60], 'youtube_views_2' => ['max_width' => 60],
    ]],
    12 => ['groups' => ['x_views' => ['f6'], 'x_posts' => ['f7']], 'layouts' => ['x_views' => ['max_width' => 70], 'x_posts' => ['max_width' => 40]]],
    13 => ['groups' => [
        'whatsapp_messages' => ['f5'], 'whatsapp_views' => ['f7'], 'whatsapp_date' => ['f10'],
        'whatsapp_messages_2' => ['f11'], 'whatsapp_views_2' => ['f13'], 'whatsapp_delivered' => ['f15'],
        'whatsapp_failed' => ['f17'], 'whatsapp_pending' => ['f19'], 'whatsapp_total' => ['f21'],
    ]],
    14 => ['groups' => [
        'campaign_1_views' => ['f6'], 'campaign_2_views' => ['f7'], 'campaign_3_views' => ['f10'], 'social_total' => ['f14'],
        'campaign_1_name' => ['f8'], 'campaign_2_name' => ['f9'], 'campaign_3_name' => ['f11'],
    ], 'image_slots' => ['i48' => 0, 'i14' => 1, 'i15' => 1, 'i32' => 2]],
    15 => ['groups' => [
        'broker_1' => ['f8'], 'broker_2' => ['f11'],
        'contract_text' => array_map(static fn (int $id): string => 'f'.$id, range(12, 62)),
    ], 'static_images' => ['i7', 'i11', 'i12'], 'images' => [
        ['section' => 'brokers', 'slot' => 0, 'box' => [135.5, 325.8, 491.6, 789.4]],
    ]],
    16 => ['groups' => ['board_2' => ['f4'], 'board_1' => ['f5'], 'board_4' => ['f6'], 'board_3' => ['f7'], 'board_6' => ['f8'], 'board_5' => ['f9'], 'google_views' => ['f10']]],
    17 => ['groups' => ['board_8' => ['f4'], 'board_7' => ['f5'], 'board_10' => ['f6'], 'board_9' => ['f7'], 'board_12' => ['f8'], 'board_11' => ['f9'], 'board_13' => ['f10']],
        'image_slots' => ['i8' => 0, 'i9' => 1, 'i2' => 2, 'i3' => 3, 'i4' => 4, 'i5' => 5, 'i6' => 5, 'i7' => 6]],
    18 => ['groups' => ['sales_total' => ['f4'], 'sold_count' => ['f5'], 'sold_position_1' => ['f7'], 'sold_position_2' => ['f20'], 'sold_position_3' => ['f34']]],
    19 => ['groups' => ['unsold_count' => ['f5']]],
    20 => ['groups' => ['phone' => ['f1'], 'unified_phone' => ['f2'], 'social' => ['f3']]],
];
