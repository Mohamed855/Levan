<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_letters', function (Blueprint $table) {
            $table->id();
            
            // البيانات الأساسية
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('customer_follow_id')->constrained('customer_follows')->onDelete('cascade');
            $table->foreignId('collection_id')->nullable()->constrained('collections')->onDelete('set null');
            
            // نوع الإجراء
            $table->enum('action_type', [
                'payment_full',
                'payment_partial',
                'document_request',
                'recovery_request'
            ])->comment('نوع الإجراء');
            
            // حالة المراجعة
            $table->enum('status', [
                'pending_approval',
                'approved_ready_to_send',
                'ready_to_send',
                'sent',
                'failed'
            ])->default('ready_to_send');
            
            // بيانات الاعتماد
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // بيانات البريد
            $table->foreignId('email_template_id')->nullable()->constrained('message_templates')->onDelete('set null');
            $table->foreignId('sent_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('sent_at')->nullable();
            $table->string('sent_to')->nullable();
            $table->text('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->text('failure_reason')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('customer_id');
            $table->index('action_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_letters');
    }
};
