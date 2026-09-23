<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('license_seats', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            //  الاشتراك
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('cascade');

            $table->uuid('assigned_user_uid')->nullable();
            $table->enum('status', ['free', 'assigned', 'suspended'])->default('free');
            $table->timestamp('assigned_at')->nullable();

            $table->boolean('is_core')->default(false);

            $table->json('meta')->nullable();
            $table->timestamps();

            // يمنع مقعدين لنفس المستخدم داخل نفس التيننت
            $table->unique(['tenant_id', 'assigned_user_uid']);

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_seats');
    }
};
