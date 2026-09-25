<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('contact_method_id')->nullable()->constrained('settings_contact_methods')->nullOnDelete();
            $table->string('contact_location')->nullable();
            $table->string('contacted_person')->nullable();
            $table->string('contacted_person_name')->nullable();
            $table->dateTime('next_contact_datetime')->nullable()->index();
            $table->foreignId('customer_status_id')->nullable()->constrained('settings_customer_status')->nullOnDelete();
            $table->foreignId('contact_result_id')->nullable()->constrained('contact_results')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('result_details')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['customer_id', 'next_contact_datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_follows');
    }
};
