<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message_individual_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_log_id')->constrained('message_logs')->onDelete('cascade');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name');
            $table->json('phone_numbers');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('portfolio_id')->nullable();
            $table->longText('message_content');
            $table->string('message_type')->default('sms');
            $table->string('platform')->default('4jawaly');
            $table->foreignId('template_id')->nullable()->constrained('message_templates')->onDelete('set null');
            $table->string('template_name')->nullable();
            $table->string('provider_job_id')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->json('provider_response')->nullable();
            $table->string('sender_name')->nullable();
            $table->enum('status', [
                'pending',
                'processing',
                'sent',
                'failed',
                'cancelled'
            ])->default('pending');

            $table->string('status_message')->nullable();
            $table->text('error_message')->nullable();
            $table->decimal('cost_per_message', 8, 4)->nullable();
            $table->decimal('total_cost', 10, 4)->nullable();
            $table->integer('messages_count')->default(1);
            $table->integer('phone_numbers_count')->default(1);
            $table->integer('successful_sends')->default(0);
            $table->integer('failed_sends')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('delivery_confirmed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();

            $table->foreignId('sent_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['batch_log_id', 'status']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['provider_job_id']);
            $table->index(['sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_individual_logs');
    }
};
