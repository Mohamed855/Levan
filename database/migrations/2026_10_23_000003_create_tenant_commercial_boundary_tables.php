<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_seats', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            // Tenant users live in a different database, so this deliberately is not a foreign key.
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->string('status', 20)->default('free')->index();
            $table->timestamp('assigned_at')->nullable();
            $table->boolean('is_core')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'assigned_user_id']);
            $table->index(['tenant_id', 'subscription_id', 'status']);
        });

        Schema::create('tenant_api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('name', 100)->default('default');
            $table->string('token_prefix', 16);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_api_tokens');
        Schema::dropIfExists('license_seats');
    }
};
