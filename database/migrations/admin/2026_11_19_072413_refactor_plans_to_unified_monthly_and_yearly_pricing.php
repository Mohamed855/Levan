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
            if (! Schema::hasColumn('plans', 'yearly_price')) {
                $table->decimal('yearly_price', 10, 2)->nullable()->after('price');
            }
        });

        if (Schema::hasColumn('plans', 'subscription_billing_cycle')) {
            // Migrate existing yearly plans so their price is converted to monthly price, and yearly_price preserves the yearly amount
            DB::connection('mysql')->table('plans')
                ->where('subscription_billing_cycle', 'yearly')
                ->where(function ($query) {
                    $query->whereNull('code')
                        ->orWhere('code', '!=', 'trial_7_days');
                })
                ->get()
                ->each(function ($plan) {
                    $originalPrice = (float) $plan->price;
                    $monthlyEquivalent = $originalPrice > 0 ? round($originalPrice / 12, 2) : 0.00;

                    DB::connection('mysql')->table('plans')
                        ->where('id', $plan->id)
                        ->update([
                            'yearly_price' => $originalPrice,
                            'price' => $monthlyEquivalent,
                        ]);
                });

            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('subscription_billing_cycle');
            });
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'subscription_billing_cycle')) {
                $table->enum('subscription_billing_cycle', ['monthly', 'yearly'])->nullable()->after('subscription_duration_type');
            }
        });

        if (Schema::hasColumn('plans', 'yearly_price')) {
            DB::connection('mysql')->table('plans')
                ->whereNotNull('yearly_price')
                ->where(function ($query) {
                    $query->whereNull('code')
                        ->orWhere('code', '!=', 'trial_7_days');
                })
                ->get()
                ->each(function ($plan) {
                    DB::connection('mysql')->table('plans')
                        ->where('id', $plan->id)
                        ->update([
                            'price' => $plan->yearly_price,
                            'subscription_billing_cycle' => 'yearly',
                        ]);
                });

            DB::connection('mysql')->table('plans')
                ->whereNull('subscription_billing_cycle')
                ->update(['subscription_billing_cycle' => 'monthly']);

            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('yearly_price');
            });
        }
    }
};
