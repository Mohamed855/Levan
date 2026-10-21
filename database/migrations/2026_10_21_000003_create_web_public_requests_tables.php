<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_name', 100);
            $table->string('customer_email');
            $table->text('message');
            $table->string('status', 30)->default('pending')->index();
            $table->timestamps();
        });

        Schema::create('trial_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_name', 100);
            $table->string('customer_email');
            $table->string('customer_phone', 20);
            $table->string('status', 30)->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_requests');
        Schema::dropIfExists('contact_requests');
    }
};
