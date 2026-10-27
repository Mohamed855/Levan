<?php

declare(strict_types=1);

return static function (string $design): array {
    $haram = $design === 'haram';
    $propertyCount = $haram ? 11 : 13;
    $soldCount = $haram ? 4 : 3;
    $properties = [];
    for ($index = 1; $index <= $propertyCount; $index++) {
        $sold = $index <= $soldCount;
        $properties[] = [
            'id' => $index, 'number' => (string) (280000 + $index), 'name' => 'عقار '.$index,
            'type' => 'أرض سكنية', 'city' => $haram ? 'مكة' : 'عسير', 'district' => '-', 'area' => 500,
            'deed_number' => (string) (371701002630 + $index), 'estimated_value' => null,
            'images' => [], 'image' => null, 'board' => ['dimensions' => '3*6', 'count' => 1],
            'result_status' => $sold ? 'sold' : 'unsold', 'opening_price' => $sold ? 500000 : null,
            'sale_price' => $sold ? 600000 : null, 'participants_count' => $sold ? 2 : null,
            'bids_count' => $sold ? 3 : null, 'unsold_reason' => $sold ? null : 'لم يصل السعر إلى الحد المطلوب',
        ];
    }

    return [
        'generated_at' => '2026-10-27T00:00:00+00:00',
        'auction' => [
            'id' => 9000, 'name' => $haram ? 'مزاد جوار الحرم' : 'واجهة عسير', 'description' => 'نتائج المزاد',
            'city' => $haram ? 'مكة' : 'عسير', 'type' => 'إلكتروني', 'category' => 'عقارات متنوعة',
            'start_date' => $haram ? '2026-08-13' : '2026-05-19', 'end_date' => $haram ? '2026-08-16' : '2026-05-21',
            'start_time' => '10:00', 'end_time' => '17:00', 'supervising_entity' => 'إنفاذ',
            'project_manager_name' => $haram ? 'عمر الريس' : 'أ / أسامه الحملي',
            'platform_name' => 'منصة المزاد', 'platform_url' => null, 'cover_image' => null, 'report_image' => null,
        ],
        'assignment' => ['name' => $haram ? 'مكة 401' : 'عسير 154', 'number' => 'FIXTURE', 'issuing_entity' => 'إنفاذ'],
        'properties' => $properties, 'marketing' => ['campaigns' => []], 'evidence' => [],
        'totals' => [
            'properties_count' => $propertyCount, 'sold_count' => $soldCount, 'unsold_count' => $propertyCount - $soldCount,
            'pending_count' => 0, 'recorded_sales_total' => $soldCount * 600000, 'participants_count' => $soldCount * 2,
            'bids_count' => $soldCount * 3,
        ],
    ];
};
