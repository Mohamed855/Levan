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
        Schema::create('company_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->decimal('target_percentage', 5, 2)->comment('نسبة المستهدف الشهري');
            $table->date('effective_from')->comment('تاريخ بدء المستهدف');
            $table->date('effective_to')->nullable()->comment('تاريخ انتهاء المستهدف (NULL = نشط)');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['company_id', 'effective_from', 'effective_to'], 'company_targets_dates_idx');
            $table->index(['company_id', 'effective_to'], 'company_targets_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_targets');
    }
};
