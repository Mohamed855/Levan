<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Tenants\MarketingCampaigns\SaveMarketingCampaignAction;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\MarketingCampaigns\MarketingCampaign;
use App\Services\Tenants\Auctions\Reports\AuctionReportRefreshService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class MarketingCampaignBrokerPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'broker_test',
            'database.connections.broker_test' => ['driver' => 'sqlite', 'database' => ':memory:'],
        ]);
        Storage::fake('tenant_public');
        // Report generation is a separate subsystem; these tests exercise the real save and files.
        $this->app->instance(AuctionReportRefreshService::class, new class
        {
            public function afterMutation(array $ids): void {}
        });
        Schema::create('auctions', function (Blueprint $table): void {
            $table->id();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('auction_properties', function (Blueprint $table): void {
            foreach (['auction_id', 'property_id', 'sort_order', 'result_status', 'opening_price', 'sale_price', 'participants_count', 'bids_count', 'unsold_reason', 'board_data', 'buyer_name', 'result_notes'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('auction_establishment_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('auction_id');
        });
        Schema::create('auction_closings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('auction_id');
            $table->text('finalized_snapshot')->nullable();
        });
        Schema::create('auction_marketing_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('auction_id');
            foreach (['channel', 'campaign_date', 'platform_label', 'metrics', 'url', 'link_label', 'link_role', 'created_by'] as $column) {
                $table->text($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('auction_attachments', function (Blueprint $table): void {
            $table->id();
            foreach (['auction_id', 'marketing_campaign_id', 'property_id', 'file_path', 'file_name', 'file_type', 'file_size', 'category', 'report_section', 'report_metadata', 'display_order', 'channels'] as $column) {
                $table->text($column)->nullable();
            }
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('broker_test');
        parent::tearDown();
    }

    public static function modes(): array
    {
        return ['single' => [false], 'batch' => [true]];
    }

    #[DataProvider('modes')]
    public function test_save_reload_rename_replace_and_remove_keep_each_file_with_its_broker(bool $batch): void
    {
        $auction = Auction::create([]);
        $rows = [['key' => 'a', 'name' => 'Alpha'], ['key' => 'b', 'name' => 'Beta'], ['key' => 'c', 'name' => 'Gamma']];
        $files = [];
        foreach ($rows as $row) {
            $files[$row['key']] = UploadedFile::fake()->create($row['key'].'.pdf', 1, 'application/pdf');
        }
        $campaign = $this->save($auction, null, $rows, $files, $batch)->fresh();
        self::assertCount(3, $campaign->metrics['brokers']);
        $evidence = $campaign->evidence->keyBy(fn ($file) => $file->report_metadata['broker_key']);
        self::assertSame('Beta', $evidence['b']->report_metadata['display_name']);
        self::assertSame('brokers', $evidence['c']->report_section);
        $oldA = $evidence['a']->file_path;
        $oldB = $evidence['b']->file_path;
        $oldC = $evidence['c']->file_path;

        $campaign = $this->save($auction, $campaign, [
            ['key' => 'b', 'name' => 'Renamed', 'evidence_id' => $evidence['b']->id],
            ['key' => 'a', 'name' => 'Alpha', 'evidence_id' => $evidence['a']->id],
        ], ['a' => UploadedFile::fake()->create('replacement.pdf', 1, 'application/pdf')], $batch)->fresh();

        self::assertSame([['key' => 'b', 'name' => 'Renamed'], ['key' => 'a', 'name' => 'Alpha']], $campaign->metrics['brokers']);
        self::assertCount(2, $campaign->evidence);
        self::assertSame('Renamed', $campaign->evidence[0]->report_metadata['display_name']);
        self::assertSame($oldB, $campaign->evidence[0]->file_path);
        self::assertSame(0, $campaign->evidence[0]->display_order);
        self::assertSame('Alpha', $campaign->evidence[1]->report_metadata['display_name']);
        Storage::disk('tenant_public')->assertExists($oldB);
        Storage::disk('tenant_public')->assertMissing($oldA);
        Storage::disk('tenant_public')->assertMissing($oldC);
    }

    #[DataProvider('modes')]
    public function test_failed_replacement_storage_preserves_the_original_broker_and_file(bool $batch): void
    {
        $auction = Auction::create([]);
        $campaign = $this->save($auction, null, [['key' => 'a', 'name' => 'Original']], [
            'a' => UploadedFile::fake()->create('original.pdf', 1, 'application/pdf'),
        ], $batch)->fresh();
        $original = $campaign->evidence()->sole();
        $disk = Storage::disk('tenant_public');
        $failingDisk = \Mockery::mock($disk);
        $failingDisk->shouldReceive('putFileAs')->once()->andReturnFalse();
        Storage::set('tenant_public', $failingDisk);
        $error = null;

        try {
            $this->save($auction, $campaign, [['key' => 'a', 'name' => 'Changed', 'evidence_id' => $original->id]], [
                'a' => UploadedFile::fake()->create('replacement.pdf', 1, 'application/pdf'),
            ], $batch);
        } catch (\RuntimeException $exception) {
            $error = $exception;
        }

        self::assertNotNull($error, 'A failed upload must reject the replacement instead of committing it.');
        self::assertSame([['key' => 'a', 'name' => 'Original']], $campaign->fresh()->metrics['brokers']);
        $retained = $campaign->fresh()->evidence()->sole();
        self::assertSame($original->id, $retained->id);
        self::assertSame($original->file_path, $retained->file_path);
        $disk->assertExists($original->file_path);
        self::assertSame([$original->file_path], $disk->allFiles());
    }

    private function save(Auction $auction, ?MarketingCampaign $campaign, array $rows, array $files, bool $batch): MarketingCampaign
    {
        $metrics = ['brokers' => $rows, 'contract_text' => 'Contract'];
        $action = new SaveMarketingCampaignAction;
        if ($batch) {
            return $action->executeBatch($auction, ['channels' => ['brokers'], 'campaigns' => ['brokers' => ['metrics' => $metrics]]], null, ['brokers' => ['evidence' => $files]])[0];
        }

        return $action->execute($campaign, ['auction_id' => $auction->id, 'channel' => 'brokers', 'metrics' => $metrics], null, $files);
    }
}
