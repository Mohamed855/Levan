<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_properties', function (Blueprint $table): void {
            $table->decimal('entry_amount', 15, 2)->nullable()->after('opening_price');
            $table->decimal('ownership_share', 5, 2)->default(100.00)->after('entry_amount');
            $table->text('disclosure')->nullable()->after('ownership_share');
        });
    }

    public function down(): void
    {
        Schema::table('auction_properties', function (Blueprint $table): void {
            $table->dropColumn(['entry_amount', 'ownership_share', 'disclosure']);
        });
    }
};
