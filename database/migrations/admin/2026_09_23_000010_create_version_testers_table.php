<?php

use App\Enums\Admin\Version\VersionTesterType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('version_testers', function (Blueprint $table) {
            $table->id();

            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            // نوع
            $table->enum('type', array_column(VersionTesterType::cases(), 'value'))->default(VersionTesterType::TESTER->value);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            //
            $table->unique('tenant_id');
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('version_testers');
    }
};
