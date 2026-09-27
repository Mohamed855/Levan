<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('mysql')->hasTable('ticket_sections')) {
            Schema::connection('mysql')->create('ticket_sections', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed default sections
            \Illuminate\Support\Facades\DB::connection('mysql')->table('ticket_sections')->insert([
                ['name' => 'الدعم الفني', 'description' => 'المشاكل الفنية والأخطاء البرمجية', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'الاشتراكات والفوترة', 'description' => 'الاستفسارات المالية وتجديد الاشتراكات', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'الاستفسارات العامة', 'description' => 'الأسئلة العامة والمقترحات', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (!Schema::connection('mysql')->hasTable('tickets')) {
            Schema::connection('mysql')->create('tickets', function (Blueprint $table) {
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

        if (!Schema::connection('mysql')->hasTable('ticket_replies')) {
            Schema::connection('mysql')->create('ticket_replies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id')->index();
                $table->string('ticket_number')->index();
                $table->string('sender_type', 20)->default('tenant'); // 'tenant' or 'admin'
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_name')->nullable();
                $table->longText('message');
                $table->json('attachments')->nullable();
                $table->unsignedTinyInteger('rating')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('ticket_replies');
        Schema::connection('mysql')->dropIfExists('tickets');
        Schema::connection('mysql')->dropIfExists('ticket_sections');
    }
};
