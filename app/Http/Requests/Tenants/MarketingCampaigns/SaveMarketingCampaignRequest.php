<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\MarketingCampaigns;

use App\Services\Tenants\Auctions\Reports\AuctionReportInputCapacity;
use App\Services\Tenants\Auctions\Reports\AuctionReportLinks;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SaveMarketingCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can($this->route('campaign') ? 'marketing_campaigns.edit' : 'marketing_campaigns.create') ?? false;
    }

    public function rules(): array
    {
        $channel = $this->input('channel');
        $metrics = is_string($channel) ? config('auction-marketing-campaigns.'.$channel, []) : [];
        $rules = ['auction_id' => ['required', 'integer', Rule::exists('auctions', 'id')->whereNull('deleted_at')], 'channel' => ['required', Rule::in(array_keys(config('auction-marketing-campaigns')))], 'campaign_date' => ['exclude_unless:channel,whatsapp', 'nullable', 'date'], 'metrics' => ['nullable', Rule::array($metrics)], 'url' => [Rule::excludeIf(! in_array($channel, config('auction-marketing-fields.links'), true)), 'nullable', 'url:https', 'max:2048'], 'evidence' => [Rule::excludeIf(! in_array($channel, config('auction-marketing-fields.evidence'), true)), 'nullable', 'array', 'max:50'], 'evidence.*' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'], 'remove_evidence' => ['nullable', 'array'], 'remove_evidence.*' => ['integer', 'distinct']];
        foreach ($metrics as $metric) {
            if (array_key_exists($metric, config('auction-marketing-fields.text'))) {
                $rules['metrics.'.$metric] = ['nullable', 'string', 'max:10000'];

                continue;
            }
            if ($metric === 'property_id') {
                $rules['metrics.property_id'] = ['required', 'integer', Rule::exists('auction_properties', 'property_id')->where('auction_id', is_scalar($this->input('auction_id')) ? $this->input('auction_id') : 0)];

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

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
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
