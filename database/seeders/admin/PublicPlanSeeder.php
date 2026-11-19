<?php

declare(strict_types=1);

namespace Database\Seeders\admin;

use App\Enums\Admin\Plans\PlanColor;
use App\Enums\Admin\Plans\PlanLimitsMode;
use App\Enums\Admin\Plans\PlanType;
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

        // Clean up legacy placeholder test plan if it has no subscriptions
        $legacy = Plan::query()->where('code', 'public_test')->first();
        if ($legacy && ! $legacy->subscriptions()->exists()) {
            $legacy->forceDelete();
        }

        $plans = [
            [
                'code' => 'PLAN_BASIC',
                'name' => 'باقة الانطلاقة',
                'description' => 'مثالية للوسطاء والمكاتب العقارية المستقلة لإدارة المزادات والإسنادات بكفاءة ومرونة.',
                'price' => 299.00,
                'yearly_price' => 2990.00,
                'extra_user_license_price' => 50.00,
                'discount_percentage' => 0,
                'plan_type' => PlanType::BASIC->value,
                'color' => PlanColor::BLUE->value,
                'limits_mode' => PlanLimitsMode::LIMITED->value,
                'included_users' => 3,
                'properties_limit' => 25,
                'storage_quota_gb' => 10,
                'active_auctions_limit' => 3,
                'subscription_duration_type' => 'limited',
                'is_active' => true,
                'sort_order' => 1,
                'available_from' => now()->subDay(),
                'created_by' => $adminId,
                'features' => [
                    'إدارة حتى 25 عقاراً مع الوثائق والمخططات',
                    'إقامة حتى 3 مزادات نشطة في نفس الوقت',
                    '3 مستخدمين للنظام مشمولين مجاناً',
                    'سعة تخزين سحابية 10 جيجابايت للمستندات والوسائط',
                    'إدارة ملفات الإسنادات والجهات القضائية المشرفة',
                    'تقارير المزادات والإغلاقات الأساسية',
                    'دعم فني عبر البريد الإلكتروني ونظام التذاكر',
                ],
            ],
            [
                'code' => 'PLAN_PRO',
                'name' => 'باقة الأعمال',
                'description' => 'الخيار الأكثر طلباً للشركات ومكاتب المزادات المتنامية لإدارة متكاملة وحملات تسويقية متقدمة.',
                'price' => 599.00,
                'yearly_price' => 5990.00,
                'extra_user_license_price' => 75.00,
                'discount_percentage' => 15,
                'plan_type' => PlanType::PROFESSIONAL->value,
                'color' => PlanColor::PRIMARY->value,
                'limits_mode' => PlanLimitsMode::LIMITED->value,
                'included_users' => 7,
                'properties_limit' => 100,
                'storage_quota_gb' => 30,
                'active_auctions_limit' => 10,
                'subscription_duration_type' => 'limited',
                'is_active' => true,
                'sort_order' => 2,
                'available_from' => now()->subDay(),
                'created_by' => $adminId,
                'features' => [
                    'إدارة حتى 100 عقار بمواصفات وصور ومواقع جغرافية',
                    'إقامة حتى 10 مزادات نشطة في نفس الوقت',
                    '7 مستخدمين للنظام مع إدارة دقيقة للأدوار والصلاحيات',
                    'سعة تخزين سحابية 30 جيجابايت للمستندات والكتيبات',
                    'إدارة الحملات التسويقية المتعددة وشبكة الوسطاء المعتمدين',
                    'توليد تقارير المزادات الرسمية التلقائية ومحاضر الترسية',
                    'لوحة تحليلات تفاعلية وإحصائيات مالية وتشغيلية',
                    'دعم فني ذو أولوية مع استجابة سريعة',
                ],
            ],
            [
                'code' => 'PLAN_ENTERPRISE',
                'name' => 'باقة المؤسسات',
                'description' => 'حل شامل وغير محدود لبيوت الخبرة والشركات الكبرى مع نطاق مخصص وإمكانات تشغيلية كاملة.',
                'price' => 999.00,
                'yearly_price' => 9990.00,
                'extra_user_license_price' => 100.00,
                'discount_percentage' => 20,
                'plan_type' => PlanType::ENTERPRISE->value,
                'color' => PlanColor::SECONDARY->value,
                'limits_mode' => PlanLimitsMode::UNLIMITED->value,
                'included_users' => 15,
                'properties_limit' => null,
                'storage_quota_gb' => null,
                'active_auctions_limit' => null,
                'subscription_duration_type' => 'limited',
                'is_active' => true,
                'sort_order' => 3,
                'available_from' => now()->subDay(),
                'created_by' => $adminId,
                'features' => [
                    'إدارة عقارات وإسنادات غير محدودة بدون أي سقف (∞)',
                    'مزادات نشطة غير محدودة في كافة المناطق (∞)',
                    '15 مستخدماً مشمولاً مع إمكانية إضافة تراخيص غير محدودة',
                    'سعة تخزين سحابية غير محدودة لكافة المستندات والملفات (∞)',
                    'إمكانية ربط نطاق مخصص للشركة (Custom Domain)',
                    'إدارة متكاملة لطلبات إقامة المزادات والإقفال المالي النهائي',
                    'نظام تنبيهات ورسائل إشعار مباشرة للمزايدين والوسطاء',
                    'مدير حساب مخصص ودعم فني متواصل على مدار الساعة 24/7',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['code' => $planData['code']],
                $planData
            );
        }
    }
}
