<?php

declare(strict_types=1);

namespace Database\Seeders\admin;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Plans\Plan;
use Illuminate\Database\Seeder;

final class TrialPlanSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = AdminUser::query()->value('id');

        if (! $adminId) {
            return;
        }

        Plan::updateOrCreate(
            ['code' => 'trial_7_days'],
            [
                'name'                       => 'باقة تجريبية',
                'description'                => 'تجربة لمدة 7 أيام',
                'price'                      => 0,
                'extra_user_license_price'   => 0,
                'discount_percentage'        => 0,
                'plan_type'                  => 'basic',
                'limits_mode'               => 'limited',
                'included_users'             => 1,
                'properties_limit'           => 10,
                'storage_quota_gb'           => 1,
                'active_auctions_limit'      => 1,
                'subscription_duration_type' => 'limited',
                'subscription_billing_cycle' => null,
                'is_active'                  => true,
                'sort_order'                 => 0,
                'created_by'                 => $adminId,
                'features'                   => [
                    'إدارة حتى 10 عقارات',
                    'مزاد نشط واحد',
                    'مستخدم واحد',
                    '1 جيجابايت تخزين',
                    'تجربة كاملة لمدة 7 أيام',
                    'دعم فني أساسي',
                ],
            ],
        );
    }
}
