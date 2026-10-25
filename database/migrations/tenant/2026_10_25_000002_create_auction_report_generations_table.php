<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_report_generations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('auction_id')->constrained('auctions')->cascadeOnDelete();
            $table->uuid('revision_token')->unique();
            $table->string('status', 20)->default('queued')->index();
            $table->json('snapshot');
            $table->string('outputs_path')->nullable();
            $table->string('report_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['auction_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_report_generations');
    }
};
