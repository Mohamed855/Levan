<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\Tenants\MarketingCampaigns\SaveMarketingCampaignRequest;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\MarketingCampaigns\MarketingCampaign;
use App\Services\Tenants\MarketingCampaigns\MarketingCampaignBrokers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class MarketingCampaignBrokersTest extends TestCase
{
    public function test_empty_legacy_file_caption_keeps_the_saved_broker_name(): void
    {
        $campaign = $this->campaign(['display_name' => 'First', 'broker_second_name' => 'Second']);
        $campaign->evidence[0]->report_metadata = ['display_name' => ''];
        self::assertSame('First', app(MarketingCampaignBrokers::class)->rows($campaign)[0]['name']);
    }

    public function test_legacy_names_and_every_existing_file_are_available_as_editable_rows(): void
    {
        $campaign = $this->campaign(['display_name' => 'First', 'broker_second_name' => 'Second']);
        $rows = app(MarketingCampaignBrokers::class)->rows($campaign);

        self::assertSame(['First', 'Second', 'Second'], array_column($rows, 'name'));
        self::assertSame([11, 12, 13], array_column($rows, 'evidence_id'));
        self::assertCount(3, array_unique(array_column($rows, 'key')));
    }

    public function test_edits_preserve_existing_files_and_update_the_name_for_each_broker(): void
    {
        $campaign = $this->campaign(['brokers' => [['key' => 'one', 'name' => 'Old']]]);
        $campaign->evidence[0]->report_metadata = ['broker_key' => 'one', 'kind' => 'document'];
        $plan = app(MarketingCampaignBrokers::class)->plan($campaign, [
            ['key' => 'one', 'name' => 'Updated', 'evidence_id' => 11],
            ['key' => 'new', 'name' => 'New broker'],
        ], ['new' => UploadedFile::fake()->create('new.pdf', 1, 'application/pdf')]);

        self::assertSame([['key' => 'one', 'name' => 'Updated'], ['key' => 'new', 'name' => 'New broker']], $plan['brokers']);
        self::assertSame([12, 13], $plan['remove_ids']);
        self::assertSame('Updated', $plan['existing'][11]['metadata']['display_name']);
        self::assertSame('one', $plan['existing'][11]['metadata']['broker_key']);
        self::assertSame('New broker', $plan['uploads']['new']['metadata']['display_name']);
    }

    public function test_replacement_removes_only_the_replaced_or_deleted_rows(): void
    {
        $plan = app(MarketingCampaignBrokers::class)->plan($this->campaign([]), [
            ['key' => 'a', 'name' => 'A', 'evidence_id' => 11],
            ['key' => 'b', 'name' => 'B', 'evidence_id' => 12],
        ], ['a' => UploadedFile::fake()->create('new.pdf', 1, 'application/pdf')]);

        self::assertSame([11, 13], $plan['remove_ids']);
        self::assertArrayHasKey(12, $plan['existing']);
    }

    public function test_another_campaigns_file_cannot_be_claimed(): void
    {
        $this->expectException(ValidationException::class);
        app(MarketingCampaignBrokers::class)->plan($this->campaign([]), [['key' => 'a', 'name' => 'A', 'evidence_id' => 999]], []);
    }

    public function test_every_new_broker_requires_its_own_file(): void
    {
        $this->expectException(ValidationException::class);
        app(MarketingCampaignBrokers::class)->plan(null, [['key' => 'a', 'name' => 'A']], []);
    }

    public function test_broker_form_renders_repeatable_names_and_one_file_per_row(): void
    {
        view()->share('errors', new ViewErrorBag);
        $campaign = new MarketingCampaign(['metrics' => ['brokers' => [
            ['key' => 'a', 'name' => '<One>'], ['key' => 'b', 'name' => 'Two'], ['key' => 'c', 'name' => 'Three'],
        ]]]);
        $campaign->setRelation('evidence', new Collection);
        $html = view('tenant.marketing-campaigns.partials.brokers', ['campaign' => $campaign])->render();

        self::assertStringContainsString('campaigns[brokers][metrics][brokers][2][name]', $html);
        self::assertStringContainsString('campaigns[brokers][evidence][c]', $html);
        self::assertStringContainsString('&lt;One&gt;', $html);
        self::assertStringContainsString('data-add-broker', $html);
        self::assertStringNotContainsString('broker_second_name', $html);
    }

    public function test_single_and_batch_rules_accept_three_brokers_and_reject_duplicate_keys(): void
    {
        $metrics = ['contract_text' => 'Contract', 'brokers' => [
            ['key' => 'a', 'name' => 'One'], ['key' => 'b', 'name' => 'Two'], ['key' => 'c', 'name' => 'Three'],
        ]];
        foreach ([false, true] as $batch) {
            $data = $batch ? ['channels' => ['brokers'], 'campaigns' => ['brokers' => ['metrics' => $metrics]]] : ['channel' => 'brokers', 'metrics' => $metrics];
            $prefix = $batch ? 'campaigns.brokers.metrics' : 'metrics';
            $request = SaveMarketingCampaignRequest::create('/', 'POST', $data);
            $rules = array_filter($request->rules(), fn (string $key): bool => str_starts_with($key, $prefix), ARRAY_FILTER_USE_KEY);
            self::assertFalse(Validator::make($data, $rules)->fails());
            data_set($data, $prefix.'.brokers.2.key', 'a');
            self::assertTrue(Validator::make($data, $rules)->errors()->has($prefix.'.brokers.2.key'));
        }
    }

    private function campaign(array $metrics): MarketingCampaign
    {
        $campaign = new MarketingCampaign(['channel' => 'brokers', 'metrics' => $metrics]);
        $campaign->setRelation('evidence', new Collection(array_map(function (int $id): AuctionAttachment {
            $file = new AuctionAttachment(['file_path' => "broker-{$id}.pdf", 'report_metadata' => []]);
            $file->id = $id;

            return $file;
        }, [11, 12, 13])));

        return $campaign;
    }
}
