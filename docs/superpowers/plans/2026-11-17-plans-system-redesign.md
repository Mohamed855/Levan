# خطة إعادة هيكلة وتطوير نظام الباقات والاشتراكات (Plans System Redesign Plan)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** إعادة هيكلة وتطوير نظام الباقات والاشتراكات في منصة لوت (LOT)، وتفعيل قيود المستأجرين برمجياً (عقارات ومزادات وتخزين) عبر Middleware مخصص، مع فصل حد المستخدمين وإتاحة خيار "غير محدود" وإلغاء شراء التراخيص الإضافية حينها، وتبسيط لوحة التحكم وتنظيف البيانات الميتة.

**Architecture:** بناء Middleware مخصص للمستأجرين باسم `EnsureTenantLimitNotExceeded` يعتمد على خدمة سياق الاشتراكات المركزية `CentralSubscriptionContextGateway` للتحقق الحقيقي من حدود العقارات والمزادات قبل الإنشاء. فصل حد المستخدمين في نموذج الباقة واستمارة الإدارة ليعمل باستقلالية عن الحدود التشغيلية، مع إخفاء أسعار التراخيص الإضافية وتعطيل شرائها عند اختيار "مستخدمين غير محدودين"، وتبسيط جدول بيانات الباقات من 20 إلى 9 أعمدة مدمجة.

**Tech Stack:** PHP 8.2+, Laravel 12, Stancl Tenancy 3.10+, Blade Components, Bootstrap 5 RTL, Tabler Icons, Yajra DataTables, PHPUnit.

**Spec:** [تقرير المراجعة الشاملة لنظام الباقات والاشتراكات](file:///C:/Users/moham/.gemini/antigravity/brain/cc3fec3a-c110-4752-919d-06d1f3b48d85/plans-system-audit-and-recommendations.md)

---

## Global Constraints

- **Strict PSR-4 & Strict Types:** كل كلاس PHP جديد يجب أن يحتوي على `declare(strict_types=1);` والالتزام الصارم بتطابق حالة الأحرف في المسارات والـ namespaces.
- **Multi-Tenancy Isolation:** يتم فحص حدود الباقة والاشتراك عبر استعلام مركزي في قاعدة البيانات المركزية (`Tenancy::central`) لتجنب أي تداخل بين قواعد بيانات المستأجرين.
- **RTL & UX Consistency:** جميع التنبيهات ورسائل الحظر يجب أن تكون باللغة العربية الواضحة والمشجعة على الترقية، مع أيقونات Tabler متوافقة (`ti ti-*`).
- **No Indiscriminate `transform: none !important`:** عدم تطبيق أي كلاس يلغي دوران الأيقونات الدوارة (`spinners`) داخل الأزرار.

---

## Review Focus

1. **حالة التجربة المجانية والاشتراكات المنتهية:** التأكد من أن المستأجر في فترة التجربة أو الاشتراك المنتهي لا يتجاوز الحدود المعرفة له في `config('trial.limits')` أو الباقة.
2. **محاولات تجاوز الحدود عبر AJAX/JSON:** التأكد من أن الـ Middleware يُرجع كود `403` مع كائن JSON متناسق للطلبات البرمجية، ورسالة فلاش وتوجيه خلفي لطلبات المتصفح العادية.
3. **تعديل الباقات المرتبطة باشتراكات:** منع كسر العقود السارية عند تعديل باقة قيد الاستخدام مع الحفاظ على الحقول المقفلة بأمان.
4. **شراء التراخيص للباقات غير المحدودة:** التأكد من استحالة قيام مستأجر على باقة "مستخدمين غير محدودة" بالدخول لصفحة شراء تراخيص أو محاسبته على أي مبلغ.
5. **سلامة العرض على الشاشات الصغيرة:** التأكد من أن كروت صفحة الهبوط وجدول الإدارة المبسط يعملان بكفاءة على شاشات الجوال والحواسيب اللوحية.

---

## Task 1: بناء وتفعيل الـ Middleware للحدود التشغيلية (`EnsureTenantLimitNotExceeded`)

**Files:**
- Create: `app/Http/Middleware/Tenancy/EnsureTenantLimitNotExceeded.php`
- Modify: `app/Services/Tenants/Shared/CentralSubscriptionContextGateway.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/tenant.php`
- Test: `tests/Feature/Tenant/TenantLimitsEnforcementFeatureTest.php`

- [ ] **Step 1: كتابة اختبار فاشل للتحقق من منع تجاوز حد العقارات والمزادات**
  - إنشاء `tests/Feature/Tenant/TenantLimitsEnforcementFeatureTest.php`.
  - كتابة اختبار يحاكي مستأجراً يملك باقة بحد 2 عقار ومزاد واحد:
    - التحقق من نجاح إضافة العقار الأول والثاني.
    - التحقق من حظر إضافة العقار الثالث وتوجيه رسالة منع واضحة.
    - التحقق من نجاح المزاد الأول وحظر المزاد الثاني عند تجاوزه.
  - تشغيل الاختبار والتأكد من فشله مبدئياً:
    ```bash
    php artisan test tests/Feature/Tenant/TenantLimitsEnforcementFeatureTest.php
    ```

- [ ] **Step 2: تزويد `CentralSubscriptionContextGateway` بدوال فحص الحدود**
  - إضافة ميثود `getPropertyLimitInfo()` لاحتساب حد العقارات وحالة "غير محدود".
  - إضافة ميثود `canCreateProperty(int $currentCount): bool`.
  - إضافة ميثود `getAuctionLimitInfo()` لاحتساب حد المزادات النشطة وحالة "غير محدود".
  - إضافة ميثود `canCreateAuction(int $currentActiveCount): bool`.

- [ ] **Step 3: إنشاء `EnsureTenantLimitNotExceeded` Middleware**
  - في `app/Http/Middleware/Tenancy/EnsureTenantLimitNotExceeded.php`:
    - استقبال البارامتر `$resource` (`properties` أو `auctions`).
    - عند `$resource === 'properties'`: احتساب عدد عقارات المستأجر غير المحذوفة، ومقارنتها بالحد.
    - عند `$resource === 'auctions'`: احتساب عدد المزادات النشطة ومقارنتها بالحد.
    - إذا تم تجاوز الحد:
      - إذا كان الطلب AJAX/JSON: إرجاع `response()->json(['message' => 'لقد وصلت إلى الحد الأقصى المسموح به في باقتك...'], 403)`.
      - لطلبات الويب: التوجيه لصفحة الفهرس مع `with('error', 'لقد استنفدت الحد المتاح في باقتك...')`.

- [ ] **Step 4: تسجيل الـ Middleware وتطبيقه على مسارات المستأجر**
  - في `bootstrap/app.php`: تسجيل الاسم المستعار `tenant.limit => EnsureTenantLimitNotExceeded::class`.
  - في `routes/tenant.php`: تطبيق الـ middleware:
    - مسارات العقارات: `properties.create`, `properties.store` تأخذ `tenant.limit:properties`.
    - مسارات المزادات: `auctions.create`, `auctions.store` تأخذ `tenant.limit:auctions`.

- [ ] **Step 5: تشغيل الاختبار والتحقق من النجاح**
  - تشغيل `php artisan test tests/Feature/Tenant/TenantLimitsEnforcementFeatureTest.php`.
  - التأكد من اجتياز كافة الاختبارات بنجاح.

---

## Task 2: فصل حد المستخدمين وإلغاء شراء التراخيص للباقات غير المحدودة

**Files:**
- Modify: `app/Models/Admin/Plans/Plan.php`
- Modify: `app/Http/Requests/Admin/Plans/PlanRequest.php`
- Modify: `app/Services/Admin/Plans/PlanService.php`
- Modify: `app/Services/Tenants/Shared/CentralSubscriptionContextGateway.php`
- Modify: `app/Http/Controllers/Tenants/Subscriptions/TenantSubscriptionController.php`
- Modify: `resources/views/tenant/subscriptions/index.blade.php`
- Test: `tests/Feature/Tenant/TenantUserLimitsFeatureTest.php`

- [ ] **Step 1: كتابة اختبار فاشل للتحقق من سلوك الباقات غير المحدودة للمستخدمين**
  - إنشاء `tests/Feature/Tenant/TenantUserLimitsFeatureTest.php`.
  - التحقق من:
    - عند كون الباقة `included_users === null`، يستطيع المستأجر إضافة أي عدد من الموظفين دون مانع.
    - محاولة الوصول لصفحة شراء تراخيص إضافية في باقة مستخدمين غير محدودة تُرجع توجيهاً وتمنع الشراء.
    - اختفاء زر "شراء تراخيص إضافية" من واجهة الاشتراكات في المستأجر.
  - تشغيل الاختبار والتأكد من فشله:
    ```bash
    php artisan test tests/Feature/Tenant/TenantUserLimitsFeatureTest.php
    ```

- [ ] **Step 2: تحديث نموذج الباقة `Plan.php` بدوال واضحة**
  - إضافة `hasUnlimitedUsers(): bool` (تُرجع `true` إذا كان `included_users === null`).
  - إضافة `allowsExtraUserLicenses(): bool` (تُرجع `true` فقط إذا كان المستخدمون محددين ويوجد سعر ترخيص إضافي `> 0`).

- [ ] **Step 3: تحديث التحقق والخدمة (`PlanRequest` & `PlanService`)**
  - في `PlanRequest.php`:
    - إضافة حقل اختياري للواجهة `users_limit_mode` (`limited` / `unlimited`).
    - إذا كان `users_limit_mode === 'unlimited'`: يتم دمج `included_users = null` و `extra_user_license_price = null`.
    - إذا كان `users_limit_mode === 'limited'`: التحقق من أن `included_users` مطلوب ورقم صحيح `>= 1`، و `extra_user_license_price` مطلوب ورقم `>= 0`.
  - في `PlanService.php`:
    - التأكد من عدم تصفير `included_users` عند اختيار وضع العمليات غير المحدود إذا كان حد المستخدمين محدداً.

- [ ] **Step 4: حماية وتحديث مسار شراء التراخيص في المستأجر**
  - في `TenantSubscriptionController::licenseCheckout` و `processLicense`:
    - فحص هل الباقة تملك مستخدمين غير محدودين (`$plan->hasUnlimitedUsers()`).
    - في حال كان غير محدود: إعادة التوجيه الفوري مع رسالة: `باقات المستخدمين غير المحدودين لا تتطلب شراء تراخيص إضافية.`
  - في `resources/views/tenant/subscriptions/index.blade.php`:
    - إخفاء زر "شراء تراخيص إضافية" بالكامل إذا كانت الباقة الحالية غير محدودة للمستخدمين.

- [ ] **Step 5: تشغيل الاختبار والتأكد من النجاح**
  - تشغيل `php artisan test tests/Feature/Tenant/TenantUserLimitsFeatureTest.php`.

---

## Task 3: إعادة هيكلة واجهات الإدارة (Form & Simplified DataTable)

**Files:**
- Modify: `resources/views/admin/plans/partials/_form.blade.php`
- Modify: `resources/assets/js/admin/plans/form-ui.js`
- Modify: `app/DataTables/Admin/Plans/PlanDataTable.php`
- Modify: `resources/views/admin/plans/show.blade.php`
- Test: `tests/Feature/Admin/PlanManagementUiFeatureTest.php`

- [ ] **Step 1: كتابة اختبار لواجهات إدارة الباقات**
  - إنشاء `tests/Feature/Admin/PlanManagementUiFeatureTest.php`.
  - التأكد من إمكانية حفظ باقة بـ "مستخدمين محددين + عمليات غير محدودة" والعكس.
  - التأكد من خلو `PlanDataTable` من الأخطاء عند استعراض الباقات بعد دمج الأعمدة.

- [ ] **Step 2: تحديث الخطوة 3 (الحدود) في `_form.blade.php` و `form-ui.js`**
  - تقسيم الخطوة الثالثة بوضوح إلى قسمين:
    1. **حدود المستخدمين (Users Limit):**
       - اختيار: [عدد محدد | غير محدود].
       - عند "محدد": إظهار حقلي `included_users` و `extra_user_license_price`.
       - عند "غير محدود": إخفاء حقلي `included_users` و `extra_user_license_price` تماماً وتفريغهما.
    2. **الحدود التشغيلية (Operational Limits):**
       - اختيار: [محددة | غير محدودة].
       - عند "محددة": إظهار حقول العقارات، المزادات النشطة، وحصة التخزين.
       - عند "غير محدودة": إظهار رسالة التنبيه وإخفاء الحقول الثلاثة.

- [ ] **Step 3: تبسيط وتقليص جدول الباقات `PlanDataTable.php`**
  - تقليص الأعمدة الـ 20 إلى **9 أعمدة مركزة وأنيقة**:
    1. `checkbox`
    2. `color`
    3. `name` (مدمج معه الكود `code` في سطر فرعي أنيق قابل للنسخ).
    4. `is_most_requested` (شارة نعم / لا).
    5. `plan_type` (شارة النوع).
    6. `price_summary` (عمود موحد: السعر النهائي بعد الخصم + دورة الفوترة، مع شطب السعر الأصلي إن وجد خصم).
    7. `users_summary` (المستخدمين: مثلاً `غير محدود` أو `5 مستخدمين (+50 ر.س لإضافي)`).
    8. `limits_summary` (الحدود: مثلاً `25 عقار | 5 مزادات | 5 GB` أو شارة `غير محدود`).
    9. `is_active`
    10. `actions`
  - إزالة الأعمدة المكررة المنفصلة لتجنب السكرول الأفقي المزعج.

- [ ] **Step 4: تحديث صفحة تفاصيل الباقة `show.blade.php`**
  - عكس التقسيم الجديد بوضوح: إظهار وضع المستخدمين المستقل (مع سعر الترخيص فقط إن كان محدداً)، وقسم الحدود التشغيلية بشكل منفصل.

- [ ] **Step 5: تشغيل الاختبار والتحقق**
  - تشغيل `php artisan test tests/Feature/Admin/PlanManagementUiFeatureTest.php`.

---

## Task 4: تحسين كارت الباقة على صفحة الهبوط وإبراز سعر الترخيص الإضافي

**Files:**
- Modify: `resources/views/components/shared/ui/package-card.blade.php`
- Modify: `resources/assets/scss/shared/package-card.scss`
- Test: `tests/Feature/PublicPlanVisibilityFeatureTest.php`

- [ ] **Step 1: كتابة اختبار لعرض بطاقة الباقة الجديد**
  - تحديث `tests/Feature/PublicPlanVisibilityFeatureTest.php`:
    - فحص أن الباقة المحدودة للمستخدمين تعرض بوضوح سعر المستخدم الإضافي للمشتري.
    - فحص أن الباقة غير المحدودة تعرض `غير محدود` ولا تذكر سعر ترخيص إضافي.

- [ ] **Step 2: تحديث قسم حدود البطاقة في `package-card.blade.php`**
  - في بطاقة المستخدمين (`users` limit):
    - إذا كان `hasUnlimitedUsers()`: عرض "غير محدود".
    - إذا كان محدداً: عرض `X مستخدم` وأسفلها بخط ناعم وواضح: `+Y ر.س لكل مستخدم إضافي` (إذا كان السعر متوفراً).
  - في بطاقات العقارات والتخزين والمزادات:
    - عرض الرقم المسموح بوضوح، أو `غير محدود` في حال اختيار الأدمن وضع العمليات غير المحدود.

- [ ] **Step 3: بناء الأصول والتحقق من التناسق**
  - تشغيل `npm run build`.
  - تشغيل الاختبارات:
    ```bash
    php artisan test tests/Feature/PublicPlanVisibilityFeatureTest.php
    ```

---

## Task 5: تنظيف البيانات المهجورة وإصلاح منطق التجديد

**Files:**
- Delete: `resources/assets/js/shared/basic-tamplate/pricing.js`
- Delete: `resources/assets/js/web/basic-tamplate/pricing.js`
- Modify: `app/Http/Controllers/Tenants/Subscriptions/TenantSubscriptionController.php`
- Test: `tests/Feature/Tenant/TenantSubscriptionRenewalFeatureTest.php`

- [ ] **Step 1: كتابة اختبار فاشل لتجديد الاشتراك بالسعر الفعلي**
  - إنشاء `tests/Feature/Tenant/TenantSubscriptionRenewalFeatureTest.php`.
  - التحقق من أن تجديد اشتراك باقة شهرية بسعر 500 ريال يقوم بمحاسبة المستأجر على 500 ريال لدورة شهرية، ولا يفرض عليه `yearly_renewal_price` بشكل أعمى.

- [ ] **Step 2: تصحيح كود التجديد في `TenantSubscriptionController.php`**
  - في `renewCheckout` و `processRenew`:
    - اعتماد السعر الفعلي للباقة بعد الخصم (`$plan->price_after_discount > 0 ? $plan->price_after_discount : $plan->price`).
    - تحديد فترة التجديد تلقائياً بحسب دورة فوترة الباقة (`subscription_billing_cycle`).

- [ ] **Step 3: حذف ملفات القالب القديمة الميتة**
  - حذف الملفين المهجورين:
    - `resources/assets/js/shared/basic-tamplate/pricing.js`
    - `resources/assets/js/web/basic-tamplate/pricing.js`

- [ ] **Step 4: فحص التوافقية والأكواد العامة**
  - تشغيل فحص التنسيق: `./vendor/bin/pint --test`
  - تشغيل فحص الـ Autoload الصارم: `composer dump-autoload --strict-psr`
  - تشغيل كامل حزمة الاختبارات ذات الصلة:
    ```bash
    php artisan test
    ```
