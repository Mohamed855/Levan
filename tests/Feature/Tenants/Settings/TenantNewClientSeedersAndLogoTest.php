<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Settings;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Assignments\IssuingEntity;
use App\Models\Tenants\Settings\Settings;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Shared\Tenant\TenantInitializer;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Database\Seeders\tenant\IssuingEntitySeeder;
use Database\Seeders\tenant\SettingsTableSeeder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantNewClientSeedersAndLogoTest extends TestCase
{
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('tenant_public');
        Storage::fake('public');

        $this->tenant = Tenant::find('test') ?? Tenant::first() ?? Tenant::create([
            'id' => 'test',
            'company_name' => 'شركة لوت التجريبية',
        ]);
        tenancy()->initialize($this->tenant);

        User::firstOrCreate(
            ['email' => 'demo@lot.com'],
            [
                'name' => 'مدير النظام',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]
        );

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_tenant_settings_image_is_null_by_default_after_seeding(): void
    {
        Settings::query()->delete();

        $regions = \Mockery::mock(new CentralCityOptionsGateway);
        $regions->shouldReceive('allRegionOptionsByName')->andReturn([
            'المنطقة الشرقية',
            'منطقة الرياض',
        ]);
        $this->app->instance(CentralCityOptionsGateway::class, $regions);

        $this->seed(SettingsTableSeeder::class);

        $settings = Settings::firstOrFail();
        $this->assertNull($settings->image, 'Tenant settings image must be null by default.');
    }

    public function test_tenant_initializer_creates_settings_with_null_image(): void
    {
        Settings::query()->delete();

        $initializer = app(TenantInitializer::class);
        $refMethod = new \ReflectionMethod($initializer, 'createSettings');
        $refMethod->invoke($initializer, [
            'billing_company_name' => 'عميل تجريبي جديد',
        ]);

        $settings = Settings::firstOrFail();
        $this->assertNull($settings->image, 'TenantInitializer must create settings with null image.');
        $this->assertSame('عميل تجريبي جديد', $settings->office_name);
    }

    public function test_issuing_entities_are_seeded_with_real_images_and_not_demo_placeholders(): void
    {
        $this->seed(IssuingEntitySeeder::class);

        $entities = IssuingEntity::all();
        $this->assertGreaterThanOrEqual(5, $entities->count());

        foreach ($entities as $entity) {
            $this->assertNotNull($entity->image, "Issuing entity [{$entity->name}] must have an image.");
            $this->assertStringNotContainsString('demo-', (string) $entity->image);
            $this->assertStringEndsWith('.png', (string) $entity->image);
            $this->assertTrue(
                Storage::disk('tenant_public')->exists($entity->image),
                "Fixture image for [{$entity->name}] was not stored in tenant_public storage: {$entity->image}"
            );
        }
    }

    public function test_tenant_database_seeder_includes_issuing_entity_seeder(): void
    {
        $content = file_get_contents(base_path('database/seeders/tenant/TenantDatabaseSeeder.php'));
        $this->assertStringContainsString('IssuingEntitySeeder::class', $content);
    }

    public function test_tenant_logo_renders_platform_icon_when_settings_image_is_null(): void
    {
        Settings::query()->delete();
        Cache::flush();

        Settings::create([
            'image' => null,
            'office_name' => 'شركة لوت',
            'logo_text' => 'لوت',
            'operational_regions' => ['منطقة الرياض'],
        ]);

        $html = Blade::render('<x-partials.tenant-logo :height="32" />');

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('src="'.asset('static/brand/logos/lot/trans-icon.png').'"', $html);
        $this->assertStringNotContainsString('logo-light.svg', $html);
        $this->assertStringNotContainsString('logo.svg', $html);
    }

    public function test_tenant_logo_renders_image_only_when_client_uploads_logo(): void
    {
        Settings::query()->delete();
        Cache::flush();

        $uploadedPath = 'settings/custom_client_logo.png';
        Storage::disk('tenant_public')->put($uploadedPath, 'fake-logo-content');

        Settings::create([
            'image' => $uploadedPath,
            'office_name' => 'شركة العميل',
            'logo_text' => 'العميل',
            'operational_regions' => ['منطقة الرياض'],
        ]);

        $html = Blade::render('<x-partials.tenant-logo :height="32" />');

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString($uploadedPath, $html);
    }
}
