<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Actions\Tenants\Settings\UpdateTenantSettingsAction;
use App\Models\Tenants\Assignments\IssuingEntity;
use App\Models\Tenants\Settings\Settings;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use Illuminate\Support\Facades\Storage;
use Tests\Support\AuctionReportCycleFixtures;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionReportCompanySourcesTest extends TenantAuctionReportTestCase
{
    use AuctionReportCycleFixtures;

    public function test_snapshot_uses_tenant_contacts_and_both_correct_logos(): void
    {
        Storage::fake('tenant_public');
        Storage::disk('tenant_public')->put('company.png', 'company-bytes');
        Storage::disk('tenant_public')->put('entity.png', 'entity-bytes');
        app(UpdateTenantSettingsAction::class)->execute(['office_name' => 'اسم داخلي', 'report_phone' => '0501234567', 'report_unified_phone' => '920000000', 'report_social' => '@company']);
        Settings::first()->update(['image' => 'company.png']);
        $auction = $this->cycleAuction();
        $entity = IssuingEntity::create(['name' => 'اسم الجهة الداخلي', 'image' => 'entity.png', 'is_active' => true]);
        $auction->assignment->update(['issuing_entity_id' => $entity->id]);
        $auction->update(['marketing_data' => ['contacts' => ['phone' => 'obsolete']]]);
        $auction->attachments()->create(['file_path' => 'internal.pdf', 'file_name' => 'INTERNAL', 'category' => 'auction_doc', 'report_section' => 'marketing_evidence', 'channels' => ['internal']]);
        $snapshot = app(AuctionReportDataBuilder::class)->build($auction->fresh());
        self::assertSame('0501234567', $snapshot['marketing']['contacts']['phone']);
        self::assertStringContainsString(base64_encode('company-bytes'), $snapshot['branding']['company_logo']);
        self::assertStringContainsString(base64_encode('entity-bytes'), $snapshot['branding']['issuing_entity_logo']);
        self::assertArrayNotHasKey('office_name', $snapshot['branding']);
        self::assertSame([], $snapshot['evidence']);
    }
}
