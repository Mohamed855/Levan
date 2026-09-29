<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->decimal('collected_amount', 14, 2);
            $table->string('collection_type')->default('cash')->index();
            $table->foreignId('payment_method_id')->nullable()->constrained('settings_payment_methods')->nullOnDelete();
            $table->string('receipt_number')->nullable()->index();
            $table->string('closing_code')->nullable();
            $table->date('collection_date');
            $table->string('payment_type')->default('settlement')->index();
            $table->string('attachment')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('source')->nullable()->index();
            $table->unsignedBigInteger('reconciliation_item_id')->nullable()->index();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['customer_id', 'collection_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
