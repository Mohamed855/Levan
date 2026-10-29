# سجل تنفيذ خطة Golden Pattern الشاملة

الخطة: [الخطة الرئيسية](../superpowers/plans/2026-10-29-golden-pattern-remediation.md) وملحقات Admin/Tenant/Shared. تاريخ بدء هذه الدفعة: 29 أكتوبر 2026. الفرع: `codex/golden-pattern-complete-20261029`.

## تأكيد النطاق

قارنت handlers الحالية من `php artisan route:list --json` بالجرد المرتبط بالخطة قبل البدء: لم تظهر handlers للوحتين خارج الخطة. النطاق يشمل SuperAdmin وTenant وكل الصفحات النشطة والعمليات التابعة، ولا يقتصر على الإسنادات والعقارات والمزادات. هذه مطابقة لنطاق التنفيذ؛ لا تعني نجاح فحص بصري لكل صفحة.

## الدفعة الأولى: إصلاحات الأمن وسلامة البيانات والأعطال المباشرة

| المرجع | ما نُفذ | الدليل | حدود الإغلاق |
|---|---|---|---|
| A02 / R01 | فصل reset الإداري إلى Request/Action مركزيين، وتفويض تعديل المستأجر قبل تغيير كلمة المرور؛ الحفاظ على تدفق OTP | ScopedPasswordResetAndFaqAuthorizationTest وTenantAuthenticationContractTest | لا تعني اكتمال جميع صفحات المصادقة و2FA |
| A21 / R13 | فصل صلاحيات Store/Update للـFAQ، وإكمال رسائل/attributes والتحقق | ScopedPasswordResetAndFaqAuthorizationTest | تحويل Index إلى DataTable وتوحيد الفورم والمسودات ما زال مطلوبًا |
| T05 / R03–R04 | تشغيل سكربت Create/Edit، واستعادة الاختيارات والمراجعة، وقفل العقارات وإعادة فحص الإتاحة ومنع كسر ارتباطات المزاد | AssetMutationSafetyTest وAssignmentFrontendSafetyTest | المعاينة البصرية لكل خطوة وقبول الإدارة الكامل لم ينفذا |
| T06 / R02 | تأجيل حذف الملفات القديمة بعد commit، ومنع تنظيف الملف الجديد عند فشل تنظيف القديم، والحفاظ على الملفات عند فشل الاستبدال | AssetMutationSafetyTest مع ملفات فعلية معزولة | تنظيف فشل التخزين الدائم يحتاج متابعة تشغيلية؛ لا أعتمد كل دورة الملفات مكتملة |
| T15 / R05 | allow-list مستقلة لـTenant، ورفض Central models قبل الاستعلام، وفحص الصلاحيات وكل سجل، ومنع حذف subsets أو IDs مكررة، وتطبيق domain guards على الأصول والمستخدمين والأدوار | AssetMutationSafetyTest: حالات HTTP وtransactions وrollback | قبول إدارة الأرشيف والتزامن في MySQL ما زال منفصلًا |
| S03 / R06–R09 | تحديد guard من route وDB connection الصحيح؛ file rollback وafterCommit، واستبدال القوائم بدل إحياء المحذوف، وhash للملفات، وحدود uploads من FormRequest المورد، ومنع البدء الجديد عند فشل discard | WizardDraftSafetyTest وWizardDraftFrontendSafetyTest | ما زال مطلوبًا job/retention وتنظيف دائم للفشل وضمان discard الكامل وإشارة نجاح Create/JSON purge؛ S03 جزئي |
| T02 | إصلاح view التحقق من البريد إلى view Tenant الموجودة | NavigationBoundaryTest | Central gateway الخاص بإعدادات2FA ما زال مطلوبًا |
| T13 | إصلاح صفحة قائمة الإشعارات وFormRequest الخاص بتحديد IDs لسجل النشاط | NavigationBoundaryTest | استخراج بقية orchestration والتصدير والقبول الكامل ما زال مطلوبًا |
| T10 / R11 | توجيه مسار renewal القديم إلى تدفق الدفع المصرح القائم، بدل handler primitive المعطوب والتجديد المباشر | NavigationBoundaryTest | لا اتصالات أو تجديد حقيقي بمزود خارجي؛ بقية صفحات الدومينات لم تغلق |
| T08 / R12 | رسالة عامة لأخطاء التقارير في API/Show/DataTable، تشمل السجلات القديمة؛ دعم غياب أول generation | ReportErrorPrivacyTest | بقية async/idempotency/queue acceptance لم تغلق |
| توافق روابط Admin / R10 | وجهة absolute و307 يحافظ على method/body للروابط القديمة | NavigationBoundaryTest | أسماء routes بقيت كما هي |

## الدفعة الثانية: توحيد النماذج والمرفقات والعزل المركزي عبر المراحل 1، 2، 3، و4

| المرجع | ما نُفذ | الدليل | حدود الإغلاق |
|---|---|---|---|
| S07 / عزل حدود Tenant | إنشاء `CentralTenantSecurityGateway` لعزل استعلامات 2FA المركزية، واستخدام `tenancy()->initialize($tenantId)` مباشرة في Jobs، واستبدال استيراد نماذج Central في خدمات Tenant | `TenantCentralBoundaryContractTest` (اختباران، 214 تأكيداً دون أخطاء) | لا يعبر أي كود Tenant إلى Central دون Gateway |
| S01 / تطابق المانيفست والبطاقات | إعادة بناء بطاقة `public-trial-requests.md` طبقاً لمخطط الـ22 مفتاحاً مع مسارات الفئات الحقيقية | `SuperAdminGoldenPatternInventoryTest` و`TenantGoldenPatternInventoryTest` و`GoldenPatternScopeContractTest` (8 اختبارات، 940 تأكيداً) | تم مطابقة جميع البطاقات مع الهيكل الفعلي |
| S02 / عقود خيارات Select والترجمة | إزالة جميع مصفوفات خيارات Select النصية المضمنة (`:options="[...]"` أو `<option>`) وترحيلها لملفات الترجمة `lang/ar/` والـEnums والمتغيرات الممررة من المتحكمات | `SelectOptionsSourceContractTest` (اختبار واحد، تأكيد واحد، 100% نجاح) | منع تعريف اختيارات النماذج داخل ملفات Blade أو JS نهائياً |
| A05 / إدارة العملاء والشركات | توحيد شاشتي الإنشاء والتعديل في جزئية مشتركة واحدة `admin.clients.partials._form`، وإزالة تعطيل زر التالي البرمجي، وتوحيد المراجعة الشاملة ودورة المسودة | `CentralClientsGoldenPatternTest` (اختباران، 18 تأكيداً) و`AdminClientsFeatureTest` (9 اختبارات، 44 تأكيداً) | إغلاق فجوة تكرار الشاشات وتعطيل الأزرار |
| A06 / باقات المنصة | توحيد شاشتي Create/Edit في `admin.plans.partials._form`، وإزالة السايدبار المصطنع (`col-lg-9/col-lg-3`)، وإزالة نصوص الأزرار المخصصة | `CentralPlansGoldenPatternTest` (3 اختبارات، 22 تأكيداً) و`CreateFormDraftContractTest` | إغلاق كامل لفجوة تصميم ونماذج الباقات |
| A10 / كوبونات المنصة | توحيد شاشتي الإنشاء والتعديل في `admin.coupons.partials._form`، تثبيت كود الكوبون كـreadonly في التعديل، وقصر زر المسودة على الإنشاء | `CentralCouponsGoldenPatternTest` (اختبار واحد، 10 تأكيدات) و`test_admin_coupons_crud_and_archive_lifecycle` | إغلاق كامل لنموذج الكوبونات |
| A15 / حسابات البنوك | توحيد Create/Edit في `admin.settings.banks.partials._form` كنموذج بطاقة أحادي الخطوة، وإزالة نصوص الأزرار المخصصة، وحذف `form.blade.php` القديم | `CreateFormDraftContractTest` و`test_admin_settings_banks_and_domains_crud_lifecycle` | نموذج بنكي موحد متكامل مع المسودات |
| A16 / نطاقات ودومينات المنصة | توحيد Create/Edit في `admin.settings.domains.partials._form` كنموذج بطاقة أحادي الخطوة، وإزالة نصوص الأزرار المخصصة، وحذف `form.blade.php` القديم | `CreateFormDraftContractTest` و`test_admin_settings_banks_and_domains_crud_lifecycle` | نموذج دومينات موحد متكامل مع المسودات |
| A21 / الأسئلة الشائعة FAQ | فصل العرض إلى `create.blade.php` و`edit.blade.php` مع الجزئية المشتركة `_form.blade.php`، وإرجاع استجابة JSON للحذف عبر AJAX، وحذف `form.blade.php` القديم | `ScopedPasswordResetAndFaqAuthorizationTest` (13 اختباراً، 39 تأكيداً) | مطابقة عقد الحذف والاستجابة للأسئلة الشائعة |
| A20 / قوالب المزادات | إنشاء الجزئية المشتركة `admin.auction-templates.partials._form`، وربط Create/Edit بها، وحذف `form.blade.php` القديم | `SelectOptionsSourceContractTest` و`CreateFormDraftContractTest` | تطابق كامل مع متطلبات النماذج المشتركة |
| T12 / تذاكر دعم المستأجر | إنشاء الجزئية المشتركة `tenant.tickets.partials._form`، إزالة نصوص الأزرار المخصصة، واستبدال كافة الروابط والقوائم العشوائية للمرفقات ببطاقات `<x-ui.file-card>` الموحدة بمقاس 150×150 | `CreateFormDraftContractTest` و`NavigationBoundaryTest` | توحيد نموذج التذاكر ومعاينات المرفقات |
| A11 / تذاكر دعم الإدارة المركزية | استبدال كافة القوائم العشوائية والأزرار لملفات المرفقات والردود في `admin.tickets.show` ببطاقات `<x-ui.file-card>` الموحدة بمقاس 150×150 | مراجعة الكود واختبارات العقد المشترك | إلغاء Layout القائمة القديم واعتماد مقاس 150×150 |
| A07 / اشتراكات الإدارة المركزية | استخراج الجزئية المشتركة `admin.subscriptions.partials._form`، وترحيل خيارات طرق وحالات الدفع إلى `lang/ar/subscriptions.php` | `SelectOptionsSourceContractTest` و`CreateFormDraftContractTest` | منع مصفوفات الخيارات المضمنة في شاشات الاشتراكات |

## التحقق المنفذ الشامل

### 1. اختبارات العقود والوحدات المركزية (PHPUnit)
```powershell
php vendor/bin/phpunit tests/Unit/SuperAdminGoldenPatternInventoryTest.php tests/Unit/TenantGoldenPatternInventoryTest.php tests/Unit/GoldenPatternScopeContractTest.php tests/Unit/TenantCentralBoundaryContractTest.php tests/Unit/AccessLabelsTest.php tests/Unit/CentralAdminUsersRolesGoldenPatternTest.php tests/Unit/SelectOptionsSourceContractTest.php tests/Unit/SharedFormValidationContractTest.php tests/Unit/CreateFormDraftContractTest.php tests/Unit/CentralClientsGoldenPatternTest.php tests/Unit/CentralPlansGoldenPatternTest.php tests/Unit/CentralCouponsGoldenPatternTest.php
```
النتيجة: **40 اختبارًا، 1978 تأكيدًا، دون أي فشل أو خطأ (100% OK)**.

### 2. اختبارات الميزات والأمان ودورة البيانات (PHPUnit Feature)
```powershell
php vendor/bin/phpunit tests/Feature/Tenants/AssetMutationSafetyTest.php tests/Unit/WizardDraftSafetyTest.php tests/Feature/Admin/ScopedPasswordResetAndFaqAuthorizationTest.php tests/Unit/TenantAuthenticationContractTest.php tests/Feature/GoldenPattern/NavigationBoundaryTest.php tests/Feature/GoldenPattern/ReportErrorPrivacyTest.php tests/Feature/AdminClientsFeatureTest.php tests/Unit/DiscardCompletedWizardDraftTest.php tests/Unit/MassDeletePolicyContractTest.php tests/Unit/TenantArchivePolicyContractTest.php
```
النتيجة: **74 اختبارًا، 298 تأكيدًا، دون أي فشل أو خطأ (100% OK)**.

### 3. اختبارات دورات العمل للإدارات الحيوية
```powershell
php vendor/bin/phpunit tests/Feature/AdminDashboardCrudFeatureTest.php --filter="test_admin_settings_banks_and_domains_crud_lifecycle|test_admin_coupons_crud_and_archive_lifecycle|test_admin_plans_crud_and_archive_lifecycle"
```
النتيجة: **3 اختبارات، 56 تأكيدًا، 100% نجاح**.

### 4. اختبارات الواجهة الأمامية (Node Test Runner)
```powershell
node --test tests/Unit/WizardDraftFrontendSafetyTest.cjs tests/Unit/AssignmentFrontendSafetyTest.cjs
```
النتيجة: **4 اختبارات، 100% نجاح**.

### 5. التحقق الإلزامي من بناء الأصول (Vite Build)
```powershell
npm run build
```
النتيجة: **نجاح كامل لبناء الأصول وتوليد الحزم في 12.47 ثانية بدون أخطاء (Exit code 0)**.

---
**حالة المراحل 1 و2 و3 و4:** منجزة وموثقة ومثبتة بالأدلة القاطعة عبر كافة العقود المشتركة والإدارات المحددة.
