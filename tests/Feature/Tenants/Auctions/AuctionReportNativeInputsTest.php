<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Http\Middleware\Tenancy\CheckSubscription;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use App\Services\Tenants\Auctions\Reports\AuctionReportPaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionReportNativeInputsTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    private function login(): void
    {
        Queue::fake();
        Storage::fake('tenant_public');
        $this->withoutVite();
        $this->withoutMiddleware(CheckSubscription::class);
        cache()->put('tenant:'.tenant('id').':subscription', (object) ['status' => null], 60);
        $user = User::where('email', 'demo@lot.com')->firstOrFail();
        foreach (['marketing_campaigns.create', 'marketing_campaigns.edit', 'marketing_campaigns.delete', 'auction_closings.create', 'auction_closings.edit'] as $permission) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'tenant']));
        }
        $this->actingAs($user, 'tenant');
    }

    public function test_native_section_data_and_board_measurements_can_be_entered_and_updated(): void
    {
        $this->login();
        $auction = $this->cycleAuction();
        $property = $auction->properties()->first();
        $store = tenant_route_name('marketing-campaigns.store');
        $this->post($store, ['auction_id' => $auction->id, 'channel' => 'planning', 'metrics' => ['brochure_methodology' => 'هوية أصلية']])->assertRedirect();
        $this->post($store, ['auction_id' => $auction->id, 'channel' => 'brochure', 'url' => 'https://example.com/book', 'evidence' => [UploadedFile::fake()->image('book.png')]])->assertRedirect();
        $this->post($store, ['auction_id' => $auction->id, 'channel' => 'board_before', 'metrics' => ['property_id' => $property->id, 'board_count' => 0, 'board_dimensions' => '3 × 2'], 'evidence' => [UploadedFile::fake()->image('board.png')]])->assertRedirect();
        $snapshot = app(AuctionReportDataBuilder::class)->build($auction->fresh());
        self::assertSame(0, $snapshot['properties'][0]['board']['count']);
        self::assertSame('3 × 2', $snapshot['properties'][0]['board']['dimensions']);
        self::assertCount(1, $snapshot['evidence']['brochure']);
        self::assertCount(1, $snapshot['evidence']['board_before']);
        $pages = app(AuctionReportPaginator::class)->paginate($snapshot, 'report');
        self::assertContains('asir-design', array_column($pages, 'page_type'));
        self::assertContains('asir-brochure', array_column($pages, 'page_type'));
        $boardCampaign = $auction->marketingCampaigns()->where('channel', 'board_before')->first();
        $this->put(tenant_route_name('marketing-campaigns.update', $boardCampaign), ['auction_id' => $auction->id, 'channel' => 'board_before', 'metrics' => ['property_id' => $property->id, 'board_count' => 2]])->assertRedirect();
        self::assertSame(2, app(AuctionReportDataBuilder::class)->build($auction->fresh())['properties'][0]['board']['count']);
        $this->delete(tenant_route_name('marketing-campaigns.destroy', $boardCampaign))->assertRedirect();
        self::assertArrayNotHasKey('count', app(AuctionReportDataBuilder::class)->build($auction->fresh())['properties'][0]['board']);
        $foreignProperty = $this->cycleAuction()->properties()->first();
        $this->postJson($store, ['auction_id' => $auction->id, 'channel' => 'board_before', 'metrics' => ['property_id' => $foreignProperty->id, 'board_count' => 2]])->assertUnprocessable()->assertJsonValidationErrors('metrics.property_id');
        foreach (['other'] as $channel) {
            $this->postJson($store, ['auction_id' => $auction->id, 'channel' => $channel, 'metrics' => ['views' => 1]])->assertUnprocessable()->assertJsonValidationErrors('channel');
        }
        $this->postJson($store, ['auction_id' => $auction->id, 'channel' => 'x', 'metrics' => ['clicks' => 4]])->assertUnprocessable()->assertJsonValidationErrors('metrics');
        $this->postJson($store, ['auction_id' => $auction->id, 'channel' => 'marketing_evidence', 'metrics' => ['views' => 4], 'url' => 'https://example.com/proof'])->assertUnprocessable()->assertJsonValidationErrors('metrics');
        $this->postJson($store, ['auction_id' => $auction->id, 'channel' => 'brochure', 'url' => 'https://user:password@example.com/book'])->assertUnprocessable()->assertJsonValidationErrors('url');
    }

    public function test_closing_accepts_native_result_text_and_removal_evidence(): void
    {
        $this->login();
        $auction = $this->cycleAuction();
        $property = $auction->properties()->first();
        $this->put(tenant_route_name('auction-closings.update', $auction), [
            'closing_date' => '2026-11-06', 'results_narrative' => 'تفاصيل النتائج الفعلية', 'board_removal_date' => '2026-11-07',
            'results' => [$property->id => ['status' => 'unsold', 'unsold_reason' => 'لم يصل للسعر']],
            'board_images_after' => [$property->id => [UploadedFile::fake()->image('removed.png')]],
        ])->assertRedirect();
        $snapshot = app(AuctionReportDataBuilder::class)->build($auction->fresh());
        self::assertSame('تفاصيل النتائج الفعلية', $snapshot['marketing']['results_narrative']);
        self::assertSame('2026-11-07', $snapshot['marketing']['board_removal_date']);
        self::assertCount(1, $snapshot['evidence']['board_after']);
        $evidence = $auction->attachments()->where('report_section', 'board_after')->firstOrFail();
        $this->put(tenant_route_name('auction-closings.update', $auction), ['closing_date' => '2026-11-06', 'results' => [$property->id => ['status' => 'unsold', 'unsold_reason' => 'لم يصل للسعر']], 'remove_board_evidence' => [$evidence->id]])->assertRedirect();
        self::assertFalse($auction->attachments()->whereKey($evidence->id)->exists());
    }

    public function test_text_that_cannot_fit_the_original_report_is_rejected_before_saving(): void
    {
        $this->login();
        $auction = $this->cycleAuction();
        $this->postJson(tenant_route_name('marketing-campaigns.store'), ['auction_id' => $auction->id, 'channel' => 'planning', 'metrics' => ['planning_methodology' => str_repeat('خطة التسويق ودراسة السوق العقاري ', 30)]])->assertUnprocessable()->assertJsonValidationErrors('metrics.planning_methodology');
        self::assertSame(0, $auction->marketingCampaigns()->count());
    }
}
