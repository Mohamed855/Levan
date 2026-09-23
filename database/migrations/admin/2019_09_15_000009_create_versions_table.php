<?php

use App\Enums\Admin\Version\VersionStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('versions', function (Blueprint $table) {
            $table->id();

            // تفاصيل عامة للنسخة
            $table->string('version', 50)->unique();
            $table->text('changelog')->nullable(); //  التغييرات

            // معلومات الملف
            $table->string('file_path', 255); // releases/v2.0.0

            $table->enum('stage', array_column(VersionStage::cases(), 'value'))->default(VersionStage::ALPHA->value);

            $table->boolean('is_active')->default(true); // متاحة

            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->foreignId('created_by')->constrained('admin_users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('admin_users')->onDelete('cascade');

            $table->index(['stage', 'is_active']);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('versions');
    }
};
