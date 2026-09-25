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
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->longText('content');
            $table->enum('category', ['customers', 'company', 'users', 'general'])->default('general');
            $table->enum('channel', ['sms', 'email', 'whatsapp', 'multiple'])->default('sms');
            $table->boolean('is_active')->default(true);
            $table->json('available_variables')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'is_active']);
            $table->index(['channel']);
            $table->index(['category', 'channel', 'is_active']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
