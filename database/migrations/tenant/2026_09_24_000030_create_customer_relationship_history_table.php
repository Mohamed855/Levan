<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_relationship_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('previous_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('new_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_type')->index();
            $table->timestamp('changed_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_relationship_history');
    }
};
