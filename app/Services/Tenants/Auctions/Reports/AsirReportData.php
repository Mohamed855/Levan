<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use InvalidArgumentException;
use App\Enums\Tenants\Auctions\AuctionStatus;

final class AsirReportData
{
    private const TYPES = [
        'asir-cover', 'asir-index', 'asir-introduction', 'asir-summary', 'asir-planning', 'asir-design', 'asir-promotion',
        'asir-board-installation-1', 'asir-board-installation-2', 'asir-brochure', 'asir-platform', 'asir-video',
        'asir-x', 'asir-whatsapp', 'asir-social', 'asir-brokers', 'asir-board-removal-1', 'asir-board-removal-2',
        'asir-sold-assets', 'asir-unsold-assets', 'asir-closing',
    ];

    public function __construct(private readonly AuctionReportFormat $format) {}

    public function page(array $snapshot, array $page): array
    {
        if (array_key_exists('gallery', $page['payload'])) {
            return (new AuctionReportEvidencePage)->content($snapshot, $page, 'asir');
        }
        $type = preg_replace('/-continuation$/', '', $page['page_type']);
        $index = array_search($type, self::TYPES, true);
        if ($index === false) {
            throw new InvalidArgumentException('Unknown Asir page.');
        }
        $auction = $snapshot['auction'];
        $payload = $page['payload'];
        $name = (string) $auction['name'];
        $start = $this->format->dateParts($auction['start_date'] ?? null);
        $end = $this->format->dateParts($auction['end_date'] ?? null);
        $marketing = $snapshot['marketing'] ?? [];
        $contacts = $marketing['contacts'] ?? [];
        $destinations = (new AuctionReportLinks)->destinations($snapshot);
        $values = [
            'name' => $name, 'subtitle' => 'التقرير النهائي '.(str_starts_with($name, 'مزاد') ? 'ل'.$name : 'لمزاد '.$name),
            'manager' => (string) ($auction['project_manager_name'] ?? '-'),
            'project' => (string) ($snapshot['assignment']['name'] ?? '-'),
            'date_range' => $this->format->dateRange($auction['start_date'] ?? null, $auction['end_date'] ?? null),
            'start_hijri' => $this->format->hijriDate($auction['start_date'] ?? null).' هـ',
            'end_hijri' => $this->format->hijriDate($auction['end_date'] ?? null).' هـ',
            'start_time' => $this->format->arabicDigits($this->format->time($auction['start_time'] ?? null)),
            'end_time' => $this->format->arabicDigits($this->format->time($auction['end_time'] ?? null)),
            'end_time_short' => $this->format->arabicDigits(str_replace(['الساعة ', 'صباحًا', 'مساءً'], ['', 'ص', 'م'], $this->format->time($auction['end_time'] ?? null))),
            'property_count' => $this->format->arabicDigits((string) count($snapshot['properties'])),
            'introduction' => (string) (($marketing['introduction'] ?? '') ?: (($auction['description'] ?? '') ?: '-')),
            'name_context' => 'اختيار اسم المزاد وإعداد الهوية البصرية - '.$name,
            'city_context' => 'المملكة ومنطقة '.($auction['city'] ?? '-').' .',
            'platform_context' => 'منصة المزاد المسجلة: '.($auction['platform_name'] ?? '-'),
            'planning_methodology' => 'تشمل منهجية التخطيط دراسة الفئات المستهدفة ووسائل الوصول إليها والمقارنة بالمشروعات السابقة وواقع السوق العقاري. هذا وصف للمنهجية وليس إثباتًا لتنفيذها.',
            'brochure_context' => 'يُخصص هذا القسم لعرض الكتيب التسويقي ومرفقاته المضافة للمزاد، ولا يدل وجود القسم وحده على نشر الكتيب أو توزيعه.',
            'video_context' => 'أدلة الفيديو التسويقي المضافة للمزاد',
            'brochure_methodology' => 'إعداد ملف تعريفي للعقارات يوضح مزاياها ومواقعها وصورها والبيانات والإحداثيات المتاحة، مع تسهيل الانتقال إلى مواقع العقارات. تُراجع المرفقات لإثبات التنفيذ الفعلي.',
            'audience_methodology' => 'تحديد العملاء المهتمين بنوعية العقارات المعروضة ووسائل دعوتهم للاطلاع على الملف التعريفي والمشاركة في المزاد. لا يفترض هذا الوصف إجراء اتصالات فعلية.',
            'messages_methodology' => 'تتضمن منهجية التواصل إعداد رسائل تعريفية وتذكيرية للمجموعات العقارية والعملاء المستهدفين. يُوثق الإرسال الفعلي فقط بالأدلة والإحصاءات المضافة.',
            'platform_url' => empty($destinations['platform']) ? '-' : 'رابط المزاد على '.($auction['platform_name'] ?? 'المنصة'),
            'auction_type' => (string) ($auction['type'] ?? '-'),
            'duration' => $this->format->duration($auction),
            'status' => ($status = AuctionStatus::tryFrom((string) ($auction['status'] ?? ''))) ? 'مزاد '.$status->label() : 'غير متوفر',
            'start_mark' => '', 'duplicate_header' => '', 'contract_text' => '',
            'phone' => (string) ($contacts['phone'] ?? '-'), 'unified_phone' => (string) ($contacts['unified_phone'] ?? '-'),
            'social' => (string) ($contacts['social'] ?? '-'),
            'brochure_url' => empty($destinations['brochure']) ? '-' : 'رابط الكتيب',
            'video_url' => empty($destinations['video']) ? '-' : 'رابط فيديو المزاد',
            'sales_total' => $this->format->arabicDigits($this->format->number($snapshot['totals']['recorded_sales_total'] ?? null)),
            'sold_count' => '('.$this->format->arabicDigits((string) ($snapshot['totals']['sold_count'] ?? 0)).') عقار تم بيعها',
            'unsold_count' => $this->format->arabicDigits((string) ($snapshot['totals']['unsold_count'] ?? 0)),
        ];
        foreach (['start' => $start, 'end' => $end] as $prefix => $parts) {
            foreach ($parts as $part => $value) {
                $values[$prefix.'_'.$part] = ($part === 'weekday' ? 'يوم ' : '').$this->format->arabicDigits($value);
            }
        }
        foreach (['youtube' => ['youtube', 'يوتيوب'], 'x' => ['x', 'أكس'], 'tiktok' => ['tiktok', 'تيك توك'], 'snapchat' => ['snapchat', 'سناب شات'], 'google' => ['google', 'جوجل'], 'whatsapp' => ['whatsapp', 'واتساب']] as $channel => $names) {
            $campaign = $this->campaign($snapshot, $names);
            foreach (['views', 'posts', 'subscribers', 'watch_hours', 'messages', 'delivered', 'failed', 'pending', 'total'] as $metric) {
                $value = $campaign[$metric] ?? null;
                $values[$channel.'_'.$metric] = $this->format->number(is_numeric($value) ? (float) $value : null, $metric === 'watch_hours' ? 2 : 0);
                $values[$channel.'_'.$metric.'_2'] = $values[$channel.'_'.$metric];
            }
        }
        $values['whatsapp_date'] = $this->format->date($marketing['whatsapp_date'] ?? null);
        $values['whatsapp_summary'] = $values['whatsapp_messages'] === '-' ? 'لا تتوفر بيانات عن عدد الرسائل' : 'عدد الرسائل المسجلة: '.$values['whatsapp_messages'];
        $values['whatsapp_views_summary'] = $values['whatsapp_views'] === '-' ? 'لا تتوفر بيانات عن المشاهدات' : 'المشاهدات المسجلة: '.$values['whatsapp_views'];
        $views = array_column($marketing['campaigns'] ?? [], 'impressions');
        $values['social_total'] = $views === [] ? '-' : $this->format->arabicDigits($this->format->number(array_sum($views)));
        foreach (range(1, 3) as $slot) {
            $values['chart_'.$slot] = '';
            $values['sold_position_'.$slot] = $this->position($snapshot, $payload['properties'][$slot - 1] ?? []);
            $values['broker_'.$slot] = (string) (($payload['brokers'][$slot - 1]['display_name'] ?? '') ?: '-');
            $channel = ['snapchat', 'tiktok', 'x'][$slot - 1];
            $campaign = AuctionReportChannels::campaign($marketing['campaigns'] ?? [], $channel);
            $values['campaign_'.$slot.'_name'] = (string) ($campaign['platform'] ?? '-');
            $values['campaign_'.$slot.'_views'] = $this->format->number($campaign['impressions'] ?? null);
        }
        foreach (range(1, 13) as $slot) {
            $relative = $slot - (in_array($index, [8, 17], true) ? 7 : 1);
            $section = $index >= 16 ? 'board_after' : 'board_before';
            $item = $payload[$section][$relative] ?? null;
            $values['board_'.$slot] = $item === null ? '' : $this->format->arabicDigits((string) (($payload['offset'] ?? 0) + $relative + 1));
        }
        $tables = [];
        if ($index === 1) {
            $tables['properties'] = array_map(fn (array $property): array => [
                (string) ($property['board']['dimensions'] ?? '-'), $this->format->number($property['board']['count'] ?? null),
                (string) $property['number'], (string) ($property['deed_number'] ?? '-'), (string) ($property['type'] ?? '-'),
                $this->position($snapshot, $property),
            ], $payload['properties']);
        }
        if ($index === 18) {
            foreach (['sales', 'sales2', 'sales3'] as $slot => $id) {
                $property = $payload['properties'][$slot] ?? null;
                $tables[$id] = $property === null ? [] : [[
                    $this->format->number($property['bids_count'] ?? null), $this->format->number($property['participants_count'] ?? null),
                    $this->format->number($property['sale_price'] ?? null), $this->format->number($property['opening_price'] ?? null),
                    (string) ($property['deed_number'] ?? '-'),
                ]];
            }
        }
        if ($index === 19) {
            $tables['unsold'] = array_map(fn (array $property): array => [
                (string) ($property['unsold_reason'] ?? '-'), (string) ($property['deed_number'] ?? '-'),
                (string) $property['number'], (string) ($property['type'] ?? '-'), $this->position($snapshot, $property),
            ], $payload['properties']);
        }
        $images = $payload;
        $images['report_image'] = array_fill(0, 2, ['uri' => $auction['report_image'] ?? null]);

        $overrides = [];
        if ($index === 13) {
            $chart = (new AuctionReportCharts)->whatsapp(AuctionReportChannels::campaign($marketing['campaigns'] ?? [], 'whatsapp'));
            $overrides['hide_clips'] = ['p13_clip_32'];
            $overrides[$chart === null ? 'empty_regions' : 'charts'] = $chart === null ? [[125, 395, 253, 520]] : [$chart];
        }
        if ($index === 11 && empty($payload['video'][2]['uri'])) {
            $overrides['empty_regions'] = [[144.445, 635.937, 464.458, 732.191]];
        }

        return ['index' => $index, 'values' => $values, 'tables' => $tables, 'images' => $images, 'artwork_overrides' => $overrides];
    }

    private function position(array $snapshot, array $property): string
    {
        foreach ($snapshot['properties'] as $index => $candidate) {
            if (isset($property['id']) && ($candidate['id'] ?? null) === $property['id']) {
                return $this->format->arabicDigits((string) ($index + 1));
            }
        }

        return '-';
    }

    private function campaign(array $snapshot, array $names): array
    {
        foreach ($snapshot['marketing']['campaigns'] ?? [] as $campaign) {
            if (in_array(mb_strtolower(trim((string) ($campaign['platform'] ?? ''))), $names, true)) {
                return $campaign;
            }
        }

        return [];
    }
}
