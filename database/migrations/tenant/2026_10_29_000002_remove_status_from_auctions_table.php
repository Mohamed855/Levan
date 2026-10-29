<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table): void {
            if (Schema::hasColumn('auctions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table): void {
            if (! Schema::hasColumn('auctions', 'status')) {
                $table->enum('status', ['in_preparation', 'pending_approval', 'active', 'completed', 'suspended'])
                    ->default('in_preparation')
                    ->index();
            }
        });
    }
};
