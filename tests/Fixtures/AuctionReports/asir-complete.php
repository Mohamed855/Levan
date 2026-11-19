<?php

declare(strict_types=1);

// Explicit demonstration data only. No tenant records or settings are created.
return static function (): array {
    $snapshot = (require __DIR__.'/asir-native.php')('populated');
    $asset = static fn (string $name): string => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents(resource_path('auction-reports/asir/'.$name)));
    $snapshot['branding'] = [
        'company_name' => 'شركة ليفيان العقارية — بيانات تجريبية',
        'company_logo' => $asset('cover-company-logo.svg'),
        'issuing_entity_name' => 'الجهة المسندة التجريبية',
        'issuing_entity_logo' => $asset('cover-issuing-logo.svg'),
    ];
    $snapshot['auction'] = array_replace($snapshot['auction'], [
        'name' => 'مزاد واجهة عسير — مثال شامل', 'status' => 'closed',
        'description' => 'مثال تجريبي شامل لمراجعة جميع أقسام التقرير.',
        'platform_name' => 'المنصة الإلكترونية التجريبية', 'platform_url' => 'https://example.com/auction/9000',
        'notes' => 'بيانات العرض الإلكتروني للمزاد وروابطه ومدة انعقاده وعدد العقارات المنشورة.',
    ]);
    $snapshot['marketing'] = [
        'introduction' => str_repeat('يوثق هذا التقرير التجريبي بيانات مزاد واجهة عسير والعقارات المرتبطة به وأعمال التسويق المدخلة ونتائج الإقفال المسجلة. أعدت هذه البيانات لأغراض مراجعة التصميم وتوزيع النصوص والصور والجداول؛ وهي لا تمثل نتائج مزاد فعلي. ', 3),
        'report_summary' => "ملخص مستقل يكتبه المستخدم من بيانات المزاد، ويختلف عن المقدمة وعن نطاق الحملة الجغرافي.\nيتضمن هذا المثال خمسة عقارات: أربعة عقارات مباعة وعقار واحد غير مباع. ترد إحصاءات كل حملة ومرفقاتها في قسمها، وتحتسب مجاميع البيع من نتائج العقارات المسجلة.",
        'contacts' => ['phone' => '0501234567', 'unified_phone' => '920012345', 'social' => '@Levan_Demo'],
        'planning_methodology' => 'مراجعة بيانات العقارات والصكوك وتحديد خطة العمل ومواعيدها ومتابعة التنفيذ وفق البيانات المسجلة.',
        'city_context' => 'عسير، الرياض، جدة',
        'brochure_methodology' => 'إعداد الهوية البصرية والملف التعريفي وتنسيق بيانات العقارات وصورها ووسائل التواصل.',
        'audience_methodology' => 'المستثمرون والأفراد المهتمون بالعقارات السكنية والتجارية وفق خطة هذه الحملة التجريبية.',
        'messages_methodology' => 'إعداد رسائل تعريفية وتذكيرية تتضمن بيانات المزاد وروابطه ومواعيد البداية والنهاية.',
        'board_context' => 'توثيق تركيب اللوحات الإعلانية للعقار الأول بالصور التجريبية المرفقة.',
        'board_url' => 'https://example.com/boards',
        'boards_url' => 'https://example.com/boards',
        'brochure_context' => 'الكتيب التعريفي التجريبي: مواقع العقارات وبيانات الصكوك والأصول والصور المتاحة وشروط المشاركة.',
        'brochure_url' => 'https://example.com/brochure',
        'social_context' => 'إحصاءات حملات التواصل الاجتماعي التجريبية وفق البيانات المسجلة.',
        'contract_text' => 'عقد وساطة تجريبي يوضح نطاق التعاون والتسويق للمزاد وفق الملف المرفق باسم الوسيط.',
        'brokers' => [['key' => 'demo-1', 'name' => 'مكتب الوسيط الأول — ملف تجريبي'], ['key' => 'demo-2', 'name' => 'مكتب الوسيط الثاني — ملف تجريبي']],
        'board_removal_date' => '2026-05-21',
    ];
    $campaigns = [
        ['platform' => 'youtube', 'views' => 18240, 'subscribers' => 235, 'watch_hours' => 152.75, 'video_context' => 'فيديو تعريفي تجريبي لعرض مواقع العقارات ومميزاتها وطريقة المشاركة.'],
        ['platform' => 'x', 'views' => 25700, 'posts' => 12, 'x_context' => 'حملة تجريبية على منصة X للتعريف بالمزاد والعقارات ومواعيد المشاركة.'],
        ['platform' => 'whatsapp', 'campaign_date' => '2026-05-18', 'messages' => 2500, 'views' => 1800, 'delivered' => 2200, 'failed' => 150, 'pending' => 150],
        ['platform' => 'snapchat', 'views' => 32500],
        ['platform' => 'tiktok', 'views' => 28750, 'impressions' => 34400, 'reach' => 21600, 'profile_views' => 1250, 'profile_change' => 12.5, 'reach_change' => 18.75, 'views_change' => 22.25],
        ['platform' => 'google', 'views' => 14200],
        ['platform' => 'marketing_evidence'],
    ];
    foreach ($campaigns as $index => &$campaign) {
        $campaign['id'] = $index + 1;
        if ($campaign['platform'] === 'whatsapp') {
            $campaign['total'] = $campaign['delivered'] + $campaign['failed'] + $campaign['pending'];
        }
        if (in_array($campaign['platform'], config('auction-marketing-fields.links'), true)) {
            $campaign['url'] = 'https://example.com/campaign/'.$campaign['platform'];
        }
    }
    unset($campaign);
    $snapshot['marketing']['campaigns'] = $campaigns;
    $ids = array_column($campaigns, 'id', 'platform');
    foreach ($snapshot['evidence'] as $section => &$items) {
        foreach ($items as &$item) {
            $channel = match ($section) {
                'video' => 'youtube', 'social' => $item['channel'], 'board_after' => null, default => $section,
            };
            if ($channel !== null && isset($ids[$channel])) {
                $item['campaign_id'] = $ids[$channel];
            } else {
                unset($item['campaign_id']);
            }
            $item['display_name'] = '';
        }
        unset($item);
    }
    unset($items);
    $snapshot['evidence']['brokers'][0]['display_name'] = 'مكتب الوسيط الأول — ملف تجريبي';
    $secondBroker = $snapshot['evidence']['brokers'][0];
    $secondBroker['id'] = 1802;
    $secondBroker['display_name'] = 'مكتب الوسيط الثاني — ملف تجريبي';
    $snapshot['evidence']['brokers'][] = $secondBroker;
    $snapshot['properties'] = array_slice($snapshot['properties'], 0, 5);
    foreach ($snapshot['properties'] as $index => &$property) {
        $property['type'] = $index % 2 === 0 ? 'أرض سكنية' : 'أرض تجارية';
        $property['board'] = ['dimensions' => '3 × 6 م', 'count' => 2];
        $property['result_status'] = $index < 4 ? 'sold' : 'unsold';
        $property['opening_price'] = 400000 + $index * 50000;
        $property['sale_price'] = $index < 4 ? 600000 + $index * 75000 : null;
        $property['participants_count'] = 10 + $index;
        $property['bids_count'] = 20 + $index * 2;
        $property['unsold_reason'] = $index < 4 ? null : 'لم يصل أعلى عرض مسجل إلى السعر المستهدف للعقار وفق نتيجة الإقفال التجريبية.';
    }
    unset($property);
    $snapshot['totals'] = ['properties_count' => 5, 'sold_count' => 4, 'unsold_count' => 1, 'pending_count' => 0, 'recorded_sales_total' => 2850000, 'participants_count' => 60, 'bids_count' => 120];
    $snapshot['closing'] = ['date' => '2026-05-21', 'approval_number' => 'DEMO-APPROVAL-9000'];

    return $snapshot;
};
