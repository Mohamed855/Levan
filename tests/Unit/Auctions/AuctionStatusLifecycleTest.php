<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Models\Tenants\Auctions\Auction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

final class AuctionStatusLifecycleTest extends TestCase
{
    public function test_public_statuses_are_pending_approved_and_closed(): void
    {
        self::assertSame(['pending', 'approved', 'closed'], array_keys(AuctionStatus::options()));
    }

    public function test_dates_and_manual_activation_do_not_approve_or_close_an_auction(): void
    {
        $auction = $this->auction(false, [null], ['is_active' => false, 'end_date' => '2020-01-01']);
        self::assertSame('pending', $auction->status->value);
        self::assertFalse($auction->isClosed());
    }

    public function test_approved_auction_requires_nonempty_all_sold_or_unsold_linked_properties_to_close(): void
    {
        self::assertSame('approved', $this->auction(true, [])->status->value);
        self::assertSame('approved', $this->auction(true, ['sold', null])->status->value);
        self::assertSame('approved', $this->auction(true, ['sold', 'pending'])->status->value);
        self::assertSame('closed', $this->auction(true, ['sold', 'unsold'])->status->value);
        self::assertSame('closed', $this->auction(true, ['unsold', 'unsold'])->status->value);
        self::assertSame('closed', $this->auction(true, ['sold', 'sold'])->status->value);
    }

    public function test_legacy_all_decided_auction_is_closed_without_a_request(): void
    {
        self::assertSame('closed', $this->auction(false, ['sold'])->status->value);
        self::assertSame('closed', $this->auction(false, ['unsold'])->status->value);
    }

    private function auction(bool $approved, array $results, array $attributes = []): Auction
    {
        $auction = new Auction($attributes);
        $request = new class extends Model
        {
            public bool $approved = false;

            public function isApproved(): bool
            {
                return $this->approved;
            }
        };
        $request->approved = $approved;
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
