<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure any remaining draft rows are removed or updated
        if (Schema::hasTable('tenants')) {
            DB::table('tenants')->where('status', 'draft')->delete();

            // 2. Drop foreign key if exists
            Schema::table('tenants', function (Blueprint $table) {
                if (Schema::hasColumn('tenants', 'drafted_by')) {
                    $table->dropForeign(['drafted_by']);
                }
            });

            // 3. Drop legacy draft columns
            Schema::table('tenants', function (Blueprint $table) {
                $columnsToDrop = [];
                foreach (['draft_step', 'draft_data', 'drafted_by'] as $col) {
                    if (Schema::hasColumn('tenants', $col)) {
                        $columnsToDrop[] = $col;
                    }
                }
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });

            // 4. Modify status column enum to remove 'draft'
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE tenants MODIFY COLUMN status ENUM('active', 'pending', 'suspended') DEFAULT 'pending'");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenants')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE tenants MODIFY COLUMN status ENUM('draft', 'pending', 'active', 'suspended') DEFAULT 'pending'");
            }

            Schema::table('tenants', function (Blueprint $table) {
                $table->unsignedTinyInteger('draft_step')->nullable();
                $table->json('draft_data')->nullable();
                $table->foreignId('drafted_by')->nullable()->constrained('admin_users')->nullOnDelete();
            });
        }
    }
};
