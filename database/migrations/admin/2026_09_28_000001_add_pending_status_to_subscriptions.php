<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // تعديل عمود الحالة ليقبل قيمة pending وتصبح الافتراضية
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('pending','active','suspended','cancelled','expired','trial') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('active','suspended','cancelled','expired','trial') NOT NULL DEFAULT 'suspended'");
    }
};
