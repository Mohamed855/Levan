<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('ticket_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::connection('mysql')->table('ticket_sections')->insert([
            ['name' => 'الدعم الفني', 'description' => 'المشاكل الفنية والأخطاء البرمجية', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'الاشتراكات والفوترة', 'description' => 'الاستفسارات المالية وتجديد الاشتراكات', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'الاستفسارات العامة', 'description' => 'الأسئلة العامة والمقترحات', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('ticket_sections');
    }
};
