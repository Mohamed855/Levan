<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

final class HaramReportData
{
    public function __construct(private readonly AuctionReportFormat $format) {}

    public function page(array $snapshot, array $page): array
    {
        $type = preg_replace('/-continuation$/', '', $page['page_type']);
        $index = array_search($type, ['haram-cover', 'haram-index', 'haram-results', 'haram-marketing', 'haram-board-before', 'haram-brokers', 'haram-closing'], true);
        $auction = $snapshot['auction'];
        $payload = $page['payload'];
        $marketing = $snapshot['marketing'] ?? [];
        $contacts = $marketing['contacts'] ?? [];
        $name = (string) $auction['name'];
        $values = [
            'name' => $name, 'subtitle' => 'التقرير النهائي '.(str_starts_with($name, 'مزاد') ? 'ل'.$name : 'لمزاد '.$name),
            'manager' => (string) ($auction['project_manager_name'] ?? '-'),
            'project' => (string) ($snapshot['assignment']['name'] ?? '-'),
            'start_date' => $this->format->date($auction['start_date'] ?? null),
            'end_date' => $this->format->date($auction['end_date'] ?? null),
            'date_range' => $this->format->dateRange($auction['start_date'] ?? null, $auction['end_date'] ?? null),
            'property_count' => (string) count($snapshot['properties'] ?? []),
            'footer' => 'مخرجات المزاد - '.$name,
            'narrative' => (string) (($auction['description'] ?? '') ?: 'نتائج المزاد '.$name.' تحت إشراف '.($auction['supervising_entity'] ?? '-')),
            'removal_date' => 'فك اللوحات - '.$this->format->date($auction['end_date'] ?? null),
            'phone' => (string) ($contacts['phone'] ?? '-'),
            'unified_phone' => (string) ($contacts['unified_phone'] ?? '-'),
            'social' => (string) ($contacts['social'] ?? '-'),
        ];
        if (in_array($index, [0, 6], true)) {
            $values['footer'] = '-'.$name.' '.$this->format->dateParts($auction['start_date'] ?? null)['year'];
        }
        $campaigns = $payload['campaigns'] ?? [];
        $channels = ['snapchat' => ['snapchat', 'سناب شات'], 'tiktok' => ['tiktok', 'تيك توك'], 'x' => ['x', 'أكس']];
        $slots = array_fill_keys(array_keys($channels), null);
        $used = [];
        foreach ($channels as $channel => $names) {
            foreach ($campaigns as $key => $campaign) {
                if (! isset($used[$key]) && in_array(mb_strtolower(trim((string) ($campaign['platform'] ?? ''))), $names, true)) {
                    $slots[$channel] = $campaign;
                    $used[$key] = true;
                    break;
                }
            }
        }
        foreach ($campaigns as $key => $campaign) {
            if (isset($used[$key])) {
                continue;
            }
            foreach ($slots as $channel => $slot) {
                if ($slot === null) {
                    $slots[$channel] = $campaign;
                    break;
                }
            }
        }
        foreach ($slots as $channel => $campaign) {
            $values[$channel.'_impressions'] = $this->format->number($campaign['impressions'] ?? null);
            $platform = mb_strtolower(trim((string) ($campaign['platform'] ?? '')));
            $values[$channel.'_label'] = $platform === '' || in_array($platform, $channels[$channel], true)
                ? 'منصة '.$channels[$channel][1]
                : (string) $campaign['platform'];
        }
        $tiktok = $this->campaign($snapshot, ['tiktok', 'تيك توك']);
        foreach (['profile_views', 'reach', 'views', 'profile_change', 'reach_change', 'views_change'] as $metric) {
            $value = $tiktok[$metric] ?? null;
            $values['tiktok_'.$metric] = is_numeric($value) ? $this->format->number((float) $value) : (string) ($value ?? '-');
        }
        $values['x_url'] = (string) ($this->campaign($snapshot, ['x', 'أكس'])['url'] ?? '-');
        $values['tiktok_url'] = (string) ($tiktok['url'] ?? '-');
        $values['snapchat_views'] = $this->format->number($this->campaign($snapshot, ['snapchat', 'سناب شات'])['impressions'] ?? null);
        $values['marketing_url'] = (string) ($marketing['evidence_url'] ?? '-');
        $values['boards_url'] = (string) ($marketing['boards_url'] ?? '-');
        foreach (range(1, 3) as $slot) {
            $values['broker_'.$slot] = (string) ($payload['brokers'][$slot - 1]['name'] ?? '-');
        }
        $position = static function (array $property) use ($snapshot): string {
            foreach ($snapshot['properties'] as $key => $candidate) {
                if (($candidate['id'] ?? null) === ($property['id'] ?? null)) {
                    return (string) ($key + 1);
                }
            }

            return '-';
        };
        $tables = [];
        if ($index === 1) {
            $tables['properties'] = array_map(static fn (array $property): array => [
                (string) ($property['board']['dimensions'] ?? '-'), (string) $property['number'],
                (string) ($property['deed_number'] ?? '-'), (string) ($property['type'] ?? '-'), $position($property),
            ], $payload['properties']);
        }
        if ($index === 2) {
            $tables['sold'] = array_map(fn (array $property): array => [
                $this->format->number($property['sale_price'] ?? null), $this->format->number($property['opening_price'] ?? null),
                $this->format->number($property['participants_count'] ?? null), $this->format->number($property['bids_count'] ?? null),
                (string) ($property['type'] ?? $property['name'] ?? '-'), $position($property),
            ], $payload['sold_properties']);
            $tables['unsold'] = array_map(static fn (array $property): array => [
                (string) ($property['unsold_reason'] ?? '-'), (string) ($property['type'] ?? $property['name'] ?? '-'), $position($property),
            ], $payload['unsold_properties']);
        }

        return ['index' => $index, 'values' => $values, 'tables' => $tables, 'images' => $payload];
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
