<?php

declare(strict_types=1);

namespace Database\Seeders\admin;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Plans\Plan;
use Illuminate\Database\Seeder;

final class PublicPlanSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = AdminUser::query()->value('id');

        if (! $adminId) {
            return;
        }

        Plan::updateOrCreate(
            ['code' => 'public_test'],
            [
                'name' => 'Public Test Plan',
                'description' => 'Public plan for testing the subscription checkout flow.',
                'price' => 100,
                'yearly_renewal_price' => 100,
                'extra_user_license_price' => 25,
                'discount_percentage' => 0,
                'plan_type' => 'basic',
                'limits_mode' => 'limited',
                'included_users' => 3,
                'properties_limit' => 25,
                'storage_quota_gb' => 5,
                'active_auctions_limit' => 5,
                'subscription_duration_type' => 'limited',
                'subscription_billing_cycle' => 'monthly',
                'is_active' => true,
                'sort_order' => 1,
                'available_from' => now()->subMinute(),
                'created_by' => $adminId,
            ],
        );
    }
}
