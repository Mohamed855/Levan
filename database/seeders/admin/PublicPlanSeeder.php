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
                'name'                       => 'Public Test Plan',
                'description'                => 'Public plan for testing the subscription checkout flow.',
                'price'                      => 100,
                'yearly_price'               => 1000,
                'extra_user_license_price'   => 25,
                'discount_percentage'        => 0,
                'plan_type'                  => 'basic',
                'limits_mode'               => 'limited',
                'included_users'             => 3,
                'properties_limit'           => 25,
                'storage_quota_gb'           => 5,
                'active_auctions_limit'      => 5,
                'subscription_duration_type' => 'limited',
                'is_active'                  => true,
                'sort_order'                 => 1,
                'available_from'             => now()->subMinute(),
                'created_by'                 => $adminId,
                'features'                   => [
                    'إدارة حتى 25 عقاراً',
                    'حتى 5 مزادات نشطة في وقت واحد',
                    '3 مستخدمين مشمولين',
                    '5 جيجابايت تخزين',
                    'تقارير المزادات الأساسية',
                    'دعم فني عبر البريد الإلكتروني',
                    'إدارة الإسنادات والجهات المسندة',
                ],
            ],
        );
    }
}
