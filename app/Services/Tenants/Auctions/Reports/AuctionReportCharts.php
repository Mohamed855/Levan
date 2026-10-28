<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use InvalidArgumentException;

final class AuctionReportCharts
{
    public function whatsapp(array $metrics): ?string
    {
        $counts = [];
        foreach (['delivered', 'failed', 'pending'] as $state) {
            $value = $metrics[$state] ?? null;
            if ($value === null) {
                return null;
            }
            if (!is_numeric($value) || $value < 0 || floor((float) $value) !== (float) $value) {
                throw new InvalidArgumentException('إحصاءات التسليم يجب أن تكون أعدادًا صحيحة غير سالبة.');
            }
            $counts[$state] = (float) $value;
        }
        $total = array_sum($counts);
        if (isset($metrics['total']) && (!is_numeric($metrics['total']) || (float) $metrics['total'] !== $total)) {
            throw new InvalidArgumentException('إجمالي رسائل الواتساب لا يساوي مجموع المسلّم والفاشل وقيد الإرسال.');
        }
        if ($total === 0.0) {
            return null;
        }
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 595.276 841.89" data-report-chart="whatsapp">';
        $angle = -M_PI / 2;
        $cx = 189.4733;
        $cy = 457.4479;
        $radius = 60.654;
        $colors = ['delivered' => '#005589', 'failed' => '#d9534f', 'pending' => '#a5a5a5'];
        foreach ($counts as $state => $count) {
            if ($count === 0.0) {
                continue;
            }
            $percent = $count / $total * 100;
            $attributes = ' data-chart-state="'.$state.'" data-percent="'.round($percent, 6).'" fill="'.$colors[$state].'" stroke="#ffffff" stroke-width="0.25"';
            if ($count === $total) {
                $svg .= '<circle cx="'.$cx.'" cy="'.$cy.'" r="'.$radius.'"'.$attributes.'/>';
            } else {
                $end = $angle + $count / $total * M_PI * 2;
                $path = sprintf('M %.6F %.6F L %.6F %.6F A %.6F %.6F 0 %d 1 %.6F %.6F Z', $cx, $cy, $cx + $radius * cos($angle), $cy + $radius * sin($angle), $radius, $radius, $percent > 50 ? 1 : 0, $cx + $radius * cos($end), $cy + $radius * sin($end));
                $svg .= '<path d="'.$path.'"'.$attributes.'/>';
                $angle = $end;
            }
        }

        return $svg.'</svg>';
    }
}
