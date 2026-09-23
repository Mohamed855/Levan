<?php

namespace App\Http\Controllers\Admin\Dashboard;


use App\Http\Controllers\Controller;
use App\Services\Admin\Dashboard\DashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function dashboard()
    {
        // Live production data
        $dashboardService = new DashboardService();
        $data = $dashboardService->getDashboardData();

        /*
        |--------------------------------------------------------------------------
        | Demo Data Toggle (for presentations / screenshots)
        |--------------------------------------------------------------------------
        | Uncomment the line below to populate the dashboard with realistic
        | mock metrics, charts, and tables without affecting the database.
        */
        // $data = $this->getDemoDashboardData();

        return view('admin.dashboard.index', $data);
    }

    /**
     * Demo Dashboard Data Provider
     *
     * Provides comprehensive mock datasets (KPIs, charts, upcoming renewals,
     * subscriptions, and domains) for presentation and screenshot purposes.
     * To activate, uncomment `$data = $this->getDemoDashboardData();` in dashboard().
     */
    private function getDemoDashboardData(): array
    {
        $months = [
            'أكتوبر 2023', 'نوفمبر 2023', 'ديسمبر 2023',
            'يناير 2024', 'فبراير 2024', 'مارس 2024',
            'أبريل 2024', 'مايو 2024', 'يونيو 2024',
            'يوليو 2024', 'أغسطس 2024', 'سبتمبر 2024'
        ];

        $customerCounts = [14, 19, 25, 32, 41, 53, 67, 82, 98, 115, 131, 142];
        $revenueCounts  = [42000, 56000, 71000, 89000, 104000, 122000, 138000, 151000, 163000, 172000, 184500, 196000];

        $monthlyCustomers = collect();
        $monthlyRevenue = collect();

        foreach ($months as $idx => $m) {
            $monthlyCustomers->push([
                'month' => $m,
                'count' => $customerCounts[$idx]
            ]);
            $monthlyRevenue->push([
                'month'   => $m,
                'revenue' => $revenueCounts[$idx]
            ]);
        }

        return [
            // KPIs
            'kpis' => [
                'total_tenants'          => 142,
                'active_subscriptions'   => 128,
                'mrr'                    => 184500,
                'total_users'            => 1450,
                'new_tenants_this_month' => 11,
                'expiring_soon'          => 4,
                'active_coupons'         => 8,
                'revenue_this_month'     => 196000,
            ],

            // Charts Data
            'monthlyCustomers'    => $monthlyCustomers,
            'topCustomersByUsers' => collect([
                ['name' => 'شركة التقنية المتقدمة للحلول الرقمية', 'users' => 195],
                ['name' => 'مؤسسة الأفق للاستشارات الإدارية', 'users' => 160],
                ['name' => 'شركة الرواد للتجارة والمقاولات', 'users' => 142],
                ['name' => 'مجموعة النور القابضة', 'users' => 118],
                ['name' => 'شركة إعمار المستقبل للتطوير', 'users' => 95],
                ['name' => 'شركة الحلول الذكية المحدودة', 'users' => 84],
                ['name' => 'مؤسسة البناء الحديث للمقاولات', 'users' => 76],
                ['name' => 'شركة المدى للخدمات اللوجستية', 'users' => 65],
                ['name' => 'شركة الصفا للأغذية والتوريدات', 'users' => 58],
                ['name' => 'مجموعة الريادة العالمية', 'users' => 45],
            ]),
            'monthlyRevenue'      => $monthlyRevenue,
            'subscriptionStatus'  => [
                ['status' => 'نشط', 'count' => 128],
                ['status' => 'معلق', 'count' => 8],
                ['status' => 'ملغي', 'count' => 3],
                ['status' => 'منتهي', 'count' => 14],
            ],
            'userUsage'           => collect([
                ['subscription' => 'SUB-2024-001', 'total' => 200, 'used' => 195, 'usage_percentage' => 97.5],
                ['subscription' => 'SUB-2024-015', 'total' => 180, 'used' => 160, 'usage_percentage' => 88.8],
                ['subscription' => 'SUB-2024-023', 'total' => 150, 'used' => 142, 'usage_percentage' => 94.6],
                ['subscription' => 'SUB-2024-042', 'total' => 130, 'used' => 118, 'usage_percentage' => 90.7],
                ['subscription' => 'SUB-2024-055', 'total' => 100, 'used' => 95,  'usage_percentage' => 95.0],
                ['subscription' => 'SUB-2024-068', 'total' => 100, 'used' => 84,  'usage_percentage' => 84.0],
                ['subscription' => 'SUB-2024-071', 'total' => 80,  'used' => 76,  'usage_percentage' => 95.0],
                ['subscription' => 'SUB-2024-089', 'total' => 75,  'used' => 65,  'usage_percentage' => 86.6],
                ['subscription' => 'SUB-2024-094', 'total' => 60,  'used' => 58,  'usage_percentage' => 96.6],
                ['subscription' => 'SUB-2024-102', 'total' => 50,  'used' => 45,  'usage_percentage' => 90.0],
            ]),
            'couponUsage'         => collect([
                ['code' => 'LAUNCH2024', 'name' => 'خصم التدشين 25%', 'used' => 48, 'limit' => 100],
                ['code' => 'FOUNDER50',  'name' => 'عرض الرواد 50%',   'used' => 30, 'limit' => 30],
                ['code' => 'SUMMER20',   'name' => 'عرض الصيف 20%',    'used' => 26, 'limit' => 50],
                ['code' => 'BUSINESS15', 'name' => 'باقة الأعمال 15%', 'used' => 22, 'limit' => 'غير محدود'],
                ['code' => 'ENTERPRISE', 'name' => 'خصم المؤسسات 10%','used' => 19, 'limit' => 50],
                ['code' => 'RAMADAN30',  'name' => 'عرض رمضان المبارك', 'used' => 15, 'limit' => 50],
                ['code' => 'RENEWAL10',  'name' => 'خصم التجديد السنوي','used' => 12, 'limit' => 'غير محدود'],
                ['code' => 'PARTNER2024','name' => 'كوبون الشركاء',    'used' => 9,  'limit' => 20],
            ]),
            'domainsByExtension'  => collect([
                ['extension' => '.sa',     'count' => 54],
                ['extension' => '.com',    'count' => 42],
                ['extension' => '.com.sa', 'count' => 18],
                ['extension' => '.net',    'count' => 12],
                ['extension' => '.org',    'count' => 7],
            ]),

            // Tables Data
            'upcomingRenewals'    => collect([
                ['tenant' => 'شركة التقنية المتقدمة للحلول الرقمية', 'subscription' => 'SUB-2024-001', 'amount' => '4,850.00', 'renewal_date' => now()->addDays(3)->format('Y-m-d'), 'days_until' => 3],
                ['tenant' => 'مؤسسة الأفق للاستشارات الإدارية',     'subscription' => 'SUB-2024-015', 'amount' => '3,600.00', 'renewal_date' => now()->addDays(6)->format('Y-m-d'), 'days_until' => 6],
                ['tenant' => 'شركة الرواد للتجارة والمقاولات',      'subscription' => 'SUB-2024-023', 'amount' => '3,200.00', 'renewal_date' => now()->addDays(11)->format('Y-m-d'), 'days_until' => 11],
                ['tenant' => 'مجموعة النور القابضة',                'subscription' => 'SUB-2024-042', 'amount' => '5,500.00', 'renewal_date' => now()->addDays(15)->format('Y-m-d'), 'days_until' => 15],
                ['tenant' => 'شركة الحلول الذكية المحدودة',         'subscription' => 'SUB-2024-055', 'amount' => '2,400.00', 'renewal_date' => now()->addDays(22)->format('Y-m-d'), 'days_until' => 22],
            ]),
            'recentSubscriptions' => collect([
                ['tenant' => 'شركة المدى للخدمات اللوجستية', 'subscription' => 'SUB-2024-142', 'plan' => 'الباقة الاحترافية (سنوي)', 'price' => '8,500.00', 'status' => 'active',    'created_at' => now()->subDay()->format('Y-m-d')],
                ['tenant' => 'مؤسسة القمة للتقنية',          'subscription' => 'SUB-2024-141', 'plan' => 'باقة الأعمال المتقدمة',   'price' => '4,200.00', 'status' => 'active',    'created_at' => now()->subDays(2)->format('Y-m-d')],
                ['tenant' => 'شركة المسار السريع للشحن',    'subscription' => 'SUB-2024-140', 'plan' => 'الباقة الأساسية',        'price' => '1,800.00', 'status' => 'active',    'created_at' => now()->subDays(3)->format('Y-m-d')],
                ['tenant' => 'مجموعة البركة الاستثمارية',    'subscription' => 'SUB-2024-139', 'plan' => 'باقة المؤسسات والشركات', 'price' => '14,000.00','status' => 'active',    'created_at' => now()->subDays(4)->format('Y-m-d')],
                ['tenant' => 'مكتب الدقة للحلول المالية',    'subscription' => 'SUB-2024-138', 'plan' => 'باقة الأعمال',           'price' => '3,600.00', 'status' => 'suspended', 'created_at' => now()->subDays(6)->format('Y-m-d')],
            ]),
            'expiringSubscriptions' => collect([
                ['tenant' => 'شركة الرؤية الحديثة للتطوير', 'subscription' => 'SUB-2024-088', 'expires_at' => now()->addDays(2)->format('Y-m-d'),  'days_left' => 2],
                ['tenant' => 'مؤسسة الصرح للتجارة العامة',  'subscription' => 'SUB-2024-076', 'expires_at' => now()->addDays(5)->format('Y-m-d'),  'days_left' => 5],
                ['tenant' => 'شركة التميز الدولية',         'subscription' => 'SUB-2024-063', 'expires_at' => now()->addDays(8)->format('Y-m-d'),  'days_left' => 8],
                ['tenant' => 'وكالة الإبداع الرقمي للدعاية', 'subscription' => 'SUB-2024-051', 'expires_at' => now()->addDays(14)->format('Y-m-d'), 'days_left' => 14],
            ]),
            'controlDomains'      => collect([
                ['extension' => '.sa',     'price' => '120.00', 'status' => 'نشط'],
                ['extension' => '.com.sa', 'price' => '100.00', 'status' => 'نشط'],
                ['extension' => '.com',    'price' => '65.00',  'status' => 'نشط'],
                ['extension' => '.net',    'price' => '70.00',  'status' => 'نشط'],
                ['extension' => '.org',    'price' => '75.00',  'status' => 'نشط'],
            ]),
            'recentDomains'       => collect([
                ['domain' => 'tech-lead.sa',      'tenant' => 'شركة التقنية المتقدمة', 'type' => 'مسجل', 'is_active' => true, 'created_at' => now()->subDay()->format('Y-m-d')],
                ['domain' => 'alofooq-group.com', 'tenant' => 'مؤسسة الأفق للاستشارات','type' => 'مربوط', 'is_active' => true, 'created_at' => now()->subDays(2)->format('Y-m-d')],
                ['domain' => 'al-rowad.sa',       'tenant' => 'شركة الرواد للتجارة',   'type' => 'مسجل', 'is_active' => true, 'created_at' => now()->subDays(4)->format('Y-m-d')],
                ['domain' => 'alnoor-sa.net',     'tenant' => 'مجموعة النور القابضة',  'type' => 'مربوط', 'is_active' => true, 'created_at' => now()->subDays(5)->format('Y-m-d')],
                ['domain' => 'smart-sol.com.sa',  'tenant' => 'شركة الحلول الذكية',    'type' => 'مسجل', 'is_active' => true, 'created_at' => now()->subDays(7)->format('Y-m-d')],
            ]),

            // Growth Metrics
            'growthRate'          => [
                'current_month' => 14,
                'last_month'    => 11,
                'growth_rate'   => 27.27,
                'trend'         => 'up',
            ],
        ];
    }
}
