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
        if (! Schema::hasColumn('auction_properties', 'buyer_name')) {
            Schema::table('auction_properties', fn (Blueprint $table) => $table->string('buyer_name')->nullable());
        }
        if (! Schema::hasColumn('auction_properties', 'result_notes')) {
            Schema::table('auction_properties', fn (Blueprint $table) => $table->text('result_notes')->nullable());
        }
        if (! Schema::hasColumn('auctions', 'published_report_generation_id')) {
            Schema::table('auctions', fn (Blueprint $table) => $table->foreignId('published_report_generation_id')->nullable()->constrained('auction_report_generations')->nullOnDelete());
        }
        foreach (DB::table('auction_report_generations')->select(['id', 'auction_id'])->where('status', 'ready')->orderBy('id')->cursor() as $generation) {
            DB::table('auctions')->where('id', $generation->auction_id)->update(['published_report_generation_id' => $generation->id]);
        }
    }

    public function down(): void
    {
        Schema::table('auctions', fn (Blueprint $table) => $table->dropConstrainedForeignId('published_report_generation_id'));
        Schema::table('auction_properties', fn (Blueprint $table) => $table->dropColumn(['buyer_name', 'result_notes']));
    }
};
