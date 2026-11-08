<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('auction_marketing_campaigns', 'campaign_date')) {
            Schema::table('auction_marketing_campaigns', fn (Blueprint $table) => $table->date('campaign_date')->nullable());
        }
        foreach (DB::table('auctions')->select(['id', 'marketing_data'])->whereNotNull('marketing_data')->orderBy('id')->cursor() as $auction) {
            $date = (json_decode($auction->marketing_data, true) ?: [])['whatsapp_date'] ?? null;
            if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (! $parsed || $parsed->format('Y-m-d') !== $date) {
                continue;
            }
            DB::table('auction_marketing_campaigns')->where('auction_id', $auction->id)->where('channel', 'whatsapp')->whereNull('campaign_date')->update(['campaign_date' => $date]);
        }
    }

    public function down(): void
    {
        Schema::table('auction_marketing_campaigns', fn (Blueprint $table) => $table->dropColumn('campaign_date'));
    }
};
