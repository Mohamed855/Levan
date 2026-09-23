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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();

            $table->decimal('price', 10, 2);

            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('included_users')->default(1); // عدد المستخدمين مع الباقة

            $table->foreignId('created_by')->constrained('admin_users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('admin_users')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
