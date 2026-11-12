<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Http\Requests\Tenants\AuctionClosings\SaveAuctionClosingRequest;
use App\Models\Tenants\Auctions\Auction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class AuctionClosingPartialStagedTest extends TestCase
{
    public function test_auction_closing_request_allows_partial_or_pending_results(): void
    {
        $request = new SaveAuctionClosingRequest;
        $rules = $request->rules();

        $data = [
            'closing_date' => '2026-11-12',
            'board_removal_date' => '2026-11-12',
            'results' => [
                1 => [
                    'status' => 'sold',
                    'buyer_name' => 'محمد أحمد',
                    'sale_price' => 500000,
                ],
                2 => [
                    'status' => '',
                ],
            ],
        ];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->fails(), 'Validation should pass with partial/pending property results');
    }

    public function test_auction_closing_request_requires_sold_fields_when_status_is_sold(): void
    {
        $request = new SaveAuctionClosingRequest;
        $rules = $request->rules();

        $data = [
            'closing_date' => '2026-11-12',
            'board_removal_date' => '2026-11-12',
            'results' => [
                1 => [
                    'status' => 'sold',
                    'buyer_name' => '',
                    'sale_price' => null,
                ],
            ],
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('results.1.buyer_name'));
        $this->assertTrue($validator->errors()->has('results.1.sale_price'));
    }

    public function test_auction_closing_request_requires_unsold_reason_when_status_is_unsold(): void
    {
        $request = new SaveAuctionClosingRequest;
        $rules = $request->rules();

        $data = [
            'closing_date' => '2026-11-12',
            'board_removal_date' => '2026-11-12',
            'results' => [
                1 => [
                    'status' => 'unsold',
                    'unsold_reason' => '',
                ],
            ],
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('results.1.unsold_reason'));
    }

    public function test_auction_status_only_transitions_to_closed_when_all_properties_have_decided_outcomes(): void
    {
        $auction = $this->makeAuctionWithResults(['sold', null]);
        $this->assertSame(AuctionStatus::APPROVED, $auction->status);
        $this->assertFalse($auction->isClosed());

        $auction = $this->makeAuctionWithResults(['sold', 'pending']);
        $this->assertSame(AuctionStatus::APPROVED, $auction->status);
        $this->assertFalse($auction->isClosed());

        $auction = $this->makeAuctionWithResults(['sold', 'unsold']);
        $this->assertSame(AuctionStatus::CLOSED, $auction->status);
        $this->assertTrue($auction->isClosed());

        $auction = $this->makeAuctionWithResults(['unsold', 'unsold']);
        $this->assertSame(AuctionStatus::CLOSED, $auction->status);
        $this->assertTrue($auction->isClosed());

        $auction = $this->makeAuctionWithResults(['sold', 'sold']);
        $this->assertSame(AuctionStatus::CLOSED, $auction->status);
        $this->assertTrue($auction->isClosed());
    }

    private function makeAuctionWithResults(array $results): Auction
    {
        $auction = new Auction;
        $request = new class extends Model
        {
            public bool $approved = true;

            public function isApproved(): bool
            {
                return $this->approved;
            }
        };
        $auction->setRelation('establishmentRequest', $request);
        $auction->setRelation('properties', new Collection(array_map(static function ($status): Model {
            $property = new class extends Model {};
            $property->setRelation('pivot', new class(['result_status' => $status]) extends Model
            {
                protected $guarded = [];
            });

            return $property;
        }, $results)));

        return $auction;
    }
}
