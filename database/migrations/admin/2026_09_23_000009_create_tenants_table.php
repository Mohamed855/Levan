<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('db_database')->index();
            $table->string('db_username');
            $table->string('db_password');

            $table->string('db_host')->default('127.0.0.1');
            $table->string('db_port')->default('3306');
            $table->string('folderName')->nullable();

            $table->string('company_name');
            $table->string('commercial_number');
            $table->string('primary_contact_name')->nullable();
            $table->string('primary_contact_email')->nullable();
            $table->string('primary_contact_phone')->nullable();
            $table->string('primary_user_global_uid')->nullable();
            $table->string('tax_number')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('address')->nullable();
            $table->enum('access_type', ['path', 'domain'])->default('path');

            // هل تم تفعيل الدومين المخصص؟
            $table->boolean('custom_domain_active')->default(false);

            $table->string('api_token', 80)->unique()->nullable();
            $table->timestamp('api_token_regenerated_at')->nullable();

            // تاريخ تفعيل الدومين
            $table->timestamp('domain_activated_at')->nullable();

            $table->softDeletes();

            $table->timestamps();
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
