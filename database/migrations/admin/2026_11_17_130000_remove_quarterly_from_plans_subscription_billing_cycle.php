<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update any existing plans that might have had quarterly to monthly before modifying enum
        DB::connection('mysql')->table('plans')
            ->where('subscription_billing_cycle', 'quarterly')
            ->update(['subscription_billing_cycle' => 'monthly']);

        // Alter enum to only monthly and yearly
        DB::connection('mysql')->statement("ALTER TABLE plans MODIFY COLUMN subscription_billing_cycle ENUM('monthly', 'yearly') NULL");
    }

    public function down(): void
    {
        DB::connection('mysql')->statement("ALTER TABLE plans MODIFY COLUMN subscription_billing_cycle ENUM('monthly', 'quarterly', 'yearly') NULL");
    }
};
