<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('drafted_by')->nullable()->after('draft_data')
                ->constrained('admin_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', fn (Blueprint $table) => $table->dropConstrainedForeignId('drafted_by'));
    }
};
