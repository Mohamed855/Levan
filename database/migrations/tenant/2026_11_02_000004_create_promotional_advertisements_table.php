<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotional_advertisements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('auction_id')->constrained('auctions')->cascadeOnDelete();
            $table->unsignedBigInteger('auction_template_id')->index();
            $table->string('template_type', 32)->index();
            $table->string('template_name', 255);
            $table->string('status', 32)->default('processing')->index();
            $table->string('output_path', 1024)->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotional_advertisements');
    }
};
