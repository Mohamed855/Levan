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
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'code')) {
                $table->string('code', 50)->nullable()->after('name');
            }
            if (!Schema::hasColumn('plans', 'plan_type')) {
                $table->string('plan_type', 50)->default('basic')->after('code');
            }
            if (!Schema::hasColumn('plans', 'color')) {
                $table->string('color', 30)->default('purple')->after('plan_type');
            }
            if (!Schema::hasColumn('plans', 'available_from')) {
                $table->timestamp('available_from')->nullable()->after('color');
            }
            if (!Schema::hasColumn('plans', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('available_from');
            }
            if (!Schema::hasColumn('plans', 'limits_mode')) {
                $table->string('limits_mode', 20)->default('limited')->after('admin_notes');
            }
            $table->unsignedInteger('included_users')->nullable()->change();
        });

        // Expand subscription_billing_cycle ENUM to include 'quarterly'
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE plans MODIFY COLUMN subscription_billing_cycle ENUM('monthly', 'quarterly', 'yearly') NULL");
        }

        // Backfill existing rows with consistent, unique uppercase code
        DB::table('plans')->orderBy('id')->each(function ($plan) {
            $updates = [];
            if (empty($plan->code)) {
                $updates['code'] = 'PLAN-' . $plan->id;
            }
            if (empty($plan->plan_type)) {
                $updates['plan_type'] = 'basic';
            }
            if (empty($plan->color)) {
                $updates['color'] = 'purple';
            }
            if (empty($plan->limits_mode)) {
                $updates['limits_mode'] = 'limited';
            }
            if (!empty($updates)) {
                DB::table('plans')->where('id', $plan->id)->update($updates);
            }
        });

        // Add UNIQUE constraint on code now that every row has a unique code
        Schema::table('plans', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn([
                'code',
                'plan_type',
                'color',
                'available_from',
                'admin_notes',
                'limits_mode',
            ]);
            $table->unsignedInteger('included_users')->default(1)->nullable(false)->change();
        });

        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE plans MODIFY COLUMN subscription_billing_cycle ENUM('monthly', 'yearly') NULL");
        }
    }
};
