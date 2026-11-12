<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\MarketingCampaigns\MarketingCampaign;
use App\Models\Tenants\UserMangment\User\User as TenantUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

final class MarketingCampaignActionButtonsTest extends TestCase
{
    public function test_when_campaigns_count_is_zero_edit_button_shows_add_icon_and_title_and_no_delete(): void
    {
        $user = (new TenantUser)->forceFill(['id' => 1]);
        $this->actingAs($user, 'tenant');
        Gate::before(fn () => true);

        $auction = new Auction;
        $auction->forceFill([
            'id' => 99,
            'name' => 'مزاد تجريبي',
            'marketing_campaigns_count' => 0,
        ]);
        $auction->setRelation('marketingCampaigns', new Collection);
        $auction->setRelation('properties', new Collection);
        $auction->setRelation('establishmentRequest', null);

        $html = view('tenant.marketing-campaigns.action', ['row' => $auction])->render();

        self::assertStringContainsString('ti ti-file-plus', $html);
        self::assertStringNotContainsString('ti ti-edit', $html);
        self::assertStringContainsString('إضافة الحملات التسويقية', $html);
        self::assertStringNotContainsString('تعديل الحملات التسويقية', $html);
        self::assertStringNotContainsString('حذف جميع حملات المزاد', $html);
    }

    public function test_when_campaigns_count_is_greater_than_zero_edit_button_shows_edit_icon_and_title_and_delete_button(): void
    {
        $user = (new TenantUser)->forceFill(['id' => 1]);
        $this->actingAs($user, 'tenant');
        Gate::before(fn () => true);

        $auction = new Auction;
        $auction->forceFill([
            'id' => 99,
            'name' => 'مزاد تجريبي',
            'marketing_campaigns_count' => 3,
        ]);
        $auction->setRelation('marketingCampaigns', new Collection);
        $auction->setRelation('properties', new Collection);
        $auction->setRelation('establishmentRequest', null);

        $html = view('tenant.marketing-campaigns.action', ['row' => $auction])->render();

        self::assertStringContainsString('ti ti-edit', $html);
        self::assertStringNotContainsString('ti ti-file-plus', $html);
        self::assertStringContainsString('تعديل الحملات التسويقية', $html);
        self::assertStringNotContainsString('إضافة الحملات التسويقية', $html);
        self::assertStringContainsString('حذف جميع حملات المزاد', $html);
    }

    public function test_when_campaigns_collection_is_not_empty_edit_button_shows_edit_icon_and_title(): void
    {
        $user = (new TenantUser)->forceFill(['id' => 1]);
        $this->actingAs($user, 'tenant');
        Gate::before(fn () => true);

        $auction = new Auction;
        $auction->forceFill([
            'id' => 99,
            'name' => 'مزاد تجريبي',
            'marketing_campaigns_count' => 0,
        ]);
        $auction->setRelation('marketingCampaigns', new Collection([
            new MarketingCampaign(['channel' => 'x']),
        ]));
        $auction->setRelation('properties', new Collection);
        $auction->setRelation('establishmentRequest', null);

        $html = view('tenant.marketing-campaigns.action', ['row' => $auction])->render();

        self::assertStringContainsString('ti ti-edit', $html);
        self::assertStringNotContainsString('ti ti-file-plus', $html);
        self::assertStringContainsString('تعديل الحملات التسويقية', $html);
        self::assertStringNotContainsString('إضافة الحملات التسويقية', $html);
        self::assertStringContainsString('حذف جميع حملات المزاد', $html);
    }
}
