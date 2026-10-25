<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class AuctionTemplateSamplesRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_auction_template_samples_feature_is_not_exposed_or_persisted(): void
    {
        $sampleRoutes = collect(Route::getRoutes()->getRoutes())
            ->pluck('action.as')
            ->filter(fn (mixed $name): bool => is_string($name))
            ->filter(fn (string $name): bool => str_starts_with($name, 'admin.auction-template-samples.'));

        self::assertCount(0, $sampleRoutes);
        self::assertFalse(Schema::connection('mysql')->hasTable('auction_template_samples'));
    }

    public function test_feature_removal_migration_does_not_delete_storage_during_tests(): void
    {
        Storage::fake('central_public');
        Storage::disk('central_public')->put('auction_template_samples/preview.png', 'sample');

        $migration = require database_path('migrations/admin/2026_10_25_000002_remove_auction_template_samples_feature.php');
        $migration->up();

        Storage::disk('central_public')->assertExists('auction_template_samples/preview.png');
    }

    public function test_feature_removal_migration_forgets_deleted_sample_permissions(): void
    {
        Permission::create([
            'name' => 'auction_template_samples.view',
            'guard_name' => 'admin',
        ]);

        $registrar = app(PermissionRegistrar::class);
        $registrar->getPermissions();

        $migration = require database_path('migrations/admin/2026_10_25_000002_remove_auction_template_samples_feature.php');
        $migration->up();

        self::assertFalse($registrar->getPermissions()->contains('name', 'auction_template_samples.view'));
    }
}
