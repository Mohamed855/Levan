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
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();

            // معلومات المرسل
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('sender_type')->default('user');

            // معلومات الرسالة
            $table->text('message_content');
            $table->string('message_type')->default('sms');
            $table->string('platform')->default('4jawaly');

            // معلومات القالب (إن وجد)
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('template_name')->nullable();

            // معلومات المستلمين
            $table->string('target_model')->nullable();
            $table->json('recipient_ids')->nullable();
            $table->json('recipients_data')->nullable();
            $table->integer('recipients_count')->default(0);

            // معلومات الإرسال
            $table->enum('status', ['pending', 'processing', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->text('status_message')->nullable();
            $table->json('response_data')->nullable();

            // إحصائيات
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->decimal('cost', 8, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();

            // تواريخ مهمة
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // معلومات إضافية
            $table->json('metadata')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('batch_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['sender_id', 'sender_type']);
            $table->index(['target_model', 'status']);
            $table->index(['template_id']);
            $table->index(['status', 'created_at']);
            $table->index(['batch_id']);
            $table->index(['scheduled_at']);

            $table->foreign('sender_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('template_id')->references('id')->on('message_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
