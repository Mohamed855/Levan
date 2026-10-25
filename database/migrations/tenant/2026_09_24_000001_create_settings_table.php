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
            $table->boolean('force_two_factor_auth')->default(false);
            $table->boolean('whatsapp_enabled')->default(false);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('sms_auto_reminders_enabled')->default(false);
            $table->unsignedInteger('sms_rate_limit')->nullable();
            $table->json('support_emails')->nullable();
            $table->string('twilio_account_sid')->nullable();
            $table->text('twilio_auth_token')->nullable();
            $table->string('twilio_whatsapp_from')->nullable();
            $table->string('horizontal_header_image')->nullable();
            $table->string('vertical_header_image')->nullable();
            $table->string('horizontal_footer_image')->nullable();
            $table->string('vertical_footer_image')->nullable();
            $table->string('signature')->nullable();
            $table->string('sms_provider')->nullable();
            $table->text('sms_api_key')->nullable();
            $table->text('sms_api_secret')->nullable();
            $table->string('sms_api_endpoint')->nullable();
            $table->string('sms_sender_id')->nullable();
            $table->string('sms_from_number')->nullable();
            $table->string('sms_callback_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
