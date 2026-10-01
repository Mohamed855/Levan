<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('properties_limit')->nullable()->after('included_users');
            $table->unsignedInteger('storage_quota_gb')->nullable()->after('properties_limit');
            $table->unsignedInteger('active_auctions_limit')->nullable()->after('storage_quota_gb');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'properties_limit',
                'storage_quota_gb',
                'active_auctions_limit',
            ]);
        });
    }
};
