<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->string('tenant_id')->index();
            $table->unsignedBigInteger('tenant_user_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->unsignedBigInteger('ticketing_sections_id')->default(1);
            $table->string('title');
            $table->longText('description');
            $table->string('status', 50)->default('open');
            $table->string('priority', 20)->default('medium');
            $table->json('attachments')->nullable();
            $table->json('survey_result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('tickets');
    }
};
