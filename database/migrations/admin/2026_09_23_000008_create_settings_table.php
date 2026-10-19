<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('office_name')->nullable();
            $table->string('logo_text')->nullable();
            $table->unsignedInteger('trial_days')->default(0);  // فترة تجريبية بالأيام

            $table->boolean('maintenance_mode')->default(false);
            $table->boolean('trial_days_mode')->default(false);
            $table->boolean('force_two_factor_auth')->default(false);

            $table->boolean('etmam_enabled')->default(false);
            $table->boolean('tickets_enabled')->default(true);
            $table->string('etmam_base_url')->nullable();
            $table->string('etmam_system_code')->nullable();
            $table->string('etmam_api_token', 120)->nullable();
            $table->string('etmam_hmac_secret', 120)->nullable();


            $table->string('microsoft_client_id')->nullable();
            $table->text('microsoft_client_secret')->nullable();
            $table->string('microsoft_redirect_uri')->nullable();
            $table->string('microsoft_tenant_id')->nullable();
            $table->string('main_email')->nullable();

            $table->string('office_address')->nullable();
            $table->string('commercial_register')->nullable(); // السجل التجاري
            $table->string('tax_number')->nullable(); // الرقم الضريبي
            $table->string('phone_number')->nullable(); // رقم الهاتف
            $table->string('email')->nullable(); // البريد الإلكتروني

            $table->string('facebook_url')->nullable();
            $table->string('x_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->text('google_maps_embed_url')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
