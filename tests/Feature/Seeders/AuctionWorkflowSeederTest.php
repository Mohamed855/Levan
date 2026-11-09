<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Models\Tenants\Auctions\Auction;
use Database\Seeders\tenant\AuctionWorkflowScenariosSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionWorkflowSeederTest extends TenantAuctionReportTestCase
{
    public function test_demo_workflows_cover_approval_closing_completeness_and_generation_without_rendering(): void
    {
        config(['seeding.test_seeders_enabled' => true]);
        Storage::fake('tenant_private');
        Storage::fake('tenant_public');
        Queue::fake();
        self::assertTrue(class_exists(AuctionWorkflowScenariosSeeder::class));
        $this->seed(AuctionWorkflowScenariosSeeder::class);
        $expected = [
            '01' => ['pending', 'incomplete', false, false],
            '02' => ['pending', 'incomplete', true, false],
            '03' => ['pending', 'complete', true, false],
            '04' => ['approved', 'incomplete', true, false],
            '05' => ['approved', 'complete', true, false],
            '06' => ['approved', 'complete', true, true],
            '07' => ['closed', 'complete', true, true],
            '08' => ['closed', 'incomplete', true, true],
            '09' => ['approved', 'incomplete', true, true],
            '10' => ['pending', 'incomplete', false, false],
        ];
        foreach ($expected as $number => [$status, $completion, $hasRequest, $hasClosing]) {
            $auction = Auction::where('name', 'like', '[WF-'.$number.']%')->firstOrFail();
            self::assertSame($status, $auction->status->value, $auction->name);
            self::assertSame($completion, $auction->completion_status->value, $auction->name);
            self::assertSame($hasRequest, $auction->establishmentRequest !== null);
            self::assertSame($hasClosing, $auction->closing !== null);
            self::assertNotNull($auction->latestReportGeneration);
            if ($hasRequest) {
                $form = \App\Models\Tenants\Auctions\AuctionFormGeneration::where('auction_id', $auction->id)->where('type', 'establishment')->latest('id')->firstOrFail();
                self::assertSame('queued', $form->status);
                foreach ($auction->establishmentRequest->data['attachments'] as $files) {
                    foreach ($files as $file) {
                        Storage::disk('tenant_private')->assertExists($file['path']);
                    }
                }
            }
            if ($hasClosing) {
                self::assertSame($auction->establishmentRequest->approval_number, $auction->closing->approval_number);
                self::assertDatabaseHas('auction_form_generations', ['auction_id' => $auction->id, 'type' => 'closing', 'status' => 'queued']);
            }
        }
        Queue::assertNothingPushed();
        self::assertSame(0, Auction::where('name', 'like', '[WF-10]%')->firstOrFail()->properties()->count());
    }

    public function test_disabled_demo_seeder_does_not_write_in_any_environment(): void
    {
        config(['seeding.test_seeders_enabled' => false]);
        $this->app->detectEnvironment(fn () => 'production');
        $count = Auction::count();
        self::assertTrue(class_exists(AuctionWorkflowScenariosSeeder::class));
        $this->seed(AuctionWorkflowScenariosSeeder::class);
        self::assertSame($count, Auction::count());
    }
}
