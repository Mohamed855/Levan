<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->uuid('checkout_token')->unique();
            $table->string('provider', 30);
            $table->string('provider_payment_id')->nullable()->unique();
            $table->string('payment_method', 30);
            $table->string('status', 30)->default('initiated')->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('SAR');
            $table->json('provider_payload')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['bill_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
