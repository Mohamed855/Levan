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
        Schema::create('cpanel_accounts', function (Blueprint $table) {
            $table->id();

            // ✅ معلومات الـ tenant
            $table->string('tenant_id')->index();
            $table->string('domain')->unique();

            // ✅ بيانات cPanel
            $table->string('cpanel_username', 16)->unique();
            $table->text('cpanel_password'); // ✅ encrypted
            $table->text('cpanel_token')->nullable(); // ✅ API token

            // ✅ معلومات إضافية
            $table->string('plan')->default('default_levan');
            $table->string('server_ip')->nullable();
            $table->string('contact_email')->nullable();

            // ✅ حالة الحساب
            $table->enum('status', ['active', 'suspended', 'terminated'])->default('active');
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('token_created_at')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('last_login_at')->nullable();

            // $table->timestamps();

            // ✅ Indexes للبحث السريع
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpanel_accounts');
    }
};
