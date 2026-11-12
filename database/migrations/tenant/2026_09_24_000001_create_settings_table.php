<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('office_name')->nullable();
            $table->string('logo_text')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->boolean('breadcrumb_visibility')->default(true);
            $table->boolean('whatsapp_enabled')->default(false);
            $table->json('support_emails')->nullable();
            $table->json('operational_regions')->nullable();
            $table->string('report_phone', 30)->nullable();
            $table->string('report_unified_phone', 30)->nullable();
            $table->string('report_social')->nullable();
            $table->string('twilio_account_sid')->nullable();
            $table->text('twilio_auth_token')->nullable();
            $table->string('twilio_whatsapp_from')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
