<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

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
