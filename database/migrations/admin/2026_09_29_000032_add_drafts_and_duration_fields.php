<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->enum('subscription_duration_type', ['limited', 'unlimited'])->default('limited')->after('included_users');
            $table->enum('subscription_billing_cycle', ['monthly', 'yearly'])->nullable()->after('subscription_duration_type');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->enum('status', ['draft', 'pending', 'active'])->default('pending')->after('address');
            $table->unsignedTinyInteger('draft_step')->nullable()->after('status');
            $table->json('draft_data')->nullable()->after('draft_step');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('ends_at')->nullable()->after('starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('ends_at');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['status', 'draft_step', 'draft_data']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['subscription_duration_type', 'subscription_billing_cycle']);
        });
    }
};
