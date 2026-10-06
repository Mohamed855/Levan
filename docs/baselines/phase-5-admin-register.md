# سجل بوابة القبول: المرحلة 5 — Super Admin

- **التاريخ:** 6 أكتوبر 2026
- **الحالة:** قيد الإغلاق — غير معتمدة للانتقال إلى Tenant بعد. راجع [`phase-0-5-closure-audit-2026-10-06.md`](phase-0-5-closure-audit-2026-10-06.md) للفجوات المثبتة.
- **المرجع التنفيذي:** [خطة التوحيد الشاملة](../system-unification-implementation-plan-ar.md) — البند 9

---

## 1. ملخص الإنجاز

تم إتمام مراجعة وتوحيد كافة أقسام وإدارات لوحة التحكم المركزية (Super Admin) وفق الأولويات والعقود المعيارية المعتمدة في وثيقة القواعد `AGENTS.md`:

1. **فصل مسارات الحذف الجماعي المركزي:**
   - تم إنشاء المتحكم المستقل [`AdminMassDeleteController`](file:///mnt/Hub/Work/Etmam/Levan/app/Http/Controllers/Admin/DeleteBulk/AdminMassDeleteController.php) في مساحة Admin وعزل مسار `admin.mass-delete` تماماً عن متحكم المستأجرين القديم `App\Http\Controllers\Tenants\DeleteBulk\MassDeleteController`.
   - تم تقييد النماذج المسموح بحذفها بقائمة بيضاء مركزية صارمة مع فحص الصلاحية المناسبة لكل نموذج بالإنجليزية (`plans.delete`, `coupons.delete`, `companies.delete`, `subscriptions.delete`, `bills.delete`, `tickets.delete`, `users.delete`, `clients.delete`).

2. **توحيد نماذج الإدخال ومعالجات الـ Wizard:**
   - تم تحويل نموذج إنشاء الاشتراكات [`admin/subscriptions/create.blade.php`](file:///mnt/Hub/Work/Etmam/Levan/resources/views/admin/subscriptions/create.blade.php) ونموذج الـ JS المرافق له [`create-wizard.js`](file:///mnt/Hub/Work/Etmam/Levan/resources/assets/js/admin/subscriptions/create-wizard.js) للاعتماد على المكوّن الموحد `<x-form.wizard.stepper>` ومحرك التحقق المشترك `SharedFormValidation` ونقطة الفحص المركزية `admin.wizard-drafts.validate-step`، مع إزالة الاعتمادية على السكربتات المخصصة ومكتبة `@form-validation/popular.js` القديمة.
   - تأكيد التزام كافة نماذج الإدارات الأخرى (الباقات، الشركات، الكوبونات، المستخدمين) بعقد الحقول المشتركة `<x-form.controls.*>` والـ FormRequests كمصدر وحيد للحقيقة.

3. **توحيد جداول البيانات (DataTables) وأزرار تبديل الحالة:**
   - تدقيق كافة الجداول التي تحتوي على حالات نشاط والتأكد من مطابقة الشرط الإلزامي: عمود `الحالة` يسبق عمود `الإجراءات` مباشرة ويتم تصييره بواسطة المكون المشترك `js-table-status-toggle` مستنداً إلى `data-url` مع معالجة الحدث عبر `table-status-toggle.js`:
     - الباقات (`PlanDataTable`)
     - الاشتراكات (`SubscriptionDataTable`)
     - الكوبونات (`CouponDataTable`)
     - المستخدمين (`AdminUserDataTable`)
     - الإصدارات (`VersionDataTable`)
     - مختبرو الإصدارات (`VersionTesterDataTable`)
     - البنوك (`BankDataTable`)
     - الدومينات (`SettingDomainDataTable`)
     - العملاء (`ClientDataTable`)
     - المحافظ (`PortfolioDataTable`)

4. **حماية مسارات الفواتير وتصحيح الروابط:**
   - إضافة middleware الصلاحيات في منشئ [`AdminBillsController`](file:///mnt/Hub/Work/Etmam/Levan/app/Http/Controllers/Admin/Bills/AdminBillsController.php) بالإنجليزية: `financial_transactions.view`، `subscriptions.pending.approve`، `subscriptions.pending.reject`.
   - تصحيح رابط رقم الفاتورة في [`AdminBillsDataTable`](file:///mnt/Hub/Work/Etmam/Levan/app/DataTables/Admin/Bill/AdminBillsDataTable.php) لاستخدام `route('admin.bills.show')` بدلاً من المساعد الخاص بالمستأجرين `tenant_route_name`.

5. **المعالجات الخاصة الموثقة:**
   - **مسار `plans.reorder`:** تم توثيقه كـ Alias/Compatibility Route مستمر بالتوازي مع المسار الرسمي `plans.update-sort-order` دون أي تعارض.
   - **طلبات التحويل (Transfer Requests):** تم إبقاؤها معطلة رسميّاً ضمن قائمة المسارات غير النشطة (Inactive Registry) ولن يُعاد تفعيلها إلا بقرار وخطة منفصلة.

---

## 2. جدول مطابقة الإدارات المركزية (Super Admin Manifest)

| الإدارة | Controller | FormRequest | DataTable | حالة التبديل | الصلاحيات الإنجليزية |
|---|---|---|---|---|---|
| **الباقات (Plans)** | `PlanController` | `PlanRequest` / `PlanReorderRequest` | `PlanDataTable` | `js-table-status-toggle` | `plans.view`, `plans.create`, `plans.edit`, `plans.delete`, `plans.reorder` |
| **الشركات (Companies)** | `CompanyController` | `StoreCompanyRequest` / `UpdateCompanyRequest` | `CompanyDataTable` | Badges (دورة حياة) | `companies.view`, `companies.create`, `companies.show`, `companies.edit`, `companies.delete` |
| **الاشتراكات (Subscriptions)** | `SubscriptionController` | `StoreSubscriptionRequest` | `SubscriptionDataTable` | `js-table-status-toggle` | `subscriptions.view`, `subscriptions.create`, `subscriptions.delete`, `subscriptions.toggle_status` |
| **الفواتير (Bills)** | `AdminBillsController` | — | `AdminBillsDataTable` | Status Badges | `financial_transactions.view`, `subscriptions.pending.approve`, `subscriptions.pending.reject` |
| **الكوبونات (Coupons)** | `AdminCouponController` | `CouponRequest` | `CouponDataTable` | `js-table-status-toggle` | `coupons.view`, `coupons.create`, `coupons.edit`, `coupons.delete` |
| **المستخدمين (Users)** | `AdminUserController` | `AdminUserStoreRequest` / `AdminUserUpdateRequest` | `AdminUserDataTable` | `js-table-status-toggle` | `users.view`, `users.create`, `users.edit`, `users.delete`, `users.change_status` |
| **الأدوار (Roles)** | `AdminRoleController` | `AdminStoreRoleRequest` / `AdminUpdateRoleRequest` | `AdminRoleDataTable` | — | `roles.view`, `roles.create`, `roles.edit`, `roles.delete`, `roles.assign` |
| **الإصدارات (Versions)** | `VersionController` | `VersionRequest` | `VersionDataTable` | `js-table-status-toggle` | `versions.view`, `versions.create`, `versions.edit`, `versions.delete` |
| **مختبرو الإصدارات** | `VersionTesterController` | `VersionTesterRequest` | `VersionTesterDataTable` | `js-table-status-toggle` | `version_testers.view`, `version_testers.create`, `version_testers.edit`, `version_testers.delete` |
| **البنوك (Banks)** | `SettingsBankController` | `BankRequest` | `BankDataTable` | `js-table-status-toggle` | `bank_settings.view` |
| **الدومينات (Domains)** | `SettingsDomainController` | `DomainExtensionPriceRequest` | `SettingDomainDataTable` | `js-table-status-toggle` | `domain_extension_prices.settings` |
| **العملاء (Clients)** | `ClientController` | `ClientRequest` | `ClientDataTable` | `js-table-status-toggle` | `clients.view`, `clients.create`, `clients.edit`, `clients.delete` |
| **المحافظ (Portfolios)**| `PortfolioController` | `PortfolioRequest` | `PortfolioDataTable` | `js-table-status-toggle` | `clients.view`, `clients.create`, `clients.edit`, `clients.delete` |
| **التذاكر (Tickets)** | `AdminTicketController`| `TicketRequest` | `AdminTicketsDataTable` | Workflow Badges | `tickets.view`, `tickets.show`, `tickets.reply`, `tickets.delete` |

---

## 3. نتائج الاختبارات وبناء الواجهة

- **بناء الواجهة (`npm run build`):**
  - تم التنفيذ بنجاح واكتمال البناء في 29.58 ثانية بدون أي أخطاء.
- **الاختبارات الآلية (`php artisan test --testsuite=Unit`):**
  - تم إنشاء وتنفيذ اختبارات العقد المعماري [`SuperAdminContractTest`](file:///mnt/Hub/Work/Etmam/Levan/tests/Unit/SuperAdminContractTest.php).
  - اجتياز كافة الاختبارات الآلية (17 اختباراً بنجاح و1,123 تأكيداً/Assertion).
  - اجتياز اختبار مفاتيح الصلاحيات الإنجليزية `EnglishAuthorizationKeysTest` لكافة ملفات الـ Blade والـ PHP في المشروع.
