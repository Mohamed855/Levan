<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Enums\Tenants\Assignments\AssignmentCategory;
use App\Enums\Tenants\Properties\PropertyType;
use App\Enums\Tenants\Properties\PropertyUsageClassification;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use Database\Seeders\admin\DatabaseSeeder;
use Database\Seeders\admin\TestTenantSeeder;
use Database\Seeders\tenant\AuctionsAndPropertiesSeeder;
use Database\Seeders\tenant\TenantDatabaseSeeder;
use Tests\TestCase;

class DemoDataAndProductionSafetyTest extends TestCase
{
    public function test_production_environment_prevents_test_tenant_and_demo_seeders(): void
    {
        $testTenantSeederContent = file_get_contents(base_path('database/seeders/admin/TestTenantSeeder.php'));
        $auctionsSeederContent = file_get_contents(base_path('database/seeders/tenant/AuctionsAndPropertiesSeeder.php'));
        $adminDatabaseSeederContent = file_get_contents(base_path('database/seeders/admin/DatabaseSeeder.php'));
        $tenantDatabaseSeederContent = file_get_contents(base_path('database/seeders/tenant/TenantDatabaseSeeder.php'));

        // TestTenantSeeder MUST check production environment and exit early
        $this->assertStringContainsString("app()->environment('production')", $testTenantSeederContent);

        // AuctionsAndPropertiesSeeder MUST check production environment and exit early
        $this->assertStringContainsString("app()->environment('production')", $auctionsSeederContent);

        // DatabaseSeeder must only call TestTenantSeeder in local environment
        $this->assertStringContainsString("if (app()->environment('local'))", $adminDatabaseSeederContent);
        $this->assertStringContainsString('TestTenantSeeder::class', $adminDatabaseSeederContent);

        // TenantDatabaseSeeder must NOT run demo seeders for all tenants
        $this->assertStringNotContainsString('AuctionsAndPropertiesSeeder::class,', $tenantDatabaseSeederContent);
    }

    public function test_test_tenant_contains_all_property_types(): void
    {
        tenancy()->initialize('test');
        try {
            $existingTypes = Property::withTrashed()->pluck('type')->map(fn ($t) => is_object($t) ? $t->value : (string) $t)->unique()->values()->all();

            foreach (PropertyType::cases() as $case) {
                $this->assertContains(
                    $case->value,
                    $existingTypes,
                    "Expected property type [{$case->value}] to be present in test tenant demo data."
                );
            }
        } finally {
            tenancy()->end();
        }
    }

    public function test_test_tenant_contains_all_usage_classifications(): void
    {
        tenancy()->initialize('test');
        try {
            $existingClassifications = Property::withTrashed()->pluck('usage_classification')->map(fn ($c) => is_object($c) ? $c->value : (string) $c)->unique()->values()->all();

            foreach (PropertyUsageClassification::cases() as $case) {
                $this->assertContains(
                    $case->value,
                    $existingClassifications,
                    "Expected property usage classification [{$case->value}] to be present in test tenant demo data."
                );
            }
        } finally {
            tenancy()->end();
        }
    }

    public function test_test_tenant_contains_all_assignment_categories(): void
    {
        tenancy()->initialize('test');
        try {
            $existingCategories = Assignment::withTrashed()->pluck('category')->map(fn ($c) => is_object($c) ? $c->value : (string) $c)->unique()->values()->all();

            foreach (AssignmentCategory::cases() as $case) {
                $this->assertContains(
                    $case->value,
                    $existingCategories,
                    "Expected assignment category [{$case->value}] to be present in test tenant demo data."
                );
            }
        } finally {
            tenancy()->end();
        }
    }

    public function test_test_tenant_has_unassigned_properties_for_all_classifications_including_other(): void
    {
        tenancy()->initialize('test');
        try {
            $unassignedProperties = Property::whereNull('assignment_id')->get();
            $unassignedClassifications = $unassignedProperties->pluck('usage_classification')->map(fn ($c) => is_object($c) ? $c->value : (string) $c)->unique()->values()->all();

            // All classifications including 'other' must have unassigned properties
            foreach (PropertyUsageClassification::cases() as $case) {
                $this->assertContains(
                    $case->value,
                    $unassignedClassifications,
                    "Expected unassigned property with classification [{$case->value}] for assignment creation testing."
                );
            }

            // At least 2 unassigned properties with classification 'other'
            $otherCount = $unassignedProperties->filter(fn ($p) => ($p->usage_classification->value ?? (string) $p->usage_classification) === 'other')->count();
            $this->assertGreaterThanOrEqual(2, $otherCount, 'Expected at least 2 unassigned properties with classification "other".');
        } finally {
            tenancy()->end();
        }
    }

    public function test_test_tenant_auctions_cover_electronic_in_person_hybrid_and_completed_states(): void
    {
        tenancy()->initialize('test');
        try {
            $auctions = Auction::withTrashed()->get();

            $types = $auctions->pluck('type')->unique()->values()->all();
            $this->assertContains('electronic', $types);
            $this->assertContains('in_person', $types);
            $this->assertContains('hybrid', $types);

            // Completed auction with sold and unsold properties
            $completedAuction = $auctions->firstWhere('name', 'مزاد مساهمات الشرقية الصناعية المنتهي');
            $this->assertNotNull($completedAuction);

            $soldProp = $completedAuction->properties()->wherePivot('result_status', 'sold')->first();
            $this->assertNotNull($soldProp);
            $this->assertNotNull($soldProp->pivot->sale_price);
            $this->assertNotNull($soldProp->pivot->buyer_name);

            $unsoldProp = $completedAuction->properties()->wherePivot('result_status', 'unsold')->first();
            $this->assertNotNull($unsoldProp);
            $this->assertNotNull($unsoldProp->pivot->unsold_reason);
        } finally {
            tenancy()->end();
        }
    }

    public function test_tenant_admin_has_admin_role_and_all_permissions_and_all_operational_regions(): void
    {
        tenancy()->initialize('test');
        try {
            $admin = \App\Models\Tenants\UserMangment\User\User::where('email', 'demo@lot.com')->first();
            $this->assertNotNull($admin);
            $this->assertTrue($admin->hasRole('Admin'));

            $totalTenantPermissions = \Spatie\Permission\Models\Permission::where('guard_name', 'tenant')->count();
            $this->assertSame($totalTenantPermissions, $admin->getAllPermissions()->count());

            $settings = \App\Models\Tenants\Settings\Settings::first();
            $this->assertNotNull($settings);
            $this->assertIsArray($settings->operational_regions);
            $this->assertCount(13, $settings->operational_regions);
            $this->assertContains('منطقة الرياض', $settings->operational_regions);
            $this->assertContains('منطقة مكة المكرمة', $settings->operational_regions);
            $this->assertContains('المنطقة الشرقية', $settings->operational_regions);
        } finally {
            tenancy()->end();
        }
    }

    public function test_superadmin_user_has_superadmin_role_and_all_admin_permissions(): void
    {
        $superAdmin = \App\Models\Admin\AdminMangment\Users\AdminUser::where('email', 'superadmin@gmail.com')->first();
        $this->assertNotNull($superAdmin);
        $this->assertTrue($superAdmin->hasRole('SuperAdmin'));

        $totalAdminPermissions = \Spatie\Permission\Models\Permission::where('guard_name', 'admin')->count();
        $this->assertSame($totalAdminPermissions, $superAdmin->getAllPermissions()->count());
    }
}
