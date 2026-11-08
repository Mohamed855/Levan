<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_closings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('auction_id')->unique()->constrained('auctions')->cascadeOnDelete();
            $table->date('closing_date');
            $table->string('approval_number')->nullable();
            $table->timestamp('saved_at');
            $table->json('finalized_snapshot')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_closings');
    }
};
