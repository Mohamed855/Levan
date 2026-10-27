<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table): void {
            if (Schema::hasColumn('assignments', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table): void {
            if (! Schema::hasColumn('assignments', 'status')) {
                $table->enum('status', ['active', 'under_review', 'suspended', 'auctioned'])->default('active')->index();
            }
        });
    }
};
