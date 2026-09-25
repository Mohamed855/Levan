<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recovery_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_result_id');
            $table->enum('recovery_reason', ['valid_print', 'has_license', 'star_report', 'traffic_decision', 'court_deed', 'other'])->default('valid_print');
            $table->string('recovery_document_path')->nullable();
            $table->string('other_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('contact_result_id')
                ->references('id')
                ->on('customer_follows')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_details');
    }
};
