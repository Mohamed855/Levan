<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('type', 30)->index();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('current_version')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('auction_template_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('auction_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('schema');
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['auction_template_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_template_versions');
        Schema::dropIfExists('auction_templates');
    }
};