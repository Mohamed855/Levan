<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\DataTables\Tenants\Auctions\AuctionsDataTable;
use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\AuctionService;
use Carbon\Carbon;
use Tests\TestCase;

class AuctionStatusLifecycleTest extends TestCase
{
    public function test_auction_status_enum_has_expected_values_and_labels(): void
    {
        $this->assertSame('upcoming', AuctionStatus::UPCOMING->value);
        $this->assertSame('active', AuctionStatus::ACTIVE->value);
        $this->assertSame('suspended', AuctionStatus::SUSPENDED->value);
        $this->assertSame('closed', AuctionStatus::CLOSED->value);

        $this->assertSame('قادم', AuctionStatus::UPCOMING->label());
        $this->assertSame('نشط', AuctionStatus::ACTIVE->label());
        $this->assertSame('معلق', AuctionStatus::SUSPENDED->label());
        $this->assertSame('تم الإغلاق', AuctionStatus::CLOSED->label());

        $options = AuctionStatus::options();
        $this->assertArrayHasKey('upcoming', $options);
        $this->assertArrayHasKey('active', $options);
        $this->assertArrayHasKey('suspended', $options);
        $this->assertArrayHasKey('closed', $options);
    }

    public function test_auction_computes_suspended_when_is_active_is_false(): void
    {
        $auction = new Auction([
            'is_active' => false,
            'start_date' => Carbon::now()->subDays(2),
            'start_time' => '10:00',
            'end_date' => Carbon::now()->addDays(2),
            'end_time' => '18:00',
        ]);

        $this->assertSame(AuctionStatus::SUSPENDED, $auction->status);
        $this->assertSame('معلق', $auction->status_label);
        $this->assertSame('bg-warning', $auction->status_badge_class);
        $this->assertTrue($auction->isSuspended());
        $this->assertFalse($auction->isClosed());
        $this->assertFalse($auction->isActive());
        $this->assertFalse($auction->isUpcoming());
    }

    public function test_auction_computes_closed_when_end_datetime_has_passed(): void
    {
        $auction = new Auction([
            'is_active' => true,
            'start_date' => Carbon::now()->subDays(5),
            'start_time' => '10:00',
            'end_date' => Carbon::now()->subDay(),
            'end_time' => '18:00',
        ]);

        $this->assertSame(AuctionStatus::CLOSED, $auction->status);
        $this->assertSame('تم الإغلاق', $auction->status_label);
        $this->assertSame('bg-secondary', $auction->status_badge_class);
        $this->assertTrue($auction->isClosed());
        $this->assertFalse($auction->isSuspended());
        $this->assertFalse($auction->isActive());
        $this->assertFalse($auction->isUpcoming());
    }

    public function test_auction_computes_closed_when_end_date_is_today_and_end_time_passed(): void
    {
        $auction = new Auction([
            'is_active' => true,
            'start_date' => Carbon::today(),
            'start_time' => '06:00',
            'end_date' => Carbon::today(),
            'end_time' => Carbon::now()->subHours(2)->format('H:i'),
        ]);

        $this->assertSame(AuctionStatus::CLOSED, $auction->status);
        $this->assertSame('تم الإغلاق', $auction->status_label);
        $this->assertTrue($auction->isClosed());
    }

    public function test_auction_computes_upcoming_when_start_datetime_is_in_future(): void
    {
        $auction = new Auction([
            'is_active' => true,
            'start_date' => Carbon::now()->addDays(2),
            'start_time' => '10:00',
            'end_date' => Carbon::now()->addDays(5),
            'end_time' => '18:00',
        ]);

        $this->assertSame(AuctionStatus::UPCOMING, $auction->status);
        $this->assertSame('قادم', $auction->status_label);
        $this->assertSame('bg-info', $auction->status_badge_class);
        $this->assertTrue($auction->isUpcoming());
        $this->assertFalse($auction->isClosed());
        $this->assertFalse($auction->isActive());
    }

    public function test_auction_computes_active_when_within_timeframe_and_active(): void
    {
        $auction = new Auction([
            'is_active' => true,
            'start_date' => Carbon::now()->subDay(),
            'start_time' => '10:00',
            'end_date' => Carbon::now()->addDays(2),
            'end_time' => '18:00',
        ]);

        $this->assertSame(AuctionStatus::ACTIVE, $auction->status);
        $this->assertSame('نشط', $auction->status_label);
        $this->assertSame('bg-success', $auction->status_badge_class);
        $this->assertTrue($auction->isActive());
        $this->assertFalse($auction->isClosed());
        $this->assertFalse($auction->isSuspended());
        $this->assertFalse($auction->isUpcoming());
    }

    public function test_auctions_data_table_has_status_badge_column(): void
    {
        $dataTable = new AuctionsDataTable();
        $reflection = new \ReflectionClass($dataTable);
        $method = $reflection->getMethod('getColumns');
        $method->setAccessible(true);
        $columns = $method->invoke($dataTable);
        $columnNames = collect($columns)->pluck('name')->all();

        $this->assertContains('status_badge', $columnNames);
        $this->assertContains('is_active', $columnNames);

        $statusBadgeCol = collect($columns)->firstWhere('name', 'status_badge');
        $this->assertSame('حالة المزاد', $statusBadgeCol['title'] ?? null);
    }
}
