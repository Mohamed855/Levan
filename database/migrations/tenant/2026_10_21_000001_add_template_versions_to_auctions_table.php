<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            if (! Schema::hasColumn('auctions', 'template_versions')) {
                $table->json('template_versions')->nullable()->after('cover_image');
            }
            if (! Schema::hasColumn('auctions', 'template_snapshot')) {
                $table->json('template_snapshot')->nullable()->after('template_versions');
            }
            if (! Schema::hasColumn('auctions', 'template_bindings')) {
                $table->json('template_bindings')->nullable()->after('template_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('auctions', 'template_bindings')) {
                $columnsToDrop[] = 'template_bindings';
            }
            if (Schema::hasColumn('auctions', 'template_snapshot')) {
                $columnsToDrop[] = 'template_snapshot';
            }
            if (Schema::hasColumn('auctions', 'template_versions')) {
                $columnsToDrop[] = 'template_versions';
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
