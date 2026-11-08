<?php

return [
    // الأقسام الرئيسية
    'sections' => [
        'dashboard' => 'لوحة القيادة',
        'issuing_entities_management' => 'إدارة الجهات المسندة',
        'assignments_management' => 'إدارة الإسنادات',
        'properties_management' => 'إدارة العقارات',
        'auctions_management' => 'إدارة المزادات',
        'marketing_campaigns_management' => 'الحملات التسويقية',
        'auction_closings_management' => 'إقفال المزاد',
        'auction_reports_management' => 'تقارير المزادات',
        'auction_templates_management' => 'إدارة القوالب الإعلانية',
        'users_management' => 'إدارة المستخدمين',
        'plans_management' => 'إدارة الباقات',
        'coupons_management' => 'إدارة الكوبونات',
        'companies_management' => 'إدارة الشركات',
        'subscriptions_management' => 'إدارة الإشتراكات',
        'financial_transactions_management' => 'إدارة المعاملات المالية',
        'bills_management' => 'إدارة الفواتير',
        'domain_management' => 'إعدادات الدومين',
        'tickets_management' => 'إدارة التذاكر والدعم الفني',
        'public_requests_management' => 'إدارة طلبات التواصل والتجربة',
        'clients_management' => 'إدارة العملاء',
        'faqs_management' => 'إدارة الأسئلة الشائعة',
        'business_center' => 'مركز الأعمال',
        'system_management' => 'إدارة النظام',
        'archive_management' => 'إدارة الأرشيف',
    ],

    // لوحة القيادة
    'dashboard.main' => 'لوحة القيادة الرئيسية',
    'dashboard.view' => 'لوحة التحكم',

    // إدارة الإسنادات
    'assignments.view' => 'عرض الإسنادات',
    'assignments.create' => 'إضافة إسناد',
    'assignments.edit' => 'تعديل إسناد',
    'assignments.delete' => 'حذف إسناد',
    'assignments.toggle_status' => 'تغيير حالة الإسناد',
    'assignments_archive.view' => 'عرض أرشيف الإسنادات',
    'assignments_archive.restore' => 'استعادة إسناد من الأرشيف',
    'assignments_archive.force_delete' => 'حذف إسناد نهائيًا من الأرشيف',

    // إدارة الجهات المسندة
    'issuing_entities.view' => 'عرض الجهات المسندة',
    'issuing_entities.create' => 'إضافة جهة مسندة',
    'issuing_entities.edit' => 'تعديل جهة مسندة',
    'issuing_entities.delete' => 'حذف جهة مسندة',
    'issuing_entities.toggle_status' => 'تغيير حالة الجهة المسندة',

    // إدارة العقارات والأصول
    'properties.view' => 'عرض العقارات',
    'properties.create' => 'إضافة عقار',
    'properties.edit' => 'تعديل عقار',
    'properties.delete' => 'حذف عقار',
    'properties.toggle_status' => 'تغيير حالة العقار',
    'properties_archive.view' => 'عرض أرشيف العقارات',
    'properties_archive.restore' => 'استعادة عقار من الأرشيف',
    'properties_archive.force_delete' => 'حذف عقار نهائيًا من الأرشيف',

    // إدارة المزادات
    'auctions.view' => 'عرض المزادات',
    'auctions.create' => 'إنشاء مزاد جديد',
    'auctions.edit' => 'تعديل المزاد',
    'auctions.delete' => 'حذف المزاد',
    'auctions.toggle_status' => 'تغيير حالة المزاد',
    'marketing_campaigns.view' => 'عرض الحملات التسويقية',
    'marketing_campaigns.create' => 'إضافة حملة تسويقية',
    'marketing_campaigns.edit' => 'تعديل حملة تسويقية',
    'marketing_campaigns.delete' => 'حذف حملة تسويقية',
    'auction_closings.view' => 'عرض إقفال المزادات',
    'auction_closings.create' => 'إضافة إقفال المزاد',
    'auction_closings.edit' => 'تعديل إقفال المزاد',
    'auction_reports.view' => 'عرض تقارير المزادات',
    'auctions_archive.view' => 'عرض أرشيف المزادات',
    'auctions_archive.restore' => 'استعادة مزاد من الأرشيف',
    'auctions_archive.force_delete' => 'حذف مزاد نهائيًا من الأرشيف',

    // قوالب إعلانية (الإدارة المركزية)
    'auction_templates.view' => 'عرض القوالب الإعلانية',
    'auction_templates.create' => 'إضافة قالب إعلاني',
    'auction_templates.edit' => 'تعديل قالب إعلاني',
    'auction_templates.delete' => 'حذف قالب إعلاني',
    'auction_templates.restore' => 'استعادة قالب إعلاني',
    'auction_templates.force_delete' => 'حذف نهائي لقالب إعلاني',

    // إدارة المستخدمين
    'users.view' => 'المستخدمين',
    'users.create' => 'إضافة مستخدم',
    'users.edit' => 'تعديل مستخدم',
    'users.delete' => 'حذف مستخدم',
    'users.change_status' => 'تغيير حالة المستخدم',
    'users.restore' => 'استعادة مستخدم من الأرشيف',
    'users.force_delete' => 'الحذف النهائي للمستخدم',
    'user_archive.view' => 'أرشيف المستخدمين',
    'user_archive.restore' => 'استرجاع مستخدم من الأرشيف',
    'user_archive.force_delete' => 'حذف نهائي للمستخدم من الأرشيف',

    // إدارة الباقات
    'plans.view' => 'الباقات',
    'plans.create' => 'إضافة باقة',
    'plans.edit' => 'تعديل باقة',
    'plans.delete' => 'حذف باقة',
    'plans.reorder' => 'إعادة ترتيب الباقات',
    'plans.restore' => 'استعادة باقة من الأرشيف',
    'plans.force_delete' => 'الحذف النهائي للباقة',

    // إدارة الكوبونات
    'coupons.view' => 'الكوبونات',
    'coupons.create' => 'إضافة كوبون',
    'coupons.edit' => 'تعديل كوبون',
    'coupons.delete' => 'حذف كوبون',
    'coupons.restore' => 'استعادة كوبون من الأرشيف',
    'coupons.force_delete' => 'حذف كوبون نهائيًا من الأرشيف',

    // إدارة الشركات ومركز الأعمال
    'companies.view' => 'عرض الشركات',
    'companies.create' => 'إضافة شركة',
    'companies.show' => 'تفاصيل الشركة',
    'companies.edit' => 'تعديل الشركة',
    'companies.delete' => 'حذف الشركة',
    'companies.restore' => 'استعادة الشركة',
    'companies.force_delete' => 'حذف الشركة نهائياً',
    'companies_archive.view' => 'عرض أرشيف الشركات',
    'companies_archive.restore' => 'استعادة شركة من الأرشيف',
    'companies_archive.force_delete' => 'حذف شركة نهائيًا من الأرشيف',

    // إدارة الاشتراكات
    'subscriptions.view' => 'عرض الاشتراكات',
    'subscriptions.create' => 'إضافة اشتراك',
    'subscriptions.delete' => 'حذف اشتراك',
    'subscriptions.toggle_status' => 'تغيير حالة الاشتراك',
    'subscriptions.pending.view' => 'عرض طلبات الاشتراك المعلقة',
    'subscriptions.pending.approve' => 'قبول طلب الاشتراك',
    'subscriptions.pending.reject' => 'رفض طلب الاشتراك',
    'subscriptions.renew' => 'تجديد الاشتراك',
    'subscriptions.add_licenses' => 'شراء تراخيص إضافية',
    'subscriptions.upgrade' => 'ترقية الاشتراك',

    // المعاملات المالية والفواتير
    'financial_transactions.view' => 'المعاملات المالية',
    'financial_transactions.restore' => 'استعادة معاملة مالية',
    'financial_transactions.force_delete' => 'حذف معاملة مالية نهائيًا',
    'bills.view' => 'الفواتير',
    'bills.pay' => 'دفع فاتورة',

    // التذاكر والدعم الفني
    'tickets.view' => 'التذاكر والدعم الفني',
    'tickets.create' => 'إنشاء تذكرة',
    'tickets.show' => 'عرض تذكرة',
    'tickets.reply' => 'الرد على تذكرة',
    'tickets.delete' => 'حذف تذكرة',

    // طلبات التواصل والتجربة المجانية
    'contact_requests.view' => 'عرض طلبات التواصل',
    'contact_requests.reply' => 'الرد على طلب تواصل',
    'trial_requests.view' => 'عرض طلبات التجربة',
    'trial_requests.approve' => 'الموافقة على طلب تجربة',
    'trial_requests.reject' => 'رفض طلب تجربة',

    // إدارة العملاء (الإدارة المركزية)
    'clients.view' => 'عرض العملاء',
    'clients.create' => 'إضافة عميل',
    'clients.show' => 'عرض تفاصيل العميل',
    'clients.edit' => 'تعديل عميل',
    'clients.delete' => 'حذف عميل',
    'clients.restore' => 'استعادة عميل',
    'clients.force_delete' => 'حذف عميل نهائيًا',

    // الأسئلة الشائعة
    'faqs.view' => 'عرض الأسئلة الشائعة',
    'faqs.create' => 'إضافة سؤال شائع',
    'faqs.edit' => 'تعديل سؤال شائع',
    'faqs.delete' => 'حذف سؤال شائع',

    // إعدادات النطاقات
    'domain.view' => 'إعدادات الدومين',
    'domain.reserve' => 'حجز دومين',
    'domain.connect' => 'ربط دومين',
    'domain.transfer' => 'نقل دومين',
    'domain_extension_prices.settings' => 'إعدادات أسعار الامتدادات',

    // إعدادات النظام والأدوار
    'system.settings' => 'إعدادات النظام',
    'system.groups' => 'المجموعات',
    'system.security_systems' => 'الأنظمة الأمنية',
    'roles.view' => 'الأدوار والصلاحيات',
    'roles.create' => 'إضافة دور',
    'roles.edit' => 'تعديل دور',
    'roles.delete' => 'حذف دور',
    'roles.assign' => 'إسناد دور لمستخدم',
    'activity_log.view' => 'سجل النشاطات',
    'bank_settings.view' => 'إعدادات البنوك',
];
