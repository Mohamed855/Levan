<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('mysql')->hasTable('notifications')) {
            Schema::connection('mysql')->create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
        Schema::connection('mysql')->create('dashboard_notification_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('scope', 10);
            $table->string('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->json('payload');
            $table->timestamp('delivered_at')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('dashboard_notification_deliveries');
        // Keep the shared notifications table: it may predate this feature.
    }
};
