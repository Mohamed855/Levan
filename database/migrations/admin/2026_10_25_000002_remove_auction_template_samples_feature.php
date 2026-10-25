<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'auction_template_samples.view',
        'auction_template_samples.create',
        'auction_template_samples.edit',
        'auction_template_samples.delete',
    ];

    public function up(): void
    {
        if (! app()->runningUnitTests()) {
            Storage::disk('central_public')->deleteDirectory('auction_template_samples');
        }

        if (Schema::hasTable('activity_log')) {
            DB::table('activity_log')
                ->where('log_name', 'auction_template_samples')
                ->orWhere('subject_type', 'App\\Models\\Admin\\AuctionTemplates\\AuctionTemplateSample')
                ->delete();
        }

        $permissionsTable = (string) config('permission.table_names.permissions', 'permissions');

        if (Schema::hasTable($permissionsTable)) {
            DB::table($permissionsTable)
                ->where('guard_name', 'admin')
                ->whereIn('name', self::PERMISSIONS)
                ->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::dropIfExists('auction_template_samples');
    }

    public function down(): void
    {
        // This destructive feature-removal migration intentionally cannot restore deleted sample data or assets.
    }
};
