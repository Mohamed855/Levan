<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('auction_templates', 'status')) {
                $table->string('status', 20)->default('draft')->index()->after('description');
            }
            if (! Schema::hasColumn('auction_templates', 'current_version')) {
                $table->unsignedInteger('current_version')->nullable()->after('is_active');
            }
            $table->string('file_path')->nullable()->change();
        });

        if (! Schema::hasTable('auction_template_versions')) {
            Schema::create('auction_template_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('auction_template_id')->constrained('auction_templates')->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->json('schema');
                $table->string('status', 20)->default('draft')->index();
                $table->timestamp('published_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['auction_template_id', 'version']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_template_versions');

        Schema::table('auction_templates', function (Blueprint $table) {
            if (Schema::hasColumn('auction_templates', 'current_version')) {
                $table->dropColumn('current_version');
            }
            if (Schema::hasColumn('auction_templates', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
