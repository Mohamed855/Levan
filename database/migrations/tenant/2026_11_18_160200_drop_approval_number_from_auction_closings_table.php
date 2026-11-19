<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('auction_closings') && Schema::hasColumn('auction_closings', 'approval_number')) {
            Schema::table('auction_closings', function (Blueprint $table): void {
                $table->dropColumn('approval_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('auction_closings') && ! Schema::hasColumn('auction_closings', 'approval_number')) {
            Schema::table('auction_closings', function (Blueprint $table): void {
                $table->string('approval_number')->nullable()->after('closing_date');
            });
        }
    }
};
