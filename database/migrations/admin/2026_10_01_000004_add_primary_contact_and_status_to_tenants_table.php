<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('primary_contact_name')->nullable()->after('commercial_number');
            $table->string('primary_contact_email')->nullable()->after('primary_contact_name');
            $table->string('primary_contact_phone')->nullable()->after('primary_contact_email');
            $table->string('primary_user_global_uid')->nullable()->after('primary_contact_phone');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tenants MODIFY COLUMN status ENUM('draft', 'pending', 'active', 'suspended') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tenants MODIFY COLUMN status ENUM('draft', 'pending', 'active') DEFAULT 'pending'");
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'primary_contact_name',
                'primary_contact_email',
                'primary_contact_phone',
                'primary_user_global_uid',
            ]);
        });
    }
};
