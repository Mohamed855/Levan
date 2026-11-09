<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Models\Tenants\AuctionClosings\AuctionClosing;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormDataBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Tests\TestCase;

final class AuctionRegulatoryFormDataBuilderTest extends TestCase
{
    private function auction(bool $approved = true): Auction
    {
        $request = new class extends Model
        {
            protected $guarded = [];
            protected $casts = ['data' => 'array', 'approved_at' => 'datetime'];

            public function isApproved(): bool
            {
                return $this->approved_at !== null && filled($this->approval_number);
            }
        };
        $request->fill(['data' => [
            'organization' => ['name' => 'Saved organization'],
            'auction' => ['name' => 'Saved auction'],
            'properties' => [
                ['property_id' => 12, 'deed_number' => '00012', 'type' => 'Saved type', 'usage' => 'Saved usage'],
                ['property_id' => 8, 'deed_number' => '00008', 'type' => 'Saved second type'],
            ],
        ], 'approved_at' => $approved ? '2026-11-09 12:30:00' : null, 'approval_number' => $approved ? '000187' : null]);
        $request->exists = true;
        $auction = new Auction(['name' => 'Changed auction']);
        $auction->setRelation('establishmentRequest', $request);
        $closing = new AuctionClosing(['closing_date' => '2026-11-10', 'saved_at' => '2026-11-10 12:00:00']);
        $closing->exists = true;
        $auction->setRelation('closing', $closing);
        $sold = new Property;
        $sold->setRawAttributes(['id' => 8, 'type' => 'Changed type']);
        $sold->setRelation('pivot', new Pivot(['result_status' => 'sold', 'buyer_name' => 'Buyer', 'sale_price' => '9999999999999999.99']));
        $unsold = new Property;
        $unsold->setRawAttributes(['id' => 12]);
        $unsold->setRelation('pivot', new Pivot(['result_status' => 'unsold', 'unsold_reason' => 'No bids', 'buyer_name' => 'Stale buyer', 'sale_price' => '100']));
        $auction->setRelation('properties', new Collection([$sold, $unsold]));

        return $auction;
    }

    public function test_establishment_uses_saved_native_snapshot_without_live_source_changes(): void
    {
        self::assertTrue(class_exists(AuctionRegulatoryFormDataBuilder::class));
        $auction = $this->auction(false);
        self::assertSame($auction->establishmentRequest->data, app(AuctionRegulatoryFormDataBuilder::class)->build($auction, 'establishment'));
    }

    public function test_closing_matches_saved_property_metadata_by_id_and_keeps_decimal_precision(): void
    {
        self::assertTrue(class_exists(AuctionRegulatoryFormDataBuilder::class));
        $auction = $this->auction();
        $original = $auction->establishmentRequest->data;
        $data = app(AuctionRegulatoryFormDataBuilder::class)->build($auction, 'closing');
        self::assertSame('000187', $data['closing']['approval_number']);
        self::assertSame('2026/11/10', $data['closing']['date']);
        self::assertSame('2026/11/09', $data['closing']['approval_date']);
        self::assertSame('00012', $data['properties'][0]['deed_number']);
        self::assertSame('Saved type', $data['properties'][0]['type']);
        self::assertSame('not_awarded', $data['properties'][0]['result']);
        self::assertSame('No bids', $data['properties'][0]['unawarded_reason']);
        self::assertSame('', $data['properties'][0]['awardee']);
        self::assertSame('awarded', $data['properties'][1]['result']);
        self::assertSame('9999999999999999.99', $data['properties'][1]['award_value']);
        self::assertSame($original, $auction->establishmentRequest->data);
    }

    public function test_closing_cannot_use_historical_closing_as_approval(): void
    {
        self::assertTrue(class_exists(AuctionRegulatoryFormDataBuilder::class));
        $this->expectException(\DomainException::class);
        app(AuctionRegulatoryFormDataBuilder::class)->build($this->auction(false), 'closing');
    }

    public function test_closing_rejects_unknown_property_results(): void
    {
        self::assertTrue(class_exists(AuctionRegulatoryFormDataBuilder::class));
        $auction = $this->auction();
        $auction->properties->first()->pivot->result_status = 'pending';
        $this->expectException(\DomainException::class);
        app(AuctionRegulatoryFormDataBuilder::class)->build($auction, 'closing');
    }

    public function test_closing_rejects_property_changes_after_approval(): void
    {
        self::assertTrue(class_exists(AuctionRegulatoryFormDataBuilder::class));
        $auction = $this->auction();
        $auction->setRelation('properties', new Collection([$auction->properties->first()]));
        $this->expectException(\DomainException::class);
        app(AuctionRegulatoryFormDataBuilder::class)->build($auction, 'closing');
    }
}
