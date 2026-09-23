<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_license_adjustments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('period_id')->nullable()->constrained('subscription_periods')->nullOnDelete();

            $table->foreignId('bill_id')->nullable()->constrained('bills')->nullOnDelete();

            $table->unsignedInteger('new_user_license');

            $table->unsignedInteger('old_user_license_snapshot');
            $table->decimal('extra_user_license_price_snapshot', 10, 2);

            $table->softDeletes();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_license_adjustments');
    }
};
