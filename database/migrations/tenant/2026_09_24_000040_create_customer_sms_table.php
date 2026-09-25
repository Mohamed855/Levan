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
        Schema::create('customer_sms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('message_template_id')->nullable()->constrained('message_templates')->onDelete('set null');
            $table->string('phone_number');
            $table->string('phone_type')->default('primary');
            $table->string('template_name')->nullable();
            $table->text('message_content');
            $table->enum('status', ['pending', 'sent', 'failed', 'delivered'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index(['sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_sms');
    }
};
