<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->string('status')->default('active')->after('password');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->ipAddress('last_login_ip')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['phone', 'status', 'last_login_at', 'last_login_ip']);
        });
    }
};
