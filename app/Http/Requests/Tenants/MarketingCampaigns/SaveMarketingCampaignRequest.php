<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\MarketingCampaigns;

use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\Reports\AuctionReportInputCapacity;
use App\Services\Tenants\Auctions\Reports\AuctionReportLinks;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SaveMarketingCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $hasTarget = $this->route('campaign') || $this->route('auction');

        return $this->user('tenant')?->can($hasTarget ? 'marketing_campaigns.edit' : 'marketing_campaigns.create') ?? false;
    }

    public function rules(): array
    {
        $auctionId = $this->input('auction_id');

        // Check if batch payload (campaigns array) is submitted
        if ($this->has('channels') || $this->has('campaigns')) {
            $channels = (array) $this->input('channels', []);
            $rules = [
                'auction_id' => ['required', 'integer', Rule::exists('auctions', 'id')->whereNull('deleted_at')],
                'channels' => ['required', 'array', 'min:1'],
                'channels.*' => ['required', 'string', Rule::in(array_keys(config('auction-marketing-campaigns')))],
                'campaigns' => ['required', 'array'],
            ];

            foreach ($channels as $channel) {
                if (! is_string($channel) || ! array_key_exists($channel, config('auction-marketing-campaigns'))) {
                    continue;
                }

                $prefix = "campaigns.{$channel}";
                $metrics = config("auction-marketing-campaigns.{$channel}", []);
                $rules["{$prefix}"] = ['nullable', 'array'];
                $rules["{$prefix}.campaign_date"] = ['nullable', 'date'];
                $rules["{$prefix}.url"] = [
                    Rule::excludeIf(! in_array($channel, config('auction-marketing-fields.links'), true)),
                    'nullable',
                    'url:https',
                    'max:2048',
                ];
                $rules["{$prefix}.evidence"] = [
                    Rule::excludeIf(! in_array($channel, config('auction-marketing-fields.evidence'), true)),
                    'nullable',
                    'array',
                    'max:50',
                ];
                $rules["{$prefix}.evidence.*"] = ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'];
                $rules["{$prefix}.remove_evidence"] = ['nullable', 'array'];
                $rules["{$prefix}.remove_evidence.*"] = ['integer', 'distinct'];

                if ($metrics !== []) {
                    $rules["{$prefix}.metrics"] = ['nullable', 'array'];
                    foreach ($metrics as $metric) {
                        $metricKey = "{$prefix}.metrics.{$metric}";
                        if (array_key_exists($metric, config('auction-marketing-fields.text'))) {
                            $rules[$metricKey] = ['nullable', 'string', 'max:10000'];
                            continue;
                        }
                        if ($metric === 'property_id') {
                            $rules[$metricKey] = [
                                'required',
                                'integer',
                                Rule::exists('auction_properties', 'property_id')
                                    ->where('auction_id', is_scalar($auctionId) ? $auctionId : 0),
                            ];
                            continue;
                        }
                        $rules[$metricKey] = ['nullable', in_array($metric, config('auction-report-inputs.integer_metrics')) ? 'integer' : 'numeric'];
                        if ($metric === 'board_count') {
                            $rules[$metricKey] = ['nullable', 'integer'];
                        }
                        if (! in_array($metric, config('auction-report-inputs.signed_metrics'))) {
                            $rules[$metricKey][] = 'min:0';
                        }
                    }
                }
            }

            return $rules;
        }

        // Backwards compatibility for single campaign payload
        $channel = $this->input('channel');
        $metrics = is_string($channel) ? config('auction-marketing-campaigns.'.$channel, []) : [];
        $rules = [
            'auction_id' => ['required', 'integer', Rule::exists('auctions', 'id')->whereNull('deleted_at')],
            'channel' => ['required', Rule::in(array_keys(config('auction-marketing-campaigns')))],
            'campaign_date' => ['exclude_unless:channel,whatsapp', 'nullable', 'date'],
            'metrics' => ['nullable', Rule::array($metrics)],
            'url' => [Rule::excludeIf(! in_array($channel, config('auction-marketing-fields.links'), true)), 'nullable', 'url:https', 'max:2048'],
            'evidence' => [Rule::excludeIf(! in_array($channel, config('auction-marketing-fields.evidence'), true)), 'nullable', 'array', 'max:50'],
            'evidence.*' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
            'remove_evidence' => ['nullable', 'array'],
            'remove_evidence.*' => ['integer', 'distinct'],
        ];

        foreach ($metrics as $metric) {
            if (array_key_exists($metric, config('auction-marketing-fields.text'))) {
                $rules['metrics.'.$metric] = ['nullable', 'string', 'max:10000'];
                continue;
            }
            if ($metric === 'property_id') {
                $rules['metrics.property_id'] = [
                    'required',
                    'integer',
                    Rule::exists('auction_properties', 'property_id')->where('auction_id', is_scalar($auctionId) ? $auctionId : 0),
                ];
                continue;
            }
            $rules['metrics.'.$metric] = ['nullable', in_array($metric, config('auction-report-inputs.integer_metrics')) ? 'integer' : 'numeric'];
            if ($metric === 'board_count') {
                $rules['metrics.'.$metric] = ['nullable', 'integer'];
            }
            if (! in_array($metric, config('auction-report-inputs.signed_metrics'))) {
                $rules['metrics.'.$metric][] = 'min:0';
            }
        }
        if ($metrics === []) {
            $rules['metrics'] = ['prohibited'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $metricLabels = [
            'property_id' => 'العقار',
            'views' => 'عدد المشاهدات',
            'reach' => 'الوصول',
            'impressions' => 'مرات الظهور',
            'posts' => 'عدد المنشورات',
            'subscribers' => 'عدد المشتركين',
            'watch_hours' => 'ساعات المشاهدة',
            'messages' => 'عدد الرسائل',
            'delivered' => 'المسلّمة',
            'failed' => 'الفاشلة',
            'pending' => 'قيد الإرسال',
            'total' => 'الإجمالي',
            'profile_views' => 'مشاهدات الملف التعريفي',
            'profile_change' => 'نسبة تغير الملف التعريفي',
            'reach_change' => 'نسبة تغير الوصول',
            'views_change' => 'نسبة تغير المشاهدات',
            'board_count' => 'عدد اللوحات',
            'board_dimensions' => 'مقاس اللوحات',
            'board_context' => 'تفاصيل تركيب اللوحات',
            'display_name' => 'اسم الوسيط',
            'broker_second_name' => 'اسم الوسيط الثاني',
            'contract_text' => 'نص التعاقد مع الوسيط',
        ];

        $textFields = config('auction-marketing-fields.text', []);
        foreach ($textFields as $key => $label) {
            $metricLabels[$key] = $label;
        }

        $channelLabels = __('auction_reports.channels', [], 'ar') ?: [];

        $attributes = [
            'auction_id' => 'المزاد',
            'channels' => 'أنواع الحملات',
            'channels.*' => 'نوع الحملة',
            'channel' => 'نوع الحملة',
            'campaign_date' => 'تاريخ الحملة',
            'url' => 'رابط الحملة',
            'evidence' => 'أدلة الحملة',
            'evidence.*' => 'أدلة الحملة',
            'remove_evidence' => 'حذف الأدلة',
            'remove_evidence.*' => 'حذف الدليل',
            'metrics' => 'بيانات المؤشرات',
            'campaigns' => 'الحملات التسويقية',
            'campaigns.*' => 'الحملة التسويقية',
            'campaigns.*.campaign_date' => 'تاريخ الحملة',
            'campaigns.*.url' => 'رابط الحملة',
            'campaigns.*.evidence' => 'أدلة الحملة',
            'campaigns.*.evidence.*' => 'أدلة الحملة',
            'campaigns.*.remove_evidence' => 'حذف الأدلة',
            'campaigns.*.remove_evidence.*' => 'حذف الدليل',
            'campaigns.*.metrics' => 'بيانات المؤشرات',
        ];

        foreach ($metricLabels as $metric => $label) {
            $attributes["metrics.{$metric}"] = $label;
            $attributes["campaigns.*.metrics.{$metric}"] = $label;
            foreach (array_keys(config('auction-marketing-campaigns')) as $ch) {
                $chName = $channelLabels[$ch] ?? $ch;
                $attributes["campaigns.{$ch}.campaign_date"] = "تاريخ حملة ({$chName})";
                $attributes["campaigns.{$ch}.url"] = "رابط حملة ({$chName})";
                $attributes["campaigns.{$ch}.evidence"] = "أدلة حملة ({$chName})";
                $attributes["campaigns.{$ch}.evidence.*"] = "دليل حملة ({$chName})";
                $attributes["campaigns.{$ch}.metrics.{$metric}"] = "{$label} في حملة ({$chName})";
            }
        }

        return $attributes;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Batch mode validation
            if ($this->has('channels') || $this->has('campaigns')) {
                $channels = (array) $this->input('channels', []);
                $campaignsData = (array) $this->input('campaigns', []);
                $auction = Auction::find($this->input('auction_id'));

                foreach ($channels as $channel) {
                    if (! is_string($channel) || ! array_key_exists($channel, config('auction-marketing-campaigns'))) {
                        continue;
                    }

                    $channelData = (array) ($campaignsData[$channel] ?? []);
                    $values = (array) ($channelData['metrics'] ?? []);

                    foreach ($values as $field => $value) {
                        if (is_string($value) && array_key_exists($field, config('auction-marketing-fields.text')) && ! app(AuctionReportInputCapacity::class)->fits($field, $value)) {
                            $validator->errors()->add("campaigns.{$channel}.metrics.{$field}", 'النص أطول من المساحة المتاحة في التقرير. اختصر النص ثم احفظ.');
                        }
                    }

                    unset($values['property_id']);
                    $hasMetric = count(array_filter($values, static fn ($v) => $v !== null && $v !== '')) > 0;
                    $hasFiles = $this->hasFile("campaigns.{$channel}.evidence");
                    $existingCampaign = $auction?->marketingCampaigns()->where('channel', $channel)->first();
                    $removeList = (array) ($channelData['remove_evidence'] ?? []);
                    $hasExistingEvidence = $existingCampaign && $existingCampaign->evidence()->whereNotIn('id', $removeList)->exists();
                    $hasEvidence = in_array($channel, config('auction-marketing-fields.evidence'), true) && ($hasFiles || $hasExistingEvidence);
                    $url = $channelData['url'] ?? null;
                    $hasUrl = in_array($channel, config('auction-marketing-fields.links'), true) && ! empty($url);

                    if ($hasUrl && app(AuctionReportLinks::class)->safeUrl($url) === null) {
                        $validator->errors()->add("campaigns.{$channel}.url", 'أدخل رابط HTTPS صالحًا دون بيانات دخول.');
                    }

                    if (! $hasMetric && ! $hasUrl && ! $hasEvidence && $channel !== 'marketing_evidence') {
                        $validator->errors()->add("campaigns.{$channel}.metrics", 'أدخل بيانات الحملة أو رابطها أو أرفق دليلًا واحدًا على الأقل.');
                    }

                    if ($channel === 'whatsapp') {
                        $waValues = (array) ($channelData['metrics'] ?? []);
                        $keys = ['total', 'delivered', 'failed', 'pending'];
                        $presentKeys = array_keys(array_filter($waValues, fn ($v) => $v !== null && $v !== ''));
                        if (count(array_intersect($keys, $presentKeys)) === 4) {
                            if ((float) $waValues['total'] !== (float) $waValues['delivered'] + (float) $waValues['failed'] + (float) $waValues['pending']) {
                                $validator->errors()->add("campaigns.{$channel}.metrics.total", 'الإجمالي يجب أن يساوي المسلّم والفاشل وقيد الإرسال.');
                            }
                        }
                    }
                }

                return;
            }

            // Single mode validation
            $channel = $this->string('channel')->toString();
            $values = (array) $this->input('metrics', []);
            foreach ($values as $field => $value) {
                if (is_string($value) && array_key_exists($field, config('auction-marketing-fields.text')) && ! app(AuctionReportInputCapacity::class)->fits($field, $value)) {
                    $validator->errors()->add('metrics.'.$field, 'النص أطول من المساحة المتاحة في التقرير. اختصر النص ثم احفظ.');
                }
            }
            unset($values['property_id']);
            $hasMetric = count(array_filter($values, static fn ($value) => $value !== null && $value !== '')) > 0;
            $campaign = $this->route('campaign');
            $hasEvidence = in_array($channel, config('auction-marketing-fields.evidence'), true) && ($this->hasFile('evidence') || ($campaign && $campaign->evidence()->whereNotIn('id', (array) $this->input('remove_evidence', []))->exists()));
            $hasUrl = in_array($channel, config('auction-marketing-fields.links'), true) && $this->filled('url');
            if ($hasUrl && app(AuctionReportLinks::class)->safeUrl($this->input('url')) === null) {
                $validator->errors()->add('url', 'أدخل رابط HTTPS صالحًا دون بيانات دخول.');
            }
            if (! $hasMetric && ! $hasUrl && ! $hasEvidence) {
                $validator->errors()->add('metrics', 'أدخل بيانات الحملة أو رابطها أو أرفق دليلًا واحدًا على الأقل.');
            }
            if ($this->input('channel') === 'whatsapp') {
                $values = (array) $this->input('metrics', []);
                if (count(array_intersect(['total', 'delivered', 'failed', 'pending'], array_keys(array_filter($values, fn ($v) => $v !== null && $v !== '')))) === 4 && (float) $values['total'] !== (float) $values['delivered'] + (float) $values['failed'] + (float) $values['pending']) {
                    $validator->errors()->add('metrics.total', 'الإجمالي يجب أن يساوي المسلّم والفاشل وقيد الإرسال.');
                }
            }
        }];
    }
}
