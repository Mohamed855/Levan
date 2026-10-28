<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use App\Services\Tenants\Auctions\Reports\AuctionReportChannels;
use Closure;

trait AuctionReportDetailRules
{
    protected function reportDetailRules(): array
    {
        $rules = [
            'property_results.*.board_count' => ['nullable', 'integer', 'min:0'],
            'property_results.*.board_dimensions' => ['nullable', 'string', 'max:100'],
            'marketing.introduction' => ['nullable', 'string', 'max:10000'],
            'marketing.results_narrative' => ['nullable', 'string', 'max:10000'],
            'marketing.contacts' => ['nullable', 'array:phone,unified_phone,social'],
            'marketing.contacts.phone' => ['nullable', 'string', 'max:30', 'regex:/^[+\p{N} ()-]+$/u'],
            'marketing.contacts.unified_phone' => ['nullable', 'string', 'max:30', 'regex:/^[+\p{N} ()-]+$/u'],
            'marketing.contacts.social' => ['nullable', 'string', 'max:255'],
            'marketing.whatsapp_date' => ['nullable', 'date'],
            'marketing.links.*.role' => ['nullable', Rule::in(config('auction-report-inputs.link_roles'))],
            'marketing.campaigns.*.platform_label' => ['nullable', 'string', 'max:100'],
            'marketing.campaigns.*' => ['array', static function (string $attribute, mixed $campaign, Closure $fail): void {
                if (!is_array($campaign) || AuctionReportChannels::key($campaign['platform'] ?? null) !== 'whatsapp') {
                    return;
                }
                $counts = [];
                foreach (['delivered', 'failed', 'pending', 'total'] as $key) {
                    if (!isset($campaign[$key]) || !is_numeric($campaign[$key])) {
                        return;
                    }
                    $counts[$key] = (float) $campaign[$key];
                }
                if ($counts['delivered'] + $counts['failed'] + $counts['pending'] !== $counts['total']) {
                    $fail('إجمالي رسائل الواتساب يجب أن يساوي مجموع المسلّم والفاشل وقيد الإرسال.');
                }
            }],
        ];
        foreach (config('auction-report-inputs.integer_metrics') as $metric) {
            $rules['marketing.campaigns.*.'.$metric] = ['nullable', 'integer', 'min:0'];
        }
        foreach (config('auction-report-inputs.decimal_metrics') as $metric) {
            $rules['marketing.campaigns.*.'.$metric] = ['nullable', 'numeric', 'min:0'];
        }
        foreach (config('auction-report-inputs.signed_metrics') as $metric) {
            $rules['marketing.campaigns.*.'.$metric] = ['nullable', 'numeric'];
        }

        return $rules;
    }
}
