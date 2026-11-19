<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Services\Tenants\Auctions\AuctionBrokersManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class MarketingCampaignBrokersTest extends TestCase
{
    public function test_rows_map_saved_brokers_and_legacy_broker_files(): void
    {
        $auction = new Auction([
            'marketing_data' => [
                'brokers' => [['key' => 'b-1', 'name' => 'الوسيط الأول']],
            ],
        ]);
        $auction->exists = true;
        $auction->setRelation('attachments', new Collection([
            $this->attachment(10, ['broker_key' => 'b-1', 'display_name' => 'الوسيط الأول']),
            $this->attachment(11, ['display_name' => 'وسيط قديم']),
        ]));

        $rows = app(AuctionBrokersManager::class)->rows($auction);

        self::assertCount(2, $rows);
        self::assertSame('الوسيط الأول', $rows[0]['name']);
        self::assertSame(10, $rows[0]['evidence_id']);
        self::assertSame('وسيط قديم', $rows[1]['name']);
        self::assertSame(11, $rows[1]['evidence_id']);
    }

    public function test_plan_skips_empty_placeholder_rows_and_requires_file_for_named_broker(): void
    {
        $plan = app(AuctionBrokersManager::class)->plan(null, [
            ['key' => 'b-1', 'name' => '', 'evidence_id' => null],
        ], []);
        self::assertSame([], $plan['brokers']);

        $this->expectException(ValidationException::class);
        app(AuctionBrokersManager::class)->plan(null, [
            ['key' => 'b-1', 'name' => 'وسيط بدون ملف'],
        ], []);
    }

    public function test_plan_accepts_multiple_brokers_with_files_and_rejects_duplicate_keys(): void
    {
        $files = [
            'a' => UploadedFile::fake()->create('a.pdf', 1, 'application/pdf'),
            'b' => UploadedFile::fake()->create('b.pdf', 1, 'application/pdf'),
        ];

        $plan = app(AuctionBrokersManager::class)->plan(null, [
            ['key' => 'a', 'name' => 'Alpha'],
            ['key' => 'b', 'name' => 'Beta'],
        ], $files);

        self::assertCount(2, $plan['brokers']);
        self::assertCount(2, $plan['uploads']);

        $this->expectException(ValidationException::class);
        app(AuctionBrokersManager::class)->plan(null, [
            ['key' => 'a', 'name' => 'Alpha'],
            ['key' => 'a', 'name' => 'Duplicate'],
        ], $files);
    }

    private function attachment(int $id, array $metadata): AuctionAttachment
    {
        $file = new AuctionAttachment([
            'file_path' => "auctions/report-evidence/{$id}.pdf",
            'file_name' => "{$id}.pdf",
            'report_section' => 'brokers',
            'report_metadata' => $metadata,
        ]);
        $file->id = $id;

        return $file;
    }
}
