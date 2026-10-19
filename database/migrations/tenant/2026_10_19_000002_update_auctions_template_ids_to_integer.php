<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First ensure any existing non-numeric string values are set to null so the column alteration succeeds
        if (Schema::hasTable('auctions')) {
            DB::table('auctions')
                ->whereRaw("brochure_template_id NOT REGEXP '^[0-9]+$'")
                ->update(['brochure_template_id' => null]);

            DB::table('auctions')
                ->whereRaw("billboard_template_id NOT REGEXP '^[0-9]+$'")
                ->update(['billboard_template_id' => null]);

            DB::table('auctions')
                ->whereRaw("social_template_id NOT REGEXP '^[0-9]+$'")
                ->update(['social_template_id' => null]);
        }

        Schema::table('auctions', function (Blueprint $table) {
            $table->unsignedBigInteger('brochure_template_id')->nullable()->change();
            $table->unsignedBigInteger('billboard_template_id')->nullable()->change();
            $table->unsignedBigInteger('social_template_id')->nullable()->change();
            $table->index('brochure_template_id');
            $table->index('billboard_template_id');
            $table->index('social_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropIndex(['brochure_template_id']);
            $table->dropIndex(['billboard_template_id']);
            $table->dropIndex(['social_template_id']);
            $table->string('brochure_template_id')->nullable()->change();
            $table->string('billboard_template_id')->nullable()->change();
            $table->string('social_template_id')->nullable()->change();
        });
    }
};
