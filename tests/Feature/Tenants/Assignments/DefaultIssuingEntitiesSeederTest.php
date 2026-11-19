<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Assignments;

use App\Models\Tenants\Assignments\IssuingEntity;
use Database\Seeders\tenant\IssuingEntitySeeder;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TenantAuctionReportTestCase;

final class DefaultIssuingEntitiesSeederTest extends TenantAuctionReportTestCase
{
    public function test_only_primary_entities_are_seeded_without_duplicate_city_branches(): void
    {
        Storage::fake('tenant_public');
        $existing = IssuingEntity::pluck('name')->all();
        $primary = ['مركز الإسناد والتصفية (إنفاذ)', 'الهيئة العامة لعقارات الدولة', 'وزارة العدل', 'لجنة الإفلاس', 'الهيئة العامة للأوقاف'];
        $this->seed(IssuingEntitySeeder::class);
        self::assertEqualsCanonicalizing(array_unique(array_merge($existing, $primary)), IssuingEntity::pluck('name')->all());
        foreach (IssuingEntity::whereIn('name', $primary)->get() as $entity) {
            self::assertTrue($entity->is_active);
            self::assertNotNull($entity->image);
            Storage::disk('tenant_public')->assertExists($entity->image);
        }
    }

    public function test_reseeding_preserves_customer_entities_and_does_not_duplicate_defaults(): void
    {
        Storage::fake('tenant_public');
        $custom = IssuingEntity::create(['name' => 'جهة خاصة بالعميل', 'is_active' => false]);
        $this->seed(IssuingEntitySeeder::class);
        $count = IssuingEntity::count();
        $this->seed(IssuingEntitySeeder::class);
        self::assertSame($count, IssuingEntity::count());
        self::assertSame('جهة خاصة بالعميل', $custom->fresh()->name);
        self::assertFalse($custom->fresh()->is_active);
    }
}
