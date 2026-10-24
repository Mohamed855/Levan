<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managed_domains', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('domain', 253)->unique();
            $table->string('type', 30)->index(); // linked, platform_subdomain, registered, transfer
            $table->string('status', 30)->default('pending_dns')->index();
            $table->boolean('is_active')->default(false)->index();
            $table->string('verification_token', 255)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('registrar', 50)->nullable();
            $table->string('registrar_order_id', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_domains');
    }
};
