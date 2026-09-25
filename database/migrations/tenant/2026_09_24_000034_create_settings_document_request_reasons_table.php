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
        Schema::create('settings_document_request_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('اسم سبب طلب المستندات');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('حالة السبب');
            $table->unsignedBigInteger('user_id')->nullable()->comment('المستخدم الذي أضاف السبب');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings_document_request_reasons');
    }
};
