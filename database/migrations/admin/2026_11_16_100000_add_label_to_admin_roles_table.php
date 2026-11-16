<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('permission.table_names.roles', 'roles');
        if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'label')) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('label')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        $table = config('permission.table_names.roles', 'roles');
        if (Schema::hasTable($table) && Schema::hasColumn($table, 'label')) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('label');
            });
        }
    }
};
