<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Http\Requests\Admin\AuctionTemplates\StoreAuctionTemplateRequest;
use App\Http\Requests\Admin\AuctionTemplates\UpdateAuctionTemplateRequest;
use App\Http\Requests\Admin\Faqs\StoreFaqRequest;
use App\Http\Requests\Admin\Faqs\UpdateFaqRequest;
use App\Http\Requests\Admin\Plans\PlanRequest;
use App\Http\Requests\Admin\PublicRequests\RejectTrialRequest;
use App\Http\Requests\Admin\PublicRequests\ReplyContactRequest;
use App\Http\Requests\Admin\Subscriptions\StoreSubscriptionRequest;
use App\Http\Requests\Concerns\AuctionReportDetailRules;
use App\Http\Requests\Site\StoreContactRequest;
use App\Http\Requests\Tenants\Assignments\StoreAssignmentRequest;
use App\Http\Requests\Tenants\Assignments\UpdateAssignmentRequest;
use App\Http\Requests\Tenants\AuctionClosings\SaveAuctionClosingRequest;
use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenants\Auctions\UpdateAuctionRequest;
use App\Http\Requests\Tenants\MarketingCampaigns\SaveMarketingCampaignRequest;
use App\Http\Requests\Tenants\Properties\StorePropertyRequest;
use App\Http\Requests\Tenants\Properties\UpdatePropertyRequest;
use App\Http\Requests\Tenants\Tickets\TicketRequest;
use App\Services\Tenants\AuctionEstablishments\AuctionEstablishmentRequestData;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class TextareaBackendMaxLengthTest extends TestCase
{
    private function assertFieldMaxLength1000(array $rules, string $field, string $ruleKey): void
    {
        $this->assertArrayHasKey($ruleKey, $rules, "Missing validation rule for {$ruleKey}.");

        $fieldRules = [$ruleKey => $rules[$ruleKey]];
        $atLimit = [];
        data_set($atLimit, $field, str_repeat('أ', 1000));
        $tooLong = [];
        data_set($tooLong, $field, str_repeat('أ', 1001));

        $validatorAtLimit = Validator::make($atLimit, $fieldRules);
        $this->assertFalse(
            $validatorAtLimit->errors()->has($field),
            "Expected {$field} to accept 1000 characters, got: ".json_encode($validatorAtLimit->errors()->get($field), JSON_UNESCAPED_UNICODE)
        );

        $validatorTooLong = Validator::make($tooLong, $fieldRules);
        $this->assertTrue(
            $validatorTooLong->errors()->has($field),
            "Expected {$field} to reject 1001 characters."
        );
    }

    public function test_all_textarea_component_backed_requests_enforce_1000_character_limit(): void
    {
        $this->assertFieldMaxLength1000((new StoreAuctionTemplateRequest)->rules(), 'description', 'description');
        $this->assertFieldMaxLength1000((new UpdateAuctionTemplateRequest)->rules(), 'description', 'description');

        $this->assertFieldMaxLength1000((new StoreFaqRequest)->rules(), 'answer', 'answer');
        $this->assertFieldMaxLength1000((new UpdateFaqRequest)->rules(), 'answer', 'answer');

        $planRules = (new PlanRequest)->rules();
        $this->assertFieldMaxLength1000($planRules, 'description', 'description');
        $this->assertFieldMaxLength1000($planRules, 'admin_notes', 'admin_notes');

        $this->assertFieldMaxLength1000((new ReplyContactRequest)->rules(), 'reply', 'reply');
        $this->assertFieldMaxLength1000((new RejectTrialRequest)->rules(), 'rejection_reason', 'rejection_reason');
        $this->assertFieldMaxLength1000((new StoreSubscriptionRequest)->rules(), 'admin_notes', 'admin_notes');
        $this->assertFieldMaxLength1000((new StoreContactRequest)->rules(), 'message', 'message');
        $this->assertFieldMaxLength1000((new TicketRequest)->rules(), 'description', 'description');

        $this->assertFieldMaxLength1000((new StoreAssignmentRequest)->rules(), 'notes', 'notes');
        $this->assertFieldMaxLength1000((new UpdateAssignmentRequest)->rules(), 'notes', 'notes');

        $closingRules = (new SaveAuctionClosingRequest)->rules();
        $this->assertFieldMaxLength1000($closingRules, 'results_narrative', 'results_narrative');
        $this->assertFieldMaxLength1000($closingRules, 'results.0.unsold_reason', 'results.*.unsold_reason');
        $this->assertFieldMaxLength1000($closingRules, 'results.0.result_notes', 'results.*.result_notes');

        $establishmentRules = app(AuctionEstablishmentRequestData::class)->rules();
        $this->assertFieldMaxLength1000($establishmentRules, 'data.properties.0.disclosure', 'data.properties.*.disclosure');
        $this->assertFieldMaxLength1000($establishmentRules, 'data.disclosures.disposition_right_other', 'data.disclosures.disposition_right_other');
        foreach (['real_rights', 'dispute', 'value_information', 'off_plan', 'contribution'] as $key) {
            $this->assertFieldMaxLength1000($establishmentRules, "data.disclosures.{$key}_details", "data.disclosures.{$key}_details");
        }

        foreach ([new StoreAuctionRequest, new UpdateAuctionRequest] as $auctionRequest) {
            $rules = $auctionRequest->rules();
            $this->assertFieldMaxLength1000($rules, 'description', 'description');
            $this->assertFieldMaxLength1000($rules, 'notes', 'notes');
            $this->assertFieldMaxLength1000($rules, 'marketing.introduction', 'marketing.introduction');
            $this->assertFieldMaxLength1000($rules, 'marketing.report_summary', 'marketing.report_summary');
        }

        $reportDetailRules = (new class
        {
            use AuctionReportDetailRules;

            public function rules(): array
            {
                return $this->reportDetailRules();
            }
        })->rules();
        $this->assertFieldMaxLength1000($reportDetailRules, 'marketing.introduction', 'marketing.introduction');
        $this->assertFieldMaxLength1000($reportDetailRules, 'marketing.results_narrative', 'marketing.results_narrative');

        $campaignRequest = SaveMarketingCampaignRequest::create('/', 'POST', [
            'channels' => ['x', 'brokers'],
            'campaigns' => [
                'x' => ['metrics' => ['x_context' => '']],
                'brokers' => ['metrics' => ['contract_text' => '', 'brokers' => [['key' => 'b1', 'name' => 'Broker']]]],
            ],
        ]);
        $campaignRules = $campaignRequest->rules();
        $this->assertFieldMaxLength1000($campaignRules, 'campaigns.x.metrics.x_context', 'campaigns.x.metrics.x_context');
        $this->assertFieldMaxLength1000($campaignRules, 'campaigns.brokers.metrics.contract_text', 'campaigns.brokers.metrics.contract_text');

        foreach ([new StorePropertyRequest, new UpdatePropertyRequest] as $propertyRequest) {
            $rules = $propertyRequest->rules();
            $this->assertFieldMaxLength1000($rules, 'short_description', 'short_description');
            $this->assertFieldMaxLength1000($rules, 'notes', 'notes');
        }
    }
}
