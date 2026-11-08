<?php

declare(strict_types=1);

return [
    'text' => [
        'city_context' => 'نطاق الحملة الجغرافي',
        'planning_methodology' => 'منهجية التخطيط',
        'name_context' => 'اختيار اسم المزاد والهوية',
        'brochure_methodology' => 'إعداد الكتيب والهوية البصرية',
        'audience_methodology' => 'الفئات المستهدفة',
        'messages_methodology' => 'الرسائل والتواصل مع العملاء',
        'brochure_context' => 'تفاصيل الكتيب التسويقي',
        'platform_context' => 'تفاصيل العرض على المنصة',
        'video_context' => 'تفاصيل الفيديو التسويقي',
        'x_context' => 'تفاصيل حملة X',
        'social_context' => 'تفاصيل حملات التواصل',
        'board_context' => 'تفاصيل تركيب اللوحات',
        'board_dimensions' => 'مقاس اللوحات',
        'display_name' => 'اسم الوسيط',
        'broker_second_name' => 'اسم الوسيط الثاني',
        'contract_text' => 'نص التعاقد مع الوسيط',
    ],
    'positions' => [
        'city_context' => [['asir', 3, 'city_context']],
        'planning_methodology' => [['asir', 4, 'planning_methodology']],
        'name_context' => [['asir', 5, 'name_context']],
        'brochure_methodology' => [['asir', 5, 'brochure_methodology']],
        'audience_methodology' => [['asir', 6, 'audience_methodology']],
        'messages_methodology' => [['asir', 6, 'messages_methodology']],
        'brochure_context' => [['asir', 9, 'brochure_context']],
        'platform_context' => [['asir', 10, 'platform_context']],
        'video_context' => [['asir', 11, 'video_context']],
        'x_context' => [['asir', 12, 'x_context']],
        'social_context' => [['asir', 14, 'social_context']],
        'board_context' => [['asir', 7, 'board_context'], ['asir', 8, 'board_context']],
        'display_name' => [['asir', 15, 'broker_1'], ['haram', 5, 'broker_1']],
        'broker_second_name' => [['asir', 15, 'broker_2'], ['haram', 5, 'broker_2']],
        'contract_text' => [['asir', 15, 'contract_text']],
        'results_narrative' => [['haram', 2, 'narrative']],
    ],
    'links' => ['youtube', 'x', 'snapchat', 'tiktok', 'whatsapp', 'brochure', 'board_before', 'marketing_evidence'],
    'evidence' => ['youtube', 'x', 'snapchat', 'tiktok', 'whatsapp', 'brochure', 'platform', 'board_before', 'brokers'],
];
