<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_replies', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_user_id')->nullable()->after('admin_user_id');
            $table->string('tenant_user_name')->nullable()->after('tenant_user_id');
            $table->index(['ticket_id', 'tenant_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('ticket_replies', function (Blueprint $table): void {
            $table->dropIndex(['ticket_id', 'tenant_user_id']);
            $table->dropColumn(['tenant_user_id', 'tenant_user_name']);
        });
    }
};
