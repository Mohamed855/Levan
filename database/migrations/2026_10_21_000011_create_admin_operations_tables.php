<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('code', 50)->unique();
            $table->string('discount_type', 20); $table->decimal('discount_value', 10, 2);
            $table->decimal('max_discount', 10, 2)->nullable(); $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable(); $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true)->index(); $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('bills', function (Blueprint $table): void {
            $table->id(); $table->string('bill_number')->unique(); $table->string('tenant_id')->nullable()->index();
            $table->string('type', 30); $table->decimal('amount', 10, 2); $table->decimal('settled_amount', 10, 2)->nullable();
            $table->string('payment_method', 30)->nullable(); $table->string('status', 30)->default('pending')->index();
            $table->timestamp('due_at')->nullable(); $table->timestamp('paid_at')->nullable(); $table->json('metadata')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id(); $table->string('ticket_number')->unique(); $table->string('tenant_id')->nullable()->index();
            $table->string('subject'); $table->text('description'); $table->string('department', 50)->nullable();
            $table->string('status', 30)->default('open')->index(); $table->boolean('service_enabled')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('ticket_replies', function (Blueprint $table): void { $table->id(); $table->foreignId('ticket_id')->constrained()->cascadeOnDelete(); $table->foreignId('admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete(); $table->text('message'); $table->timestamps(); });
        Schema::create('banks', function (Blueprint $table): void { $table->id(); $table->string('name'); $table->string('account_name'); $table->string('iban', 34)->unique(); $table->string('account_number')->nullable(); $table->string('logo_path')->nullable(); $table->boolean('is_active')->default(true)->index(); $table->timestamps(); $table->softDeletes(); });
        Schema::create('control_domains', function (Blueprint $table): void { $table->id(); $table->string('domain')->unique(); $table->boolean('is_active')->default(true)->index(); $table->timestamps(); });
        Schema::create('settings', function (Blueprint $table): void { $table->id(); $table->string('key')->unique(); $table->json('value')->nullable(); $table->timestamps(); });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings'); Schema::dropIfExists('control_domains'); Schema::dropIfExists('banks'); Schema::dropIfExists('ticket_replies'); Schema::dropIfExists('tickets'); Schema::dropIfExists('bills'); Schema::dropIfExists('coupons');
    }
};
