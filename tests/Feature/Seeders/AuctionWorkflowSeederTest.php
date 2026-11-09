<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Enums\Tenants\Assignments\AssignmentCategory;
use App\Enums\Tenants\Properties\PropertyType;
use App\Enums\Tenants\Properties\PropertyUsageClassification;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionFormGeneration;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryForms;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Database\Seeders\tenant\AuctionsAndPropertiesSeeder;
use Database\Seeders\tenant\AuctionWorkflowScenariosSeeder;
use Database\Seeders\tenant\TenantDatabaseSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionWorkflowSeederTest extends TenantAuctionReportTestCase
{
    public function test_demo_workflows_cover_approval_closing_completeness_and_generation_without_rendering(): void
    {
        config(['seeding.test_seeders_enabled' => true]);
        $this->app->detectEnvironment(fn () => 'production');
        Storage::fake('tenant_private');
        Storage::fake('tenant_public');
        Queue::fake();
        self::assertTrue(class_exists(AuctionWorkflowScenariosSeeder::class));
        $this->artisan('db:seed', ['--class' => AuctionWorkflowScenariosSeeder::class, '--force' => true])->assertSuccessful();
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
                $form = AuctionFormGeneration::where('auction_id', $auction->id)->where('type', 'establishment')->latest('id')->firstOrFail();
                self::assertSame('queued', $form->status);
                self::assertStringContainsString('name="auction-regulatory-form"', app(AuctionRegulatoryForms::class)->html($form->snapshot, 'establishment'));
                foreach ($auction->establishmentRequest->data['attachments'] as $files) {
                    foreach ($files as $file) {
                        Storage::disk('tenant_private')->assertExists($file['path']);
                    }
                }
            }
            if ($hasClosing) {
                self::assertSame($auction->establishmentRequest->approval_number, $auction->closing->approval_number);
                self::assertDatabaseHas('auction_form_generations', ['auction_id' => $auction->id, 'type' => 'closing', 'status' => 'queued']);
                $form = AuctionFormGeneration::where('auction_id', $auction->id)->where('type', 'closing')->latest('id')->firstOrFail();
                self::assertStringContainsString('name="auction-regulatory-form"', app(AuctionRegulatoryForms::class)->html($form->snapshot, 'closing'));
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
        $this->artisan('db:seed', ['--class' => AuctionWorkflowScenariosSeeder::class, '--force' => true])->assertSuccessful();
        self::assertSame($count, Auction::count());
    }

    public function test_repeated_scenario_seed_preserves_tester_changes_without_duplicate_documents(): void
    {
        config(['seeding.test_seeders_enabled' => true]);
        Storage::fake('tenant_private');
        Queue::fake();
        $this->seed(AuctionWorkflowScenariosSeeder::class);
        $auction = Auction::where('name', 'like', '[WF-05]%')->firstOrFail();
        $auction->update(['name' => 'Renamed by tester', 'description' => 'Preserve tester changes']);
        $empty = Auction::where('name', 'like', '[WF-10]%')->firstOrFail();
        $empty->update(['name' => 'Renamed empty auction']);
        $counts = [Auction::count(), AuctionFormGeneration::count(), count(Storage::disk('tenant_private')->allFiles())];
        $this->seed(AuctionWorkflowScenariosSeeder::class);
        self::assertSame('Preserve tester changes', $auction->fresh()->description);
        self::assertSame('Renamed by tester', $auction->fresh()->name);
        self::assertSame('Renamed empty auction', $empty->fresh()->name);
        self::assertSame($counts, [Auction::count(), AuctionFormGeneration::count(), count(Storage::disk('tenant_private')->allFiles())]);
    }

    public function test_base_demo_seeder_keeps_property_coverage_and_links_existing_mixed_closing(): void
    {
        config(['seeding.test_seeders_enabled' => true]);
        Storage::fake('tenant_private');
        Storage::fake('tenant_public');
        Queue::fake();
        // Region lookup crosses tenancy contexts and would discard transactional fixtures.
        $regions = \Mockery::mock(new CentralCityOptionsGateway);
        $regions->shouldReceive('allRegionOptionsByName')->once()->andReturn([]);
        $this->app->instance(CentralCityOptionsGateway::class, $regions);
        $originalAuctionCount = Auction::count();
        $this->seed(TenantDatabaseSeeder::class);
        self::assertSame($originalAuctionCount, Auction::count(), 'Normal tenant bootstrap must not create demo auctions.');
        $this->seed(AuctionsAndPropertiesSeeder::class);
        foreach (PropertyType::cases() as $type) {
            self::assertTrue(Property::withTrashed()->where('type', $type->value)->exists(), $type->value);
        }
        foreach (AssignmentCategory::cases() as $category) {
            self::assertTrue(Assignment::withTrashed()->where('category', $category->value)->exists(), $category->value);
        }
        foreach (PropertyUsageClassification::cases() as $usage) {
            self::assertTrue(Property::whereNull('assignment_id')->where('usage_classification', $usage->value)->exists(), $usage->value);
        }
        $auction = Auction::where('name', 'مزاد مساهمات الشرقية الصناعية المنتهي')->firstOrFail();
        self::assertSame('approved', $auction->status->value);
        self::assertTrue($auction->establishmentRequest->isApproved());
        self::assertNotNull($auction->closing);
        self::assertTrue($auction->properties->contains(fn ($property) => $property->pivot->result_status === 'sold'));
        self::assertTrue($auction->properties->contains(fn ($property) => $property->pivot->result_status === 'unsold'));
        Storage::disk('tenant_public')->assertExists('auctions/attachments/sample_brochure.pdf');
        $admin = User::where('email', 'demo@lot.com')->firstOrFail();
        self::assertTrue($admin->can('auction_establishment_requests.approve'));
        self::assertTrue($admin->can('auction_establishment_requests.regenerate'));
        Queue::assertNothingPushed();
    }
}
