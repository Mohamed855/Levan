<?php

declare(strict_types=1);

return [
    0 => ['groups' => ['name' => ['f0'], 'subtitle' => ['f1']], 'fallback_frames' => ['i5' => [['box' => [0, 508.802, 595.275, 841.89], 'clip' => 'p0_clip_12']]], 'layouts' => [
        'name' => ['box' => [35, 165, 560, 315], 'fit' => 'shrink', 'max_lines' => 2, 'center' => true],
        'subtitle' => ['box' => [45, 320, 550, 380], 'fit' => 'shrink', 'max_lines' => 2, 'center' => true],
    ]],
    1 => ['groups' => [
        'manager' => ['f3'], 'name' => ['f4'], 'project' => ['f5'],
        'end_weekday' => ['f9'], 'start_weekday' => ['f11'], 'end_month' => ['f12'], 'start_month' => ['f13'],
        'end_hijri' => ['f14'], 'start_hijri' => ['f15'], 'start_mark' => ['f16'],
        'end_year' => ['f17'], 'start_year' => ['f18'], 'end_time' => ['f19', 'f23'],
        'start_time' => ['f20'], 'end_day' => ['f21'], 'start_day' => ['f22'], 'duplicate_header' => ['f24'],
    ], 'layouts' => [
        'manager' => ['max_width' => 150, 'max_lines' => 2, 'center' => true],
        'name' => ['max_width' => 150, 'max_lines' => 2, 'center' => true],
        'project' => ['max_width' => 140, 'max_lines' => 2, 'center' => true],
        'start_month' => ['max_width' => 60], 'end_month' => ['max_width' => 60],
        'start_weekday' => ['max_width' => 115], 'end_weekday' => ['max_width' => 115],
        'start_time' => ['max_width' => 140], 'end_time' => ['max_width' => 140],
        'start_hijri' => ['max_width' => 110], 'end_hijri' => ['max_width' => 110],
    ]],
    2 => ['groups' => ['introduction' => ['f0', 'f1', 'f2', 'f3', 'f4', 'f5', 'f6']]],
    3 => ['groups' => ['city_context' => ['f6']]],
    4 => ['groups' => ['planning_methodology' => ['f2', 'f3', 'f4', 'f5']]],
    5 => ['groups' => ['name_context' => ['f2'], 'brochure_methodology' => ['f7', 'f8', 'f9', 'f10']], 'layouts' => ['brochure_methodology' => ['max_width' => 473.159, 'wrap_width' => 450]]],
    6 => ['groups' => ['audience_methodology' => ['f8', 'f9', 'f10'], 'messages_methodology' => ['f11', 'f12', 'f13']], 'layouts' => ['messages_methodology' => ['max_width' => 440]]],
    7 => ['hide_fills' => ['#4a6810'], 'groups' => ['board_2' => ['f6'], 'board_1' => ['f7'], 'board_4' => ['f8'], 'board_3' => ['f9'], 'board_6' => ['f10'], 'board_5' => ['f11']]],
    8 => ['hide_fills' => ['#4a6810'], 'groups' => ['board_8' => ['f6'], 'board_7' => ['f7'], 'board_10' => ['f8'], 'board_9' => ['f9'], 'board_12' => ['f10'], 'board_11' => ['f11'], 'board_13' => ['f12']],
        'image_slots' => ['i8' => 0, 'i9' => 1, 'i2' => 2, 'i3' => 3, 'i4' => 4, 'i5' => 5, 'i6' => 5, 'i7' => 6]],
    9 => ['groups' => ['brochure_url' => ['f7'], 'brochure_context' => ['f4', 'f5', 'f6']], 'link_fields' => ['brochure' => 'brochure_url']],
    10 => ['groups' => [
        'platform_context' => ['f5', 'f6'], 'auction_type' => ['f7'], 'name' => ['f9', 'f10'],
        'duration' => ['f12'], 'property_count' => ['f13'], 'date_range' => ['f14'], 'end_time_short' => ['f15'],
        'status' => ['f16'], 'platform_url' => ['f17'],
    ], 'link_fields' => ['platform' => 'platform_url'], 'layouts' => ['auction_type' => ['max_width' => 80], 'property_count' => ['max_width' => 80], 'duration' => ['max_width' => 110], 'date_range' => ['max_width' => 200], 'end_time_short' => ['max_width' => 110], 'status' => ['box' => [250, 675, 500, 735], 'fit' => 'shrink', 'max_lines' => 2, 'min_font_size' => 18, 'center' => true]]],
    11 => ['groups' => [
        'video_context' => ['f4'], 'youtube_views' => ['f5'], 'auction_type' => ['f7'], 'name' => ['f9'],
        'youtube_subscribers' => ['f14'], 'youtube_watch_hours' => ['f15'], 'youtube_views_2' => ['f16'],
        'chart_1' => ['f17'], 'chart_2' => ['f18'], 'chart_3' => ['f19'], 'video_url' => ['f20'],
    ], 'link_fields' => ['video' => 'video_url'], 'hidden_image_defs' => ['i8'], 'image_slots' => ['i12' => 1], 'layouts' => [
        'auction_type' => ['max_width' => 80], 'name' => ['max_width' => 120], 'youtube_views' => ['max_width' => 70],
        'youtube_subscribers' => ['max_width' => 60], 'youtube_watch_hours' => ['max_width' => 60], 'youtube_views_2' => ['max_width' => 60],
    ]],
    12 => ['groups' => ['x_views' => ['f6'], 'x_posts' => ['f7']], 'link_fields' => ['x' => 'x_views'], 'layouts' => ['x_views' => ['max_width' => 70], 'x_posts' => ['max_width' => 40]]],
    13 => ['groups' => [
        'whatsapp_summary' => ['f5', 'f6'], 'whatsapp_views_summary' => ['f7', 'f8', 'f9'], 'whatsapp_date' => ['f10'],
        'whatsapp_messages_2' => ['f11'], 'whatsapp_views_2' => ['f13'], 'whatsapp_delivered' => ['f15'],
        'whatsapp_failed' => ['f17'], 'whatsapp_pending' => ['f19'], 'whatsapp_total' => ['f21'],
    ], 'link_fields' => ['whatsapp' => 'whatsapp_summary'], 'layouts' => [
        'whatsapp_summary' => ['box' => [70, 275, 525, 307], 'fit' => 'shrink', 'min_font_size' => 12, 'max_lines' => 2, 'center' => true],
        'whatsapp_views_summary' => ['box' => [70, 310, 525, 340], 'fit' => 'shrink', 'min_font_size' => 12, 'max_lines' => 2, 'center' => true],
        'whatsapp_date' => ['max_width' => 150],
        'whatsapp_messages_2' => ['max_width' => 60, 'center' => true], 'whatsapp_views_2' => ['max_width' => 60, 'center' => true],
        'whatsapp_delivered' => ['max_width' => 60, 'center' => true], 'whatsapp_failed' => ['max_width' => 60, 'center' => true],
        'whatsapp_pending' => ['max_width' => 60, 'center' => true], 'whatsapp_total' => ['max_width' => 60, 'center' => true],
    ]],
    14 => ['groups' => [
        'campaign_1_views' => ['f7'], 'campaign_2_views' => ['f6'], 'campaign_3_views' => ['f10'], 'social_total' => ['f14'],
        'campaign_1_name' => ['f9'], 'campaign_2_name' => ['f8'], 'campaign_3_name' => ['f11'],
    ], 'layouts' => ['campaign_1_views' => ['max_width' => 100], 'campaign_2_views' => ['max_width' => 100], 'campaign_3_views' => ['max_width' => 100], 'social_total' => ['max_width' => 150]], 'link_fields' => ['snapchat' => 'campaign_1_name', 'tiktok' => 'campaign_2_name', 'x' => 'campaign_3_name'], 'image_slots' => ['i48' => 1, 'i14' => 0, 'i15' => 0, 'i32' => 2]],
    15 => ['empty_evidence_boxes' => ['brokers' => [100, 290, 515, 800]], 'groups' => [
        'broker_1' => ['f8'], 'broker_2' => ['f11'],
        'contract_text' => array_map(static fn (int $id): string => 'f'.$id, range(12, 62)),
    ], 'static_images' => ['i7', 'i11', 'i12'], 'images' => [
        ['section' => 'brokers', 'slot' => 0, 'box' => [135.5, 325.8, 491.6, 789.4]],
    ]],
    16 => ['hide_fills' => ['#4a6810'], 'groups' => ['board_2' => ['f4'], 'board_1' => ['f5'], 'board_4' => ['f6'], 'board_3' => ['f7'], 'board_6' => ['f8'], 'board_5' => ['f9'], 'google_views' => ['f10']]],
    17 => ['hide_fills' => ['#4a6810'], 'groups' => ['board_8' => ['f4'], 'board_7' => ['f5'], 'board_10' => ['f6'], 'board_9' => ['f7'], 'board_12' => ['f8'], 'board_11' => ['f9'], 'board_13' => ['f10']],
        'image_slots' => ['i8' => 0, 'i9' => 1, 'i2' => 2, 'i3' => 3, 'i4' => 4, 'i5' => 5, 'i6' => 5, 'i7' => 6]],
    18 => ['groups' => ['sales_total' => ['f4'], 'sold_count' => ['f5'], 'sold_position_1' => ['f7'], 'sold_position_2' => ['f20'], 'sold_position_3' => ['f34']], 'layouts' => [
        'sold_position_1' => ['max_width' => 40], 'sold_position_2' => ['max_width' => 40], 'sold_position_3' => ['max_width' => 40],
    ]],
    19 => ['groups' => ['unsold_count' => ['f5']]],
    20 => ['groups' => ['phone' => ['f1'], 'unified_phone' => ['f2'], 'social' => ['f3']], 'link_fields' => ['phone' => 'phone', 'unified_phone' => 'unified_phone', 'social' => 'social'], 'layouts' => [
        'phone' => ['box' => [170, 550, 485, 605], 'fit' => 'shrink', 'max_lines' => 1, 'min_font_size' => 28, 'center' => true],
        'unified_phone' => ['box' => [170, 610, 485, 665], 'fit' => 'shrink', 'max_lines' => 1, 'min_font_size' => 28, 'center' => true],
        'social' => ['box' => [140, 745, 455, 790], 'fit' => 'shrink', 'max_lines' => 2, 'min_font_size' => 10, 'center' => true],
    ]],
];
