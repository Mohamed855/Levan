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
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'billing_cycle')) {
                $table->string('billing_cycle', 20)->default('monthly')->after('plan_id');
            }
        });

        Schema::table('subscription_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_periods', 'plan_code_snapshot')) {
                $table->string('plan_code_snapshot', 50)->nullable()->after('plan_id_snapshot');
            }
            if (!Schema::hasColumn('subscription_periods', 'plan_name_snapshot')) {
                $table->string('plan_name_snapshot')->nullable()->after('plan_code_snapshot');
            }
            if (!Schema::hasColumn('subscription_periods', 'plan_type_snapshot')) {
                $table->string('plan_type_snapshot', 50)->nullable()->after('plan_name_snapshot');
            }
            if (!Schema::hasColumn('subscription_periods', 'billing_cycle_snapshot')) {
                $table->string('billing_cycle_snapshot', 20)->nullable()->after('plan_type_snapshot');
            }
            if (!Schema::hasColumn('subscription_periods', 'limits_mode_snapshot')) {
                $table->string('limits_mode_snapshot', 20)->default('limited')->after('billing_cycle_snapshot');
            }
            if (!Schema::hasColumn('subscription_periods', 'limits_snapshot')) {
                $table->json('limits_snapshot')->nullable()->after('limits_mode_snapshot');
            }
            if (!Schema::hasColumn('subscription_periods', 'features_snapshot')) {
                $table->json('features_snapshot')->nullable()->after('limits_snapshot');
            }
        });

        // Backfill existing subscriptions billing_cycle
        DB::table('subscriptions')->orderBy('id')->each(function ($sub) {
            $plan = null;
            if ($sub->plan_id) {
                $plan = DB::table('plans')->where('id', $sub->plan_id)->first();
            }
            $cycle = $plan?->subscription_billing_cycle ?: 'monthly';
            DB::table('subscriptions')->where('id', $sub->id)->update([
                'billing_cycle' => $cycle,
            ]);
        });

        // Backfill existing subscription_periods snapshots
        DB::table('subscription_periods')->orderBy('id')->each(function ($period) {
            $planId = $period->plan_id_snapshot;
            if (!$planId && $period->subscription_id) {
                $sub = DB::table('subscriptions')->where('id', $period->subscription_id)->first();
                $planId = $sub?->plan_id;
            }

            $plan = $planId ? DB::table('plans')->where('id', $planId)->first() : null;

            $limitsMode = $plan?->limits_mode ?: 'limited';
            $features = $plan && !empty($plan->features) ? (is_array(json_decode($plan->features, true)) ? json_decode($plan->features, true) : []) : [];

            $limitsSnapshot = $limitsMode === 'unlimited' ? null : [
                'included_users'        => (int) ($period->total_users_snapshot ?: ($plan?->included_users ?: 1)),
                'properties_limit'      => (int) ($plan?->properties_limit ?: 50),
                'storage_quota_gb'      => (int) ($plan?->storage_quota_gb ?: 10),
                'active_auctions_limit' => (int) ($plan?->active_auctions_limit ?: 10),
                'branches_limit'        => (int) ($plan?->branches_limit ?: 1),
                'unlimited_branches'    => (bool) ($plan?->unlimited_branches ?? false),
            ];

            DB::table('subscription_periods')->where('id', $period->id)->update([
                'plan_code_snapshot'     => $plan?->code ?: ('PLAN-' . ($plan?->id ?: 1)),
                'plan_name_snapshot'     => $plan?->name ?: 'Standard Plan',
                'plan_type_snapshot'     => $plan?->plan_type ?: 'basic',
                'billing_cycle_snapshot' => $plan?->subscription_billing_cycle ?: 'monthly',
                'limits_mode_snapshot'   => $limitsMode,
                'limits_snapshot'        => $limitsSnapshot ? json_encode($limitsSnapshot) : null,
                'features_snapshot'      => json_encode($features),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('subscription_periods', function (Blueprint $table) {
            $table->dropColumn([
                'plan_code_snapshot',
                'plan_name_snapshot',
                'plan_type_snapshot',
                'billing_cycle_snapshot',
                'limits_mode_snapshot',
                'limits_snapshot',
                'features_snapshot',
            ]);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['billing_cycle']);
        });
    }
};
