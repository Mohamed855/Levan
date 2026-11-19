<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Tenants\MarketingCampaigns\SaveMarketingCampaignAction;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\Reports\AuctionReportRefreshService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MarketingCampaignBrokerPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'campaign_persist_test',
            'database.connections.campaign_persist_test' => ['driver' => 'sqlite', 'database' => ':memory:'],
        ]);
        Storage::fake('tenant_public');
        $this->app->instance(AuctionReportRefreshService::class, new class
        {
            public function afterMutation(array $ids): void {}
        });

        Schema::create('auctions', function (Blueprint $table): void {
            $table->id();
            $table->json('marketing_data')->nullable();
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
        DB::purge('campaign_persist_test');
        parent::tearDown();
    }

    public function test_batch_save_computes_whatsapp_total_and_stores_unified_social_context(): void
    {
        $auction = Auction::create(['marketing_data' => ['introduction' => 'مقدمة محفوظة']]);

        $saved = (new SaveMarketingCampaignAction)->executeBatch($auction, [
            'social_context' => 'تفاصيل حملات التواصل الموحدة',
            'channels' => ['whatsapp', 'x'],
            'campaigns' => [
                'whatsapp' => [
                    'campaign_date' => '2026-11-10',
                    'url' => 'https://example.com/wa',
                    'metrics' => [
                        'messages' => 100,
                        'delivered' => 80,
                        'failed' => 15,
                        'pending' => 5,
                        'views' => 60,
                    ],
                ],
                'x' => [
                    'url' => 'https://example.com/x',
                    'metrics' => [
                        'views' => 500,
                        'posts' => 4,
                        'x_context' => 'حملة منصة X',
                    ],
                ],
            ],
        ], null, []);

        self::assertCount(2, $saved);
        $wa = $auction->marketingCampaigns()->where('channel', 'whatsapp')->firstOrFail();
        self::assertSame(100, $wa->metrics['total']);

        $freshAuction = $auction->fresh();
        self::assertSame('تفاصيل حملات التواصل الموحدة', $freshAuction->marketing_data['social_context']);
        self::assertSame('مقدمة محفوظة', $freshAuction->marketing_data['introduction']);
    }
}
