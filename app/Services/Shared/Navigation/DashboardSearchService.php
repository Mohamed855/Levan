<?php

declare(strict_types=1);

namespace App\Services\Shared\Navigation;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class DashboardSearchService
{
    /**
     * Get searchable dashboard pages for the current authenticated user and scope.
     *
     * @param  string|null  $scope  'admin' | 'tenant' | null
     * @return array<int, array{name: string, url: string, icon: string, category: string, keywords: string}>
     */
    public function getSearchablePages(?string $scope = null): array
    {
        $scope = $scope ?? $this->resolveScope();

        if ($scope === 'admin') {
            return $this->getAdminPages();
        }

        if ($scope === 'tenant') {
            return $this->getTenantPages();
        }

        return [];
    }

    /**
     * Resolve the active guard/scope.
     */
    public function resolveScope(): ?string
    {
        if (Auth::guard('admin')->check()) {
            return 'admin';
        }

        if (Auth::guard('tenant')->check()) {
            return 'tenant';
        }

        if (function_exists('tenant') && tenant()) {
            return 'tenant';
        }

        return null;
    }

    /**
     * Get searchable pages for central Admin dashboard.
     *
     * @return array<int, array{name: string, url: string, icon: string, category: string, keywords: string}>
     */
    public function getAdminPages(): array
    {
        $definitions = [
            // الرئيسية
            [
                'name' => 'لوحة القيادة',
                'route' => 'admin.dashboard',
                'icon' => 'ti-layout-dashboard',
                'category' => 'الرئيسية',
                'permission' => 'dashboard.main',
                'keywords' => 'الرئيسية dashboard احصائيات نظرة عامة لوحة التحكم',
            ],

            // العملاء
            [
                'name' => 'إدارة العملاء والشركات',
                'route' => 'admin.clients.index',
                'icon' => 'ti-building',
                'category' => 'العملاء والشركات',
                'permission' => ['clients.view', 'companies.view'],
                'keywords' => 'عملاء شركات مستأجرين مؤسسات clients companies tenants',
            ],

            // الباقات والعروض
            [
                'name' => 'إدارة الباقات',
                'route' => 'admin.plans.index',
                'icon' => 'ti-package',
                'category' => 'الباقات والعروض',
                'permission' => 'plans.view',
                'keywords' => 'باقات اشتراكات اسعار خطط plans pricing',
            ],
            [
                'name' => 'إضافة باقة جديدة',
                'route' => 'admin.plans.create',
                'icon' => 'ti-plus',
                'category' => 'الباقات والعروض',
                'permission' => 'plans.create',
                'keywords' => 'انشاء باقة جديدة اضافة باقة create plan',
            ],
            [
                'name' => 'إدارة الكوبونات',
                'route' => 'admin.coupons.index',
                'icon' => 'ti-ticket',
                'category' => 'الباقات والعروض',
                'permission' => 'coupons.view',
                'keywords' => 'كوبونات قسائم خصم تخفيض coupons discounts',
            ],
            [
                'name' => 'إضافة كوبون جديد',
                'route' => 'admin.coupons.create',
                'icon' => 'ti-plus',
                'category' => 'الباقات والعروض',
                'permission' => 'coupons.create',
                'keywords' => 'انشاء كوبون جديد اضافة كوبون create coupon',
            ],

            // الاشتراكات والفواتير
            [
                'name' => 'جميع الاشتراكات',
                'route' => 'admin.subscriptions.index',
                'icon' => 'ti-file-certificate',
                'category' => 'الاشتراكات والفواتير',
                'permission' => 'subscriptions.view',
                'keywords' => 'اشتراكات سارية subscriptions all',
            ],
            [
                'name' => 'اشتراكات تقارب على الانتهاء',
                'route' => 'admin.subscriptionManage.expiring',
                'icon' => 'ti-clock-exclamation',
                'category' => 'الاشتراكات والفواتير',
                'permission' => 'subscriptions.view',
                'keywords' => 'اشتراكات تنتهي قريبا expiring subscriptions',
            ],
            [
                'name' => 'الاشتراكات المنتهية',
                'route' => 'admin.subscriptionManage.expired',
                'icon' => 'ti-calendar-off',
                'category' => 'الاشتراكات والفواتير',
                'permission' => 'subscriptions.view',
                'keywords' => 'اشتراكات منتهية ملغاة expired subscriptions',
            ],
            [
                'name' => 'الفواتير والعمليات المالية',
                'route' => 'admin.bills.index',
                'icon' => 'ti-credit-card',
                'category' => 'الاشتراكات والفواتير',
                'permission' => 'financial_transactions.view',
                'keywords' => 'فواتير مدفوعات مبيعات تحويلات bills invoices payments',
            ],

            // الطلبات والدعم
            [
                'name' => 'طلبات التواصل',
                'route' => 'admin.contact-requests.index',
                'icon' => 'ti-mail',
                'category' => 'الطلبات والدعم',
                'permission' => 'contact_requests.view',
                'keywords' => 'تواصل رسائل اتصل بنا contact requests messages',
            ],
            [
                'name' => 'طلبات التجربة',
                'route' => 'admin.trial-requests.index',
                'icon' => 'ti-clock',
                'category' => 'الطلبات والدعم',
                'permission' => 'trial_requests.view',
                'keywords' => 'طلبات تجربة نسخ تجريبية معاينة trial requests demo',
            ],
            [
                'name' => 'الدعم الفني والتذاكر',
                'route' => 'admin.tickets.index',
                'icon' => 'ti-headset',
                'category' => 'الطلبات والدعم',
                'permission' => 'tickets.view',
                'keywords' => 'تذاكر دعم فني شكاوى ومقترحات tickets support help',
            ],

            // إدارة النظام
            [
                'name' => 'إدارة المستخدمين',
                'route' => 'admin.users.index',
                'icon' => 'ti-users',
                'category' => 'إدارة النظام',
                'permission' => 'users.view',
                'keywords' => 'مستخدمين مديرين مشرفين ادارة users admins staff',
            ],
            [
                'name' => 'إضافة مستخدم جديد',
                'route' => 'admin.users.create',
                'icon' => 'ti-user-plus',
                'category' => 'إدارة النظام',
                'permission' => 'users.create',
                'keywords' => 'اضافة مستخدم جديد انشاء مشرف add user admin',
            ],
            [
                'name' => 'الأدوار والصلاحيات',
                'route' => 'admin.roles.index',
                'icon' => 'ti-shield-lock',
                'category' => 'إدارة النظام',
                'permission' => 'roles.view',
                'keywords' => 'ادوار صلاحيات اذن رتب roles permissions',
            ],
            [
                'name' => 'سجل العمليات والنشاطات',
                'route' => 'admin.activity-logs.index',
                'icon' => 'ti-history',
                'category' => 'إدارة النظام',
                'permission' => ['activity_logs.view', 'activity_log.view'],
                'keywords' => 'سجل نشاطات عمليات تدقيق حركات logs audit activity',
            ],
            [
                'name' => 'الأسئلة الشائعة',
                'route' => 'admin.faqs.index',
                'icon' => 'ti-help',
                'category' => 'إدارة النظام',
                'permission' => 'faqs.view',
                'keywords' => 'اسئلة شائعة faq questions',
            ],
            [
                'name' => 'الحسابات البنكية',
                'route' => 'admin.settings.banks.index',
                'icon' => 'ti-building-bank',
                'category' => 'إدارة النظام',
                'permission' => ['banks.view', 'bank_settings.view'],
                'keywords' => 'بنوك حسابات بنكية تحويل مصرفي banks bank accounts',
            ],
            [
                'name' => 'إعدادات النظام',
                'route' => 'admin.settings.index',
                'icon' => 'ti-settings',
                'category' => 'إدارة النظام',
                'permission' => ['system_settings.view', 'settings.view'],
                'keywords' => 'اعدادات ضبط عام موقع نظام settings configuration',
            ],
            [
                'name' => 'قوالب الإعلانات',
                'route' => 'admin.auction-templates.index',
                'icon' => 'ti-template',
                'category' => 'القوالب',
                'permission' => 'auction_templates.view',
                'keywords' => 'قوالب اعلانات مزادات تصاميم templates',
            ],

            // الأرشيف
            [
                'name' => 'أرشيف المستخدمين',
                'route' => 'admin.archives.index',
                'icon' => 'ti-archive',
                'category' => 'الأرشيف',
                'permission' => 'archive.view',
                'keywords' => 'ارشيف مستخدمين محذوفين users archive deleted',
            ],
            [
                'name' => 'أرشيف العملاء والشركات',
                'route' => 'admin.archives.tenants.index',
                'icon' => 'ti-archive',
                'category' => 'الأرشيف',
                'permission' => 'archive.view',
                'keywords' => 'ارشيف شركات مستأجرين عملاء tenants archive',
            ],
            [
                'name' => 'أرشيف الباقات',
                'route' => 'admin.archives.plans.index',
                'icon' => 'ti-archive',
                'category' => 'الأرشيف',
                'permission' => 'archive.view',
                'keywords' => 'ارشيف باقات محذوفة plans archive',
            ],
            [
                'name' => 'أرشيف الكوبونات',
                'route' => 'admin.archives.coupons.index',
                'icon' => 'ti-archive',
                'category' => 'الأرشيف',
                'permission' => 'archive.view',
                'keywords' => 'ارشيف كوبونات محذوفة coupons archive',
            ],
            [
                'name' => 'أرشيف الفواتير',
                'route' => 'admin.archives.bills.index',
                'icon' => 'ti-archive',
                'category' => 'الأرشيف',
                'permission' => 'archive.view',
                'keywords' => 'ارشيف فواتير محذوفة bills archive',
            ],

            // الحساب الشخصي
            [
                'name' => 'الملف الشخصي',
                'route' => 'admin.profile.show',
                'icon' => 'ti-user',
                'category' => 'الحساب الشخصي',
                'permission' => null,
                'keywords' => 'ملفي الشخصي حسابي كلمة المرور profile account',
            ],
        ];

        return $this->filterPermittedPages($definitions, 'admin');
    }

    /**
     * Get searchable pages for Tenant dashboard.
     *
     * @return array<int, array{name: string, url: string, icon: string, category: string, keywords: string}>
     */
    public function getTenantPages(): array
    {
        $definitions = [
            // الرئيسية
            [
                'name' => 'لوحة القيادة',
                'route' => 'dashboard',
                'icon' => 'ti-layout-dashboard',
                'category' => 'الرئيسية',
                'permission' => null,
                'keywords' => 'لوحة التحكم الرئيسية احصائيات dashboard home stats',
            ],

            // الإسنادات والعقارات
            [
                'name' => 'الجهات المسندة',
                'route' => 'issuing-entities.index',
                'icon' => 'ti-scale',
                'category' => 'الإسنادات والعقارات',
                'permission' => 'issuing_entities.view',
                'keywords' => 'جهات مسندة محاكم انفاذ دوائر قضائية issuing entities courts',
            ],
            [
                'name' => 'العقارات',
                'route' => 'properties.index',
                'icon' => 'ti-building',
                'category' => 'الإسنادات والعقارات',
                'permission' => 'properties.view',
                'keywords' => 'عقارات اراضي شقق فلل عمائر وحدات properties real estate',
            ],
            [
                'name' => 'إضافة عقار جديد',
                'route' => 'properties.create',
                'icon' => 'ti-plus',
                'category' => 'الإسنادات والعقارات',
                'permission' => 'properties.create',
                'keywords' => 'اضافة عقار تسجيل عقار ادخال جديد create property',
            ],
            [
                'name' => 'الإسنادات',
                'route' => 'assignments.index',
                'icon' => 'ti-file-certificate',
                'category' => 'الإسنادات والعقارات',
                'permission' => 'assignments.view',
                'keywords' => 'اسنادات اوامر اسناد قرارات تكليف assignments',
            ],
            [
                'name' => 'إضافة إسناد جديد',
                'route' => 'assignments.create',
                'icon' => 'ti-plus',
                'category' => 'الإسنادات والعقارات',
                'permission' => 'assignments.create',
                'keywords' => 'اضافة اسناد امر اسناد جديد create assignment',
            ],

            // المزادات والتقارير
            [
                'name' => 'المزادات',
                'route' => 'auctions.index',
                'icon' => 'ti-gavel',
                'category' => 'المزادات والتقارير',
                'permission' => 'auctions.view',
                'keywords' => 'مزادات علنية مزاد الكتروني بيع بالمزاد auctions',
            ],
            [
                'name' => 'إضافة مزاد جديد',
                'route' => 'auctions.create',
                'icon' => 'ti-plus',
                'category' => 'المزادات والتقارير',
                'permission' => 'auctions.create',
                'keywords' => 'اضافة مزاد انشاء مزاد جديد create auction',
            ],
            [
                'name' => 'طلب إقامة مزاد',
                'route' => 'auction-establishment-requests.index',
                'icon' => 'ti-file-certificate',
                'category' => 'المزادات والتقارير',
                'permission' => 'auction_establishment_requests.view',
                'keywords' => 'طلب اقامة مزاد تصريح مزادات تراخيص auction establishment',
            ],
            [
                'name' => 'الحملات التسويقية',
                'route' => 'marketing-campaigns.index',
                'icon' => 'ti-speakerphone',
                'category' => 'المزادات والتقارير',
                'permission' => 'marketing_campaigns.view',
                'keywords' => 'حملات تسويقية اعلانات ترويج سناب واتساب marketing campaigns ads',
            ],
            [
                'name' => 'إقفال المزاد',
                'route' => 'auction-closings.index',
                'icon' => 'ti-lock',
                'category' => 'المزادات والتقارير',
                'permission' => 'auction_closings.view',
                'keywords' => 'اقفال المزاد انهاء المزاد تقرير اقفال ترسية closing auction',
            ],
            [
                'name' => 'تقارير المزادات',
                'route' => 'auction-reports.index',
                'icon' => 'ti-file-analytics',
                'category' => 'المزادات والتقارير',
                'permission' => 'auction_reports.view',
                'keywords' => 'تقارير مزادات احصائيات بروشور تقرير نهائي reports analytics brochure',
            ],

            // التذاكر والدعم
            [
                'name' => 'الدعم الفني والتذاكر',
                'route' => 'tickets.index',
                'icon' => 'ti-headset',
                'category' => 'التذاكر والدعم',
                'permission' => 'tickets.view',
                'keywords' => 'تذاكر دعم فني مساعدة شكاوى tickets support help',
            ],
            [
                'name' => 'فتح تذكرة جديدة',
                'route' => 'tickets.create',
                'icon' => 'ti-ticket',
                'category' => 'التذاكر والدعم',
                'permission' => 'tickets.create',
                'keywords' => 'انشاء تذكرة جديدة فتح تذكرة دعم new ticket support',
            ],

            // إدارة النظام
            [
                'name' => 'إدارة المستخدمين',
                'route' => 'users.index',
                'icon' => 'ti-users',
                'category' => 'إدارة النظام',
                'permission' => 'users.view',
                'keywords' => 'مستخدمين موظفين فريق العمل حسابات users staff team',
            ],
            [
                'name' => 'إضافة مستخدم جديد',
                'route' => 'users.create',
                'icon' => 'ti-user-plus',
                'category' => 'إدارة النظام',
                'permission' => 'users.create',
                'keywords' => 'اضافة موظف مستخدم جديد add user',
            ],
            [
                'name' => 'الأدوار والصلاحيات',
                'route' => 'roles.index',
                'icon' => 'ti-shield-lock',
                'category' => 'إدارة النظام',
                'permission' => 'roles.view',
                'keywords' => 'ادوار صلاحيات اذونات رتب roles permissions',
            ],
            [
                'name' => 'إعدادات النظام',
                'route' => 'settings.index',
                'icon' => 'ti-settings',
                'category' => 'إدارة النظام',
                'permission' => 'system.settings',
                'keywords' => 'اعدادات ضبط عام شعار هوية settings configuration',
            ],
            [
                'name' => 'سجل النشاطات',
                'route' => 'activity-logs.index',
                'icon' => 'ti-activity',
                'category' => 'إدارة النظام',
                'permission' => 'activity_log.view',
                'keywords' => 'سجل نشاطات عمليات حركات تدقيق audit activity logs',
            ],
            [
                'name' => 'إدارة الاشتراك',
                'route' => 'subscriptions.index',
                'icon' => 'ti-award',
                'category' => 'إدارة النظام',
                'permission' => 'subscriptions.view',
                'keywords' => 'اشتراك باقة تجديد ترقية ترخيص subscriptions plans',
            ],
            [
                'name' => 'الفواتير',
                'route' => 'bills.index',
                'icon' => 'ti-credit-card',
                'category' => 'إدارة النظام',
                'permission' => 'bills.view',
                'keywords' => 'فواتير مدفوعات ايصالات سداد bills invoices',
            ],

            // الأرشيف
            [
                'name' => 'أرشيف المستخدمين',
                'route' => 'archive.users.index',
                'icon' => 'ti-archive',
                'category' => 'الأرشيف',
                'permission' => 'user_archive.view',
                'keywords' => 'ارشيف مستخدمين محذوفين users archive',
            ],
            [
                'name' => 'أرشيف العقارات',
                'route' => 'archive.properties.index',
                'icon' => 'ti-archive',
                'category' => 'الأرشيف',
                'permission' => 'properties_archive.view',
                'keywords' => 'ارشيف عقارات محذوفة properties archive',
            ],
            [
                'name' => 'أرشيف الإسنادات',
                'route' => 'archive.assignments.index',
                'icon' => 'ti-archive',
                'category' => 'الأرشيف',
                'permission' => 'assignments_archive.view',
                'keywords' => 'ارشيف اسنادات محذوفة assignments archive',
            ],
            [
                'name' => 'أرشيف المزادات',
                'route' => 'archive.auctions.index',
                'icon' => 'ti-archive',
                'category' => 'الأرشيف',
                'permission' => 'auctions_archive.view',
                'keywords' => 'ارشيف مزادات محذوفة auctions archive',
            ],

            // الحساب الشخصي
            [
                'name' => 'الملف الشخصي',
                'route' => 'profile.show',
                'icon' => 'ti-user',
                'category' => 'الحساب الشخصي',
                'permission' => null,
                'keywords' => 'ملفي الشخصي حسابي كلمة المرور profile account',
            ],
        ];

        return $this->filterPermittedPages($definitions, 'tenant');
    }

    /**
     * Filter page definitions by permission and resolve their URLs.
     *
     * @param  array<int, array{name: string, route: string, icon: string, category: string, permission: string|array<int, string>|null, keywords: string}>  $definitions
     * @return array<int, array{name: string, url: string, icon: string, category: string, keywords: string}>
     */
    protected function filterPermittedPages(array $definitions, string $guard): array
    {
        $pages = [];

        foreach ($definitions as $def) {
            if (! $this->canAccess($def['permission'], $guard)) {
                continue;
            }

            $url = $this->resolveUrl($def['route'], $guard);
            if (! $url || $url === '#') {
                continue;
            }

            $pages[] = [
                'name' => $def['name'],
                'url' => $url,
                'icon' => $def['icon'],
                'category' => $def['category'],
                'keywords' => $def['keywords'],
            ];
        }

        return $pages;
    }

    /**
     * Check if current user in guard has permission.
     *
     * @param  string|array<int, string>|null  $permission
     */
    protected function canAccess(string|array|null $permission, string $guard): bool
    {
        $user = Auth::guard($guard)->user();
        if (! $user) {
            return false;
        }

        if ($permission === null) {
            return true;
        }

        if (is_array($permission)) {
            foreach ($permission as $perm) {
                if (method_exists($user, 'can') && $user->can($perm)) {
                    return true;
                }
            }

            return false;
        }

        return method_exists($user, 'can') && $user->can($permission);
    }

    /**
     * Resolve route URL safely according to guard.
     */
    protected function resolveUrl(string $route, string $guard): ?string
    {
        try {
            if ($guard === 'admin') {
                return Route::has($route) ? route($route) : null;
            }

            if ($guard === 'tenant') {
                if (function_exists('tenant_route_name')) {
                    $url = tenant_route_name($route);

                    return ($url && $url !== '#') ? $url : null;
                }

                return Route::has($route) ? route($route) : null;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
