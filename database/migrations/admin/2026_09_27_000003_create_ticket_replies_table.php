<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('ticket_replies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ticket_id')->index();
            $table->string('ticket_number')->index();
            $table->string('sender_type', 20)->default('tenant');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->longText('message');
            $table->json('attachments')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('ticket_replies');
    }
};
