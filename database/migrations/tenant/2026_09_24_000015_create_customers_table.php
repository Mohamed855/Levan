<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('grandfather_name')->nullable();
            $table->string('family_name')->nullable();
            $table->string('customer_type')->default('individual')->index();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('portfolio_id')->nullable()->constrained('portfolios')->nullOnDelete();
            $table->string('identity_number')->nullable()->index();
            $table->string('nationality')->nullable();
            $table->foreignId('relationship_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_status_id')->nullable()->constrained('settings_customer_status')->nullOnDelete();
            $table->string('phone', 30)->nullable();
            $table->string('account_number')->nullable()->index();
            $table->text('last_sms_text')->nullable();
            $table->timestamp('last_sms_at')->nullable();
            $table->foreignId('last_contact_result_id')->nullable()->constrained('contact_results')->nullOnDelete();
            $table->date('contract_date')->nullable();
            $table->decimal('commission_rate', 8, 2)->nullable();
            $table->date('default_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'portfolio_id']);
            $table->index(['relationship_manager_id', 'customer_status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
