<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table): void {
            $columnsToDrop = [];
            foreach ([
                'template_versions',
                'template_snapshot',
                'template_bindings',
                'brochure_template_id',
                'billboard_template_id',
                'social_template_id',
            ] as $col) {
                if (Schema::hasColumn('auctions', $col)) {
                    $columnsToDrop[] = $col;
                }
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::table('assignments', function (Blueprint $table): void {
            if (Schema::hasColumn('assignments', 'entity_logo')) {
                $table->dropColumn('entity_logo');
            }
        });

        Schema::table('auction_report_generations', function (Blueprint $table): void {
            if (! Schema::hasColumn('auction_report_generations', 'progress')) {
                $table->unsignedTinyInteger('progress')->default(0)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('auction_report_generations', function (Blueprint $table): void {
            if (Schema::hasColumn('auction_report_generations', 'progress')) {
                $table->dropColumn('progress');
            }
        });

        Schema::table('assignments', function (Blueprint $table): void {
            if (! Schema::hasColumn('assignments', 'entity_logo')) {
                $table->string('entity_logo')->nullable()->after('issuing_entity');
            }
        });

        Schema::table('auctions', function (Blueprint $table): void {
            if (! Schema::hasColumn('auctions', 'brochure_template_id')) {
                $table->unsignedBigInteger('brochure_template_id')->nullable()->index();
            }
            if (! Schema::hasColumn('auctions', 'billboard_template_id')) {
                $table->unsignedBigInteger('billboard_template_id')->nullable()->index();
            }
            if (! Schema::hasColumn('auctions', 'social_template_id')) {
                $table->unsignedBigInteger('social_template_id')->nullable()->index();
            }
            if (! Schema::hasColumn('auctions', 'template_versions')) {
                $table->json('template_versions')->nullable();
            }
            if (! Schema::hasColumn('auctions', 'template_snapshot')) {
                $table->json('template_snapshot')->nullable();
            }
            if (! Schema::hasColumn('auctions', 'template_bindings')) {
                $table->json('template_bindings')->nullable();
            }
        });
    }
};
