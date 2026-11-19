<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const REMOVED_CHANNELS = [
        'planning',
        'promotion',
        'brochure',
        'platform',
        'board_before',
        'brokers',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('auction_marketing_campaigns') || ! Schema::hasTable('auctions')) {
            return;
        }

        $auctions = DB::table('auctions')
            ->select(['id', 'marketing_data', 'notes'])
            ->orderBy('id')
            ->get();

        foreach ($auctions as $auction) {
            $marketing = json_decode((string) ($auction->marketing_data ?? ''), true);
            if (! is_array($marketing)) {
                $marketing = [];
            }

            $campaigns = DB::table('auction_marketing_campaigns')
                ->where('auction_id', $auction->id)
                ->orderBy('id')
                ->get();

            $marketingChanged = false;
            $socialContexts = [];

            foreach ($campaigns as $campaign) {
                $metrics = json_decode((string) ($campaign->metrics ?? ''), true);
                if (! is_array($metrics)) {
                    $metrics = [];
                }

                if (in_array($campaign->channel, ['x', 'snapchat', 'tiktok'], true) && filled($metrics['social_context'] ?? null)) {
                    $socialContexts[] = trim((string) $metrics['social_context']);
                    unset($metrics['social_context']);
                    if (in_array($campaign->channel, ['x', 'snapchat'], true)) {
                        unset($metrics['impressions']);
                    }
                    DB::table('auction_marketing_campaigns')
                        ->where('id', $campaign->id)
                        ->update(['metrics' => json_encode($metrics, JSON_UNESCAPED_UNICODE)]);
                }

                if ($campaign->channel === 'whatsapp') {
                    $delivered = (int) ($metrics['delivered'] ?? 0);
                    $failed = (int) ($metrics['failed'] ?? 0);
                    $pending = (int) ($metrics['pending'] ?? 0);
                    $metrics['total'] = $delivered + $failed + $pending;
                    DB::table('auction_marketing_campaigns')
                        ->where('id', $campaign->id)
                        ->update(['metrics' => json_encode($metrics, JSON_UNESCAPED_UNICODE)]);
                }

                if (! in_array($campaign->channel, self::REMOVED_CHANNELS, true)) {
                    continue;
                }

                if ($campaign->channel === 'planning') {
                    foreach (['planning_methodology', 'brochure_methodology'] as $key) {
                        if (empty($marketing[$key]) && filled($metrics[$key] ?? null)) {
                            $marketing[$key] = (string) $metrics[$key];
                            $marketingChanged = true;
                        }
                    }
                    if (empty($marketing['city_context']) && filled($metrics['city_context'] ?? null)) {
                        $rawCityContext = $metrics['city_context'];
                        $marketing['city_context'] = is_array($rawCityContext)
                            ? array_values(array_filter(array_map('trim', $rawCityContext)))
                            : array_values(array_filter(array_map('trim', preg_split('/[،,]+/u', (string) $rawCityContext) ?: [])));
                        $marketingChanged = true;
                    }
                } elseif ($campaign->channel === 'promotion') {
                    foreach (['audience_methodology', 'messages_methodology'] as $key) {
                        if (empty($marketing[$key]) && filled($metrics[$key] ?? null)) {
                            $marketing[$key] = (string) $metrics[$key];
                            $marketingChanged = true;
                        }
                    }
                } elseif ($campaign->channel === 'brochure') {
                    if (empty($marketing['brochure_context']) && filled($metrics['brochure_context'] ?? null)) {
                        $marketing['brochure_context'] = (string) $metrics['brochure_context'];
                        $marketingChanged = true;
                    }
                    if (empty($marketing['brochure_url']) && filled($campaign->url)) {
                        $marketing['brochure_url'] = (string) $campaign->url;
                        $marketingChanged = true;
                    }
                } elseif ($campaign->channel === 'board_before') {
                    if (empty($marketing['board_context']) && filled($metrics['board_context'] ?? null)) {
                        $marketing['board_context'] = (string) $metrics['board_context'];
                        $marketingChanged = true;
                    }
                    if (empty($marketing['board_url']) && filled($campaign->url)) {
                        $marketing['board_url'] = (string) $campaign->url;
                        $marketingChanged = true;
                    }
                    $propertyId = (int) ($metrics['property_id'] ?? 0);
                    if ($propertyId > 0 && Schema::hasTable('auction_properties')) {
                        $pivot = DB::table('auction_properties')
                            ->where('auction_id', $auction->id)
                            ->where('property_id', $propertyId)
                            ->first();
                        if ($pivot) {
                            $boardData = json_decode((string) ($pivot->board_data ?? ''), true);
                            if (! is_array($boardData)) {
                                $boardData = [];
                            }
                            if (isset($metrics['board_count'])) {
                                $boardData['count'] = (int) $metrics['board_count'];
                            }
                            if (isset($metrics['board_dimensions'])) {
                                $boardData['dimensions'] = (string) $metrics['board_dimensions'];
                            }
                            DB::table('auction_properties')
                                ->where('id', $pivot->id)
                                ->update(['board_data' => json_encode($boardData, JSON_UNESCAPED_UNICODE)]);
                        }
                    }
                } elseif ($campaign->channel === 'brokers') {
                    if (empty($marketing['contract_text']) && filled($metrics['contract_text'] ?? null)) {
                        $marketing['contract_text'] = (string) $metrics['contract_text'];
                        $marketingChanged = true;
                    }
                    if (empty($marketing['brokers'])) {
                        if (! empty($metrics['brokers']) && is_array($metrics['brokers'])) {
                            $marketing['brokers'] = $metrics['brokers'];
                            $marketingChanged = true;
                        } else {
                            $legacyBrokers = [];
                            foreach (['display_name', 'broker_second_name'] as $idx => $legacyKey) {
                                if (filled($metrics[$legacyKey] ?? null)) {
                                    $legacyBrokers[] = ['key' => 'broker-'.($idx + 1), 'name' => (string) $metrics[$legacyKey]];
                                }
                            }
                            if ($legacyBrokers !== []) {
                                $marketing['brokers'] = $legacyBrokers;
                                $marketingChanged = true;
                            }
                        }
                    }
                }

                if (Schema::hasTable('auction_attachments')) {
                    DB::table('auction_attachments')
                        ->where('marketing_campaign_id', $campaign->id)
                        ->update(['marketing_campaign_id' => null]);
                }
            }

            if ($socialContexts !== [] && empty($marketing['social_context'])) {
                $marketing['social_context'] = implode("\n", array_values(array_unique($socialContexts)));
                $marketingChanged = true;
            }

            if ($marketingChanged) {
                DB::table('auctions')
                    ->where('id', $auction->id)
                    ->update(['marketing_data' => json_encode($marketing, JSON_UNESCAPED_UNICODE)]);
            }
        }

        DB::table('auction_marketing_campaigns')
            ->whereIn('channel', self::REMOVED_CHANNELS)
            ->delete();
    }

    public function down(): void
    {
        // One-way cleanup of deprecated channels.
    }
};
