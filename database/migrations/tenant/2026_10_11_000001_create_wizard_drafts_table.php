<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wizard_drafts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('guard', 20)->default('tenant');
            $table->unsignedBigInteger('user_id');
            $table->string('tenant_id')->default('');
            $table->string('resource_key');
            $table->unsignedTinyInteger('last_step')->default(1);
            $table->json('draft_data')->nullable();
            $table->json('draft_files')->nullable();
            $table->timestamps();

            $table->unique(['guard', 'user_id', 'tenant_id', 'resource_key'], 'wizard_drafts_owner_resource_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wizard_drafts');
    }
};
