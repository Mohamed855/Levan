<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use InvalidArgumentException;

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
        $values = [
            'name' => $name, 'subtitle' => 'التقرير النهائي لـ'.$name,
            'manager' => (string) ($auction['project_manager_name'] ?? '-'),
            'project' => (string) ($snapshot['assignment']['name'] ?? '-'),
            'date_range' => $this->format->date($auction['start_date'] ?? null).' - '.$this->format->date($auction['end_date'] ?? null),
            'start_hijri' => $this->format->hijriDate($auction['start_date'] ?? null).' هـ',
            'end_hijri' => $this->format->hijriDate($auction['end_date'] ?? null).' هـ',
            'start_time' => $this->format->arabicDigits((string) ($auction['start_time'] ?? '-')),
            'end_time' => $this->format->arabicDigits((string) ($auction['end_time'] ?? '-')),
            'property_count' => $this->format->arabicDigits((string) count($snapshot['properties'])),
            'introduction' => (string) ($marketing['introduction'] ?? $auction['description'] ?? '-'),
            'name_context' => 'اختيار اسم المزاد وإعداد الهوية البصرية - '.$name,
            'city_context' => 'المملكة ومنطقة '.($auction['city'] ?? '-').' .',
            'platform_context' => 'نشر المزاد على '.($auction['platform_name'] ?? '-'),
            'platform_url' => (string) ($auction['platform_url'] ?? '-'),
            'auction_type' => (string) ($auction['type'] ?? '-'),
            'duration' => '', 'status' => '', 'start_mark' => '', 'duplicate_header' => '', 'contract_text' => '',
            'phone' => (string) ($contacts['phone'] ?? '-'), 'unified_phone' => (string) ($contacts['unified_phone'] ?? '-'),
            'social' => (string) ($contacts['social'] ?? '-'),
            'brochure_url' => (string) ($marketing['brochure_url'] ?? '-'),
            'video_url' => (string) ($marketing['video_url'] ?? '-'),
            'sales_total' => $this->format->arabicDigits($this->format->number($snapshot['totals']['recorded_sales_total'] ?? null)),
            'sold_count' => '('.$this->format->arabicDigits((string) ($snapshot['totals']['sold_count'] ?? 0)).') عقار تم بيعها',
            'unsold_count' => $this->format->arabicDigits((string) ($snapshot['totals']['unsold_count'] ?? 0)),
        ];
        foreach (['start' => $start, 'end' => $end] as $prefix => $parts) {
            foreach ($parts as $part => $value) {
                $values[$prefix.'_'.$part] = $this->format->arabicDigits($value);
            }
        }
        foreach (['youtube' => ['youtube', 'يوتيوب'], 'x' => ['x', 'أكس'], 'tiktok' => ['tiktok', 'تيك توك'], 'snapchat' => ['snapchat', 'سناب شات'], 'google' => ['google', 'جوجل'], 'whatsapp' => ['whatsapp', 'واتساب']] as $channel => $names) {
            $campaign = $this->campaign($snapshot, $names);
            foreach (['views', 'posts', 'subscribers', 'watch_hours', 'messages', 'delivered', 'failed', 'pending', 'total'] as $metric) {
                $value = $campaign[$metric] ?? ($metric === 'views' ? ($campaign['impressions'] ?? null) : null);
                $values[$channel.'_'.$metric] = $this->format->number(is_numeric($value) ? (float) $value : null);
                $values[$channel.'_'.$metric.'_2'] = $values[$channel.'_'.$metric];
            }
        }
        $values['whatsapp_date'] = $this->format->date($marketing['whatsapp_date'] ?? null);
        $views = array_column($marketing['campaigns'] ?? [], 'impressions');
        $values['social_total'] = $views === [] ? '-' : $this->format->arabicDigits($this->format->number(array_sum($views)));
        foreach (range(1, 3) as $slot) {
            $values['chart_'.$slot] = '';
            $values['sold_position_'.$slot] = $this->position($snapshot, $payload['properties'][$slot - 1] ?? []);
            $values['broker_'.$slot] = (string) ($payload['brokers'][$slot - 1]['name'] ?? '-');
            $campaign = $payload['campaigns'][$slot - 1] ?? [];
            $values['campaign_'.$slot.'_name'] = (string) ($campaign['platform'] ?? '-');
            $values['campaign_'.$slot.'_views'] = $this->format->number($campaign['impressions'] ?? null);
        }
        foreach (range(1, 13) as $slot) {
            $relative = $slot - (in_array($index, [8, 17], true) ? 7 : 1);
            $section = $index >= 16 ? 'board_after' : 'board_before';
            $item = $payload[$section][$relative] ?? null;
            $values['board_'.$slot] = $item === null ? '-' : $this->format->arabicDigits((string) (($payload['offset'] ?? 0) + $relative + 1));
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

        return ['index' => $index, 'values' => $values, 'tables' => $tables, 'images' => $images];
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
