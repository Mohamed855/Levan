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
        if (Schema::connection('mysql')->hasTable('auction_templates')) {
            // Remove any legacy brochure templates if present to avoid ALTER enum truncation
            DB::connection('mysql')->table('auction_templates')->where('type', 'brochure')->delete();

            if (DB::connection('mysql')->getDriverName() === 'mysql') {
                DB::connection('mysql')->statement("ALTER TABLE auction_templates MODIFY COLUMN type ENUM('billboard', 'social') NOT NULL");
            } else {
                Schema::connection('mysql')->table('auction_templates', function (Blueprint $table) {
                    $table->string('type', 32)->change();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::connection('mysql')->hasTable('auction_templates')) {
            if (DB::connection('mysql')->getDriverName() === 'mysql') {
                DB::connection('mysql')->statement("ALTER TABLE auction_templates MODIFY COLUMN type ENUM('brochure', 'billboard', 'social') NOT NULL");
            }
        }
    }
};
