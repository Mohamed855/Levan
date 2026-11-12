<?php

declare(strict_types=1);

use App\Services\Tenants\Auctions\Reports\AuctionReportChannels;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_marketing_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('auction_id')->constrained('auctions')->cascadeOnDelete();
            $table->string('channel', 40);
            $table->string('platform_label')->nullable();
            $table->json('metrics')->nullable();
            $table->text('url')->nullable();
            $table->string('link_label')->nullable();
            $table->string('link_role', 40)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->date('campaign_date')->nullable();
            $table->string('legacy_key', 64)->nullable()->unique();
            $table->timestamps();
        });

        foreach (DB::table('auctions')->select(['id', 'marketing_data'])->whereNotNull('marketing_data')->orderBy('id')->cursor() as $auction) {
            $marketing = json_decode($auction->marketing_data, true) ?: [];
            foreach ($marketing['campaigns'] ?? [] as $index => $campaign) {
                if (! is_array($campaign) || empty($campaign['platform'])) {
                    continue;
                }
                $channel = AuctionReportChannels::key($campaign['platform']);
                $metrics = array_intersect_key($campaign, array_flip(array_merge(config('auction-report-inputs.integer_metrics', []), config('auction-report-inputs.decimal_metrics', []), config('auction-report-inputs.signed_metrics', []))));
                if (array_filter($metrics, static fn ($value) => $value !== null && $value !== '') === [] && empty($campaign['url'])) {
                    continue;
                }
                DB::table('auction_marketing_campaigns')->insertOrIgnore(['auction_id' => $auction->id, 'channel' => $channel, 'platform_label' => $campaign['platform_label'] ?? ($channel === 'other' ? $campaign['platform'] : null), 'metrics' => json_encode($metrics), 'url' => $campaign['url'] ?? null, 'link_label' => $campaign['link_label'] ?? null, 'link_role' => $campaign['link_role'] ?? null, 'legacy_key' => hash('sha256', $auction->id.':'.$index), 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_marketing_campaigns');
    }
};
