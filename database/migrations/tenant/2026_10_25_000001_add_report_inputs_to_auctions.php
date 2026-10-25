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
            $table->string('project_manager_name')->nullable()->after('supervising_entity');
            $table->string('report_image')->nullable()->after('cover_image');
            $table->json('marketing_data')->nullable()->after('notes');
        });

        Schema::table('auction_properties', function (Blueprint $table): void {
            $table->string('result_status', 20)->nullable()->after('sort_order')->index();
            $table->decimal('opening_price', 15, 2)->nullable()->after('result_status');
            $table->decimal('sale_price', 15, 2)->nullable()->after('opening_price');
            $table->unsignedInteger('participants_count')->nullable()->after('sale_price');
            $table->unsignedInteger('bids_count')->nullable()->after('participants_count');
            $table->text('unsold_reason')->nullable()->after('bids_count');
            $table->json('board_data')->nullable()->after('unsold_reason');
        });

        Schema::table('auction_attachments', function (Blueprint $table): void {
            $table->string('report_section', 50)->nullable()->after('category')->index();
            $table->foreignId('property_id')->nullable()->after('report_section')
                ->constrained('properties')->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0)->after('property_id');
        });
    }

    public function down(): void
    {
        Schema::table('auction_attachments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('property_id');
            $table->dropColumn(['report_section', 'display_order']);
        });

        Schema::table('auction_properties', function (Blueprint $table): void {
            $table->dropColumn([
                'result_status',
                'opening_price',
                'sale_price',
                'participants_count',
                'bids_count',
                'unsold_reason',
                'board_data',
            ]);
        });

        Schema::table('auctions', function (Blueprint $table): void {
            $table->dropColumn(['project_manager_name', 'report_image', 'marketing_data']);
        });
    }
};
