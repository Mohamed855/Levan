<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'default_date')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->date('default_date')->nullable()->after('contract_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'default_date')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('default_date');
            });
        }
    }
};
