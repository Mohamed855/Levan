<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            $table->dropColumn(['branches_limit', 'unlimited_branches']);
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            $table->integer('branches_limit')->nullable()->after('active_auctions_limit');
            $table->boolean('unlimited_branches')->default(false)->after('branches_limit');
        });
    }
};
