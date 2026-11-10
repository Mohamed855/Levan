<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('mysql')->hasColumn('coupons', 'start_date')) {
            Schema::connection('mysql')->table('coupons', function (Blueprint $table): void {
                $table->date('start_date')->nullable()->after('max_discount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('mysql')->hasColumn('coupons', 'start_date')) {
            Schema::connection('mysql')->table('coupons', function (Blueprint $table): void {
                $table->dropColumn('start_date');
            });
        }
    }
};

