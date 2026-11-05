<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('settings', 'force_two_factor_auth')) {
            Schema::table('settings', function (Blueprint $table): void {
                $table->dropColumn('force_two_factor_auth');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('settings', 'force_two_factor_auth')) {
            Schema::table('settings', function (Blueprint $table): void {
                $table->boolean('force_two_factor_auth')->default(false);
            });
        }
    }
};
