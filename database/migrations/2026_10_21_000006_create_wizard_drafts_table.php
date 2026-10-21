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
            $table->string('guard', 20);
            $table->unsignedBigInteger('user_id');
            $table->string('resource_key');
            $table->unsignedTinyInteger('last_step')->default(1);
            $table->json('draft_data')->nullable();
            $table->json('draft_files')->nullable();
            $table->timestamps();
            $table->unique(['guard', 'user_id', 'resource_key']);
        });
    }

    public function down(): void { Schema::dropIfExists('wizard_drafts'); }
};