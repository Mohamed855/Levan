<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_id')->nullable()->constrained('assignments')->nullOnDelete();
            $table->string('property_number')->index();
            $table->string('name');
            $table->string('type')->default('residential_land')->index();
            $table->string('usage_classification')->nullable()->default('residential')->index();
            $table->text('short_description')->nullable();
            $table->string('city');
            $table->string('district')->nullable();
            $table->string('administrative_region')->nullable();
            $table->string('plan_number')->nullable();
            $table->string('parcel_number')->nullable();
            $table->text('google_maps_url')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('area', 12, 2)->default(0);
            $table->string('deed_number')->nullable()->index();
            $table->date('deed_date')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->string('ownership_type')->nullable()->default('sole_ownership');
            $table->string('agent_name')->nullable();
            $table->string('agent_national_id')->nullable();
            $table->string('agency_number')->nullable();
            $table->date('agency_date')->nullable();
            $table->decimal('estimated_value', 15, 2)->default(0);
            $table->string('data_status', 50)->default('incomplete')->index();
            $table->enum('status', ['available', 'assigned_to_auction', 'sold'])->default('available')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('main_image')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
