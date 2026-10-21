<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 20)->nullable()->unique();
            $table->string('password');
            $table->string('status')->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->ipAddress('last_login_ip')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('assignment_number')->unique();
            $table->string('name');
            $table->string('issuing_entity');
            $table->string('entity_logo')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district')->nullable();
            $table->string('category', 40)->default('lands')->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('property_number')->index();
            $table->string('name');
            $table->string('type', 40)->default('residential_land')->index();
            $table->string('usage_classification', 40)->nullable()->default('residential')->index();
            $table->text('short_description')->nullable();
            $table->string('city', 100);
            $table->string('district', 100)->nullable();
            $table->string('administrative_region', 100)->nullable();
            $table->string('plan_number', 100)->nullable();
            $table->string('parcel_number', 100)->nullable();
            $table->text('google_maps_url')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('boundary_geojson')->nullable();
            $table->string('aerial_image')->nullable();
            $table->decimal('area', 12, 2)->default(0);
            $table->string('deed_number', 100)->nullable()->index();
            $table->date('deed_date')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->string('ownership_type', 40)->nullable()->default('sole_ownership');
            $table->string('agent_name')->nullable();
            $table->string('agent_national_id', 100)->nullable();
            $table->string('agency_number', 100)->nullable();
            $table->date('agency_date')->nullable();
            $table->decimal('estimated_value', 15, 2)->default(0);
            $table->string('data_status', 40)->default('incomplete')->index();
            $table->string('status', 40)->default('available')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('main_image')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['assignment_id', 'property_number']);
        });

        Schema::create('auctions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->time('start_time')->nullable();
            $table->date('end_date');
            $table->time('end_time')->nullable();
            $table->string('type', 30)->default('electronic')->index();
            $table->string('category', 30)->default('lands')->index();
            $table->string('city', 100);
            $table->unsignedInteger('expected_properties_count')->default(1);
            $table->string('supervising_entity')->nullable();
            $table->string('platform_name')->nullable();
            $table->string('platform_url')->nullable();
            $table->text('notes')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('template_versions')->nullable();
            $table->json('template_snapshot')->nullable();
            $table->json('template_bindings')->nullable();
            $table->string('status', 30)->default('in_preparation')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('property_boundaries', function (Blueprint $table): void {
            $table->id(); $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 30)->index(); $table->string('border_type', 50)->nullable();
            $table->string('border_description')->nullable(); $table->decimal('length', 10, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0); $table->timestamps();
        });
        Schema::create('property_owners', function (Blueprint $table): void {
            $table->id(); $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('owner_name'); $table->string('national_id_or_cr', 100);
            $table->unsignedSmallInteger('sort_order')->default(0); $table->timestamps();
        });
        Schema::create('property_units', function (Blueprint $table): void {
            $table->id(); $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('unit_number', 100); $table->string('unit_type', 40)->nullable();
            $table->decimal('area', 10, 2)->default(0); $table->string('current_use', 40)->nullable();
            $table->string('status', 40)->nullable(); $table->unsignedSmallInteger('sort_order')->default(0); $table->timestamps();
        });
        Schema::create('auction_properties', function (Blueprint $table): void {
            $table->id(); $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0); $table->timestamps();
            $table->unique(['auction_id', 'property_id']);
        });
        Schema::create('wizard_drafts', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->string('guard', 20); $table->unsignedBigInteger('user_id');
            $table->string('resource_key'); $table->unsignedTinyInteger('last_step')->default(1);
            $table->json('draft_data')->nullable(); $table->json('draft_files')->nullable(); $table->timestamps();
            $table->unique(['guard', 'user_id', 'resource_key']);
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id(); $table->string('guard', 20); $table->string('name'); $table->string('display_name'); $table->timestamps(); $table->unique(['guard', 'name']);
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id(); $table->string('guard', 20); $table->string('name'); $table->string('display_name'); $table->timestamps(); $table->unique(['guard', 'name']);
        });
        Schema::create('permission_role', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete(); $table->foreignId('permission_id')->constrained()->cascadeOnDelete(); $table->primary(['role_id', 'permission_id']);
        });
        Schema::create('role_user', function (Blueprint $table): void {
            $table->string('guard', 20); $table->unsignedBigInteger('user_id'); $table->foreignId('role_id')->constrained()->cascadeOnDelete(); $table->primary(['guard', 'user_id', 'role_id']);
        });
        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary(); $table->string('token'); $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens'); Schema::dropIfExists('role_user'); Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions'); Schema::dropIfExists('roles'); Schema::dropIfExists('wizard_drafts');
        Schema::dropIfExists('auction_properties'); Schema::dropIfExists('property_units'); Schema::dropIfExists('property_owners');
        Schema::dropIfExists('property_boundaries'); Schema::dropIfExists('auctions'); Schema::dropIfExists('properties');
        Schema::dropIfExists('assignments'); Schema::dropIfExists('users');
    }
};
