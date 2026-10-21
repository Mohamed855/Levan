<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Admin\City;
use App\Models\Admin\Faq;
use App\Models\Admin\Plans\Plan;
use Illuminate\Database\Seeder;

class WebSiteSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['الرياض', 'جدة', 'الدمام', 'مكة المكرمة', 'المدينة المنورة'] as $name) {
            City::firstOrCreate(['name' => $name]);
        }

        Plan::updateOrCreate(['code' => 'starter'], [
            'name' => 'باقة البداية', 'plan_type' => 'basic', 'color' => 'purple',
            'description' => 'أساسيات إدارة عمليات التحصيل لفريقك.', 'price' => 299,
            'discount_percentage' => 0, 'limits_mode' => 'limited', 'included_users' => 3,
            'properties_limit' => 100, 'storage_quota_gb' => 10,
            'features' => ['إدارة العملاء', 'لوحة متابعة التحصيل', 'تقارير أساسية'],
            'is_active' => true, 'sort_order' => 1, 'subscription_billing_cycle' => 'monthly',
        ]);

        Plan::updateOrCreate(['code' => 'professional'], [
            'name' => 'باقة الاحتراف', 'plan_type' => 'professional', 'color' => 'blue',
            'description' => 'إدارة متقدمة للفرق والعمليات المتنامية.', 'price' => 699,
            'discount_percentage' => 10, 'limits_mode' => 'limited', 'included_users' => 10,
            'properties_limit' => 1000, 'storage_quota_gb' => 50,
            'features' => ['كل مزايا البداية', 'تقارير متقدمة', 'دعم ذو أولوية'],
            'is_active' => true, 'sort_order' => 2, 'subscription_billing_cycle' => 'monthly',
        ]);

        Faq::updateOrCreate(['question' => 'هل يمكن تجربة المنصة قبل الاشتراك؟'], [
            'answer' => 'نعم، يمكنك إرسال طلب تجربة مجانية وسيتواصل معك الفريق لتفعيلها.', 'is_active' => true,
        ]);
        Faq::updateOrCreate(['question' => 'هل يمكن تغيير الباقة لاحقاً؟'], [
            'answer' => 'نعم، يمكن تغيير الباقة وفق احتياجات منشأتك عند اكتمال منظومة الاشتراكات.', 'is_active' => true,
        ]);
    }
}
