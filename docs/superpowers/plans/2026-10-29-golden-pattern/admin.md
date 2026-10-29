# خطة مطابقة إدارات Admin مع Golden Pattern

> هذا الملحق جزء من خطة 29 أكتوبر 2026. أريد تنفيذ الأعمال التالية إدارة بإدارة، مع الحفاظ على السلوك الصحيح الموجود، ولا أعتبر حالة `Compliant` القديمة دليلًا على إغلاق المراجعة الجديدة.

## أساس المقارنة وحدودها

- قارنت المسارات المحملة فعليًا بواسطة `php artisan route:list --json`، ثم تتبعت Controllers وRequests وPolicies وActions/Services وDataTables وBlade والاختبارات المرتبطة. تفاصيل كل endpoint موجودة في `route-inventory.json` وملحق التغطية بالخطة الرئيسية.
- أتعامل مع `admin.clients.*` و`admin.companies.*` كواجهتين للإدارة نفسها؛ كلاهما يتجه إلى `Admin/Clients/ClientController`، ولا أعدهما إدارتين.
- أراجع الإشعارات والمسودات المشتركة في الملحق المشترك S، وأراجع هنا استهلاك الإدارات لهما.
- المقارنة الحالية فحص للكود والعقود وليست اعتمادًا بصريًا داخل متصفح مسجل الدخول. كل بند يستلزم رفع ملفات أو معاملات أو صلاحيات فعلية أو عزل قاعدة بيانات يبقى بحاجة لاختبار تشغيل.
- أريد قاعدة اختبار معزولة قبل أي Feature test؛ إعداد `phpunit.xml` الحالي يشير إلى MySQL وقاعدة `etmam_levan`. لن أشغل اختبارات `RefreshDatabase` أو migrations على قاعدة العمل.
- كل ملف مشار إليه أدناه موجود ما لم أكتب أمامه «إنشاء». لا أنشئ طبقة أو ملفًا إذا كان `not_applicable` مبررًا في بطاقة الإدارة.
- أعتمد أولوية `AGENTS.md` عند تعارض الصياغة مع Golden Pattern: جداول علاقات Show يجوز أن تستخدم `table-hover` وفق عقد Show الصريح، بينما بقية الجداول تستخدم `table table-striped table-bordered w-100`. أوثق هذا الاستثناء بدل اعتباره فجوة في كل Show.

## نتيجة التحقق التي سأبني عليها

شغلت أثناء إعداد الخطة فقط الاختبارات التي تمتد من `PHPUnit\Framework\TestCase` دون تهيئة قاعدة بيانات:

```powershell
php vendor/bin/phpunit tests/Unit/SuperAdminGoldenPatternInventoryTest.php tests/Unit/Admin/PublicRequestsGoldenPatternTest.php
```

النتيجة: **7 tests، 579 assertions، 4 failures**، exit code 1. اثنان متعلقان ببطاقة طلبات التجربة التي تستخدم حقولًا مختلفة و`Status: implemented`، واثنان يتوقعان نص `type="date"` مباشرة في صفحات تستخدم مكون `x-filter.date`. أريد تصحيح اختبار العقد ليفحص مخرجات المكون وعقد الفلترة، بدل استبدال المكون المشترك بعناصر محلية لإرضاء اختبار نصي قديم. لم أشغل Feature tests أو build؛ هذه خطة ولم أغير assets.

## مسار تنفيذ ثابت لكل بطاقة

أطبق الخطوات التالية مع البنود الخاصة بكل إدارة أدناه:

1. أكتب اختبارًا يفشل يثبت الفجوة المسماة، وأشغله على قاعدة الاختبار المعزولة إن احتاج DB.
2. أعدل أصغر مجموعة ملفات تحقق العقد المطلوب، وأحافظ على أسماء routes وpermissions ومفاتيح البيانات إلا في migration مقصودة موثقة.
3. أشغل الاختبار المركز والعقود الأفقية المتأثرة. إذا عدلت JS/CSS/SCSS أشغل `npm run build`.
4. أفتح صفحات الإدارة بصلاحية كاملة ومحدودة، وأراجع desktop/mobile وRTL وحالات empty/error/loading و422 و403.
5. أحدث بطاقة الإدارة بمسارات الملفات والأدلة الجديدة، وأحذف وصف الفجوة فقط بعد نجاح التحقق؛ ثم أعمل commit مستقل يخص الإدارة.

## A01 — لوحة القيادة المركزية

**النطاق:** `admin.dashboard` و`admin.dashboard.platform-activity`؛ صفحة `resources/views/admin/dashboard/index.blade.php`، وقراءة نشاط المنصة عبر GET.

**الموجود الذي سأحافظ عليه:** `app/Http/Controllers/Admin/Dashboard/DashboardController.php` نحيف ويستخدم `app/Services/Admin/Dashboard/DashboardService.php`، وصلاحية `dashboard.main` و`app/Http/Requests/Admin/Dashboard/PlatformActivityRequest.php` موجودان. الدليل: Controller:11–32. لن أفرض CRUD أو drafts أو archive على صفحة مؤشرات.

**المطلوب — P2، بعد S الخاص بالتصميم والإشعارات:**

- [ ] أراجع تجميع كل KPI والرسم من الخدمة، وحدود الفترة الزمنية والكاش ووقت انتهاء الاشتراك؛ أضيف tests لحدود بداية/نهاية اليوم وغياب البيانات وتوافق البطاقات مع المصدر.
- [ ] أوثق عقد استجابة النشاط الحالية `data` وأثبت استهلاك الواجهة له قبل إضافة envelope جديد؛ الصفحة GET وليست mutation، فلا أغير الاستجابة لمجرد اختلاف شكلي عن mutation contract.
- [ ] أطبق المكونات والألوان والهوية الموحدة في الصفحة وأربط inbox المشترك دون إنشاء implementation إشعارات آخر.
- [ ] أضيف فحص 403 للمستخدم بلا `dashboard.main`، واختبار نطاق غير مسموح يرجع 422، وفحص cache invalidation بعد العملية التي تغير المؤشرات.
- [ ] أراجع `tests/Unit/Admin/DashboardPlatformActivityContractTest.php` و`tests/Feature/AdminVisualReviewTest.php` و`tests/Feature/AdminDashboardCrudFeatureTest.php`؛ لا أعتبر عدد البطاقات الثابت هدفًا إذا اختلفت مؤشرات المجال.

**القبول:** صحة الأرقام والفترة والصلاحيات مثبتة، ولا queries أو logic جديدة في Blade، وتطابق مرئي مع المكونات المشتركة.

## A02 — المصادقة وإعدادات 2FA وكلمة المرور

**النطاق:** `admin.login` وPOST `admin.` و`admin.logout` و`admin.two-factor.*`، وكذلك `admin.password.store` الذي يشير حاليًا إلى `Tenants/Auth/NewPasswordController@updatePassword`، ومسار إعادة تعيين كلمة المرور المرتبط به.

**الملفات:** `app/Http/Controllers/Admin/Auth/{AdminAuthController,AdminTwoFactorChallengeController,AdminTwoFactorSettingsController}.php`، `app/Http/Requests/Admin/Auth/`، `app/Actions/Auth/{ResolveTwoFactorLogin,PersistAuthenticationStateAction}.php`، `resources/views/admin/auth/{login,two-factor-challenge,two-factor-settings}.blade.php`، `routes/admin.php`.

**الفجوة المؤكدة:** `AdminTwoFactorChallengeController::cancel` يمسح حالة الجلسة عبر GET في Controller:49–55، بينما GET يجب أن يبقى للقراءة. يوجد ربط Admin password إلى Tenant Controller في `routes/admin.php:31,33,67`؛ هذا يحتاج تدفق مركزي صريح بدل إخفائه تحت اسم route مركزي. أوثق هل صفحة reset الحالية تابعة للـAdmin فعلًا قبل تغيير الربط.

**المطلوب — P0 للحدود وP1 لباقي التدفق:**

- [ ] أنشئ تدفق Admin password صريحًا بـRequest/action وguard/password broker مركزيين؛ أحافظ على route names وروابط البريد أو أوثق نافذة ترحيلها.
- [ ] أحول إلغاء challenge إلى POST بعقد CSRF وRequest مخصص، وأعدل روابط/أزرار الإلغاء واختبارات method؛ GET القديم لا يعدل الجلسة.
- [ ] أميز authentication requests العامة عن mutations الإعدادات المحمية؛ لا أضيف permissions إدارية إلى login العام ولا drafts إلى login/OTP/reset.
- [ ] أثبت 2FA forced mode، تقييد محاولات challenge، single-use recovery code، تجديد الجلسة بعد الدخول، وعدم كشف secret/recovery codes لمستخدم آخر أو cache عام.
- [ ] أوحد JSON الخاص بـenable/confirm/disable/recovery وفق العقد المشترك مع تحديث المستهلك في التغيير نفسه، وأبقي استجابات QR منفصلة موثقة.
- [ ] أراجع مكونات حقول auth وvalidation endpoint الفعلي وRTL، مع عدم إنشاء stepper أو review وهميين.

**التحقق:** `tests/Feature/AdminAuthenticationValidationFeatureTest.php` وauth cases داخل `tests/Feature/AdminDashboardCrudFeatureTest.php`، ثم tests جديدة لفصل Admin/Tenant وإلغاء challenge بطريقة غير GET.

**القبول:** لا يعيد رابط Admin ضبط كلمة مرور مستخدم Tenant، ولا mutation عبر GET، ولا تسرب أسرار، وتعمل التدفقات العامة والمحمية بالـguard الصحيح.

## A03 — مستخدمو الإدارة المركزية والملف الشخصي

**النطاق:** `admin.users.*` و`admin.archives.toggle-status`؛ صفحات `resources/views/admin/users/{index,create,edit,show,action}.blade.php` والـpartial `partials/_form.blade.php`.

**الموجود:** الفورم مشترك ويستخدم `$is_edit`، ومسودة `admin.user-create` موجودة، وDataTable يعرض الصورة والسويتش. Controller `app/Http/Controllers/Admin/AdminMangment/Users/AdminUserController.php` يستخدم resource policy وtyped Store/Update/BulkDelete/uniqueness requests؛ الخدمة `app/Services/Admin/AdminMangment/Users/UserService.php` قائمة. الدليل: `users/partials/_form:15–28,62–63` و`users/create:22` و`users/edit:22`.

**المطلوب — P1، بعد S للتحقق والتبديل:**

- [ ] أحافظ على الفورم المشترك وأراجع single-step rendering وإخفاء المسودة في Edit واستعادة الصورة والملفات وحذف المسودة بعد النجاح من السيرفر.
- [ ] أستبدل Request المصادقة العام في toggle/delete بعقد العملية؛ أربط نشاط المستخدم بالـmethod المشتركة مع حماية SuperAdmin ومنع تعطيل/حذف الحساب المحمي.
- [ ] أراجع `showProfile` وإسناد الدور كعمليات خاصة لا يغطيها `authorizeResource` تلقائيًا؛ permission + record policy + admin guard لكل واحدة.
- [ ] أثبت عمل `bulkDelete` وendpoint الحذف الجماعي العام بالقواعد نفسها، وPolicy لكل row، وفشل transaction عند وجود سجل محمي وفق عقد واضح.
- [ ] أوحد صورة المستخدم وfallback والـShow وملفاته مع المكونات المشتركة، وأحافظ على avatar storage cleanup عند الاستبدال والحذف النهائي.

**الملفات والاختبارات:** Controller والخدمة و`app/DataTables/Admin/AdminMangment/Users/AdminUserDataTable.php` وRequests تحت `Admin/AdminMangment/Users` وPolicy المسجلة في `AuthServiceProvider`؛ أوسع `tests/Feature/AdminDashboardCrudFeatureTest.php` و`tests/Unit/CentralAdminUsersRolesGoldenPatternTest.php` للـdraft الكامل وspecialized operations.

**القبول:** CRUD والملف الشخصي والإسناد والأرشيف والحذف الجماعي تحترم سجل المستخدم وحمايات SuperAdmin، وتنجح الاستجابات HTML وAJAX.

## A04 — الأدوار وإسناد صلاحيات Admin

**النطاق:** `admin.roles.*` بما فيها `get-user-roles` و`update-user-role`؛ صفحات `resources/views/admin/roles/{index,create,edit,action}.blade.php` و`partials/_form.blade.php`.

**الموجود:** `app/Http/Controllers/Admin/AdminMangment/Roles/AdminRoleController.php` و`app/Services/Admin/AdminMangment/Role/RoleService.php` و`app/DataTables/Admin/AdminMangment/Roles/AdminRoleDataTable.php`، والفورم المشترك ومسودة الإنشاء موجودان. الصلاحيات تعرض بالعربية مع الاحتفاظ بالمفاتيح الإنجليزية.

**الفجوة المؤكدة:** Controller:153,157 يعرض `$e->getMessage()` مباشرة في JSON/flash.

**المطلوب — P1، بعد A03 وعقد الأخطاء S:**

- [ ] أفصل أخطاء المجال المسموح عرضها عن الأخطاء غير المتوقعة، وأسجل التفاصيل داخليًا مع رسالة عربية عامة للمستخدم.
- [ ] أثبت أن Role وPermission وUser من guard `admin` فقط؛ أختبر إرسال role من `tenant` أو ID مستخدم خارج العقد وحماية الدور الرئيسي.
- [ ] أراجع صلاحية record policy على `getUserRoles/updateUserRoles` بجانب Requests الحالية `AdminStoreRoleRequest/AdminUpdateRoleRequest/AdminUpdateUserRoleRequest`، وأخصص delete Request بدل `AdminAuthenticatedRequest`.
- [ ] أراجع حفظ واسترجاع مصفوفة permissions في المسودة، والتطابق بين Create/Edit، وcheckbox groups عبر مكون مشترك وعقد validation كامل.
- [ ] أثبت حذف role المرتبط وفق قاعدة المجال دون إسقاط الصلاحيات بصمت، وأختبر HTML redirect وAJAX JSON.

**التحقق:** `tests/Unit/CentralAdminUsersRolesGoldenPatternTest.php` وroles lifecycle في `tests/Feature/AdminDashboardCrudFeatureTest.php` مع 403/422 وحالات guard الخاطئ.

## A05 — العملاء وحسابات الشركات/المستأجرين

**النطاق:** `admin.clients.*` وaliases `admin.companies.*`؛ index/create/edit/show و5 uniqueness lookups وplan-duration وCRUD. Controller الفعلي `app/Http/Controllers/Admin/Clients/ClientController.php`، والخدمة `app/Services/Admin/Clients/ClientService.php` التي تتكامل مع بنية `CompanyService`.

**الفجوات المؤكدة:** `resources/views/admin/clients/create.blade.php:141` يعطل next-button؛ Create:66–245 أربع خطوات بينما Edit:47–117 خطوتان دون Review؛ كلاهما يملك form markup مستقلًا. Controller:22 يستورد Tenant User Model، و`checkAdminEmailUnique/checkAdminPhoneUnique` يستعلمانه مباشرة؛ أريد scope صريحًا لهذا الفحص من المركزي، ولا أفترض أن المستخدم الحالي مهيأ داخل أي Tenant DB.

**المطلوب — P0 للحدود وP1 للنموذج، بعد A06 وS:**

- [ ] أنشئ `resources/views/admin/clients/partials/_form.blade.php`، وأنقل Create/Edit إليه باستخدام `$is_edit`، وأوحد خطوات بيانات العميل/المسؤول/الاشتراك/المراجعة.
- [ ] أحدد الحقول غير القابلة للتعديل وفق `UpdateClientRequest`؛ تظهر readonly/disabled مع hidden values اللازمة، ولا أجعل التطابق البصري يغير عقد تعديل الاشتراك أو مسؤول العميل بلا صلاحية مستقلة.
- [ ] أزيل تعطيل next-button في Create؛ يتم التحقق عند الضغط من حقول الخطوة فقط، مع منع الطلب المتكرر داخليًا.
- [ ] أنقل options والuniqueness queries إلى service/gateway واضح يحدد central identity ومنطق البريد والهاتف على مستوى النظام؛ أختبر مستأجرين لهما نفس local IDs وانعدام tenant context أثناء طلب Admin.
- [ ] أفصل العملية المركبة لإنشاء client + tenant provisioning + admin + subscription/files إلى Action ذرية/قابلة للاستئناف عند تعدد موارد DB أو filesystem؛ أحافظ على التفعيل الفوري والتجريبي وعدم إنشاء حسابات مكررة عند retry.
- [ ] أحافظ على `admin.client-create` وأثبت استعادة البيانات والعلاقات وخيارات الباقة والملفات، وعدم إسقاط المسودة عند فشل provisioning.
- [ ] أوحد Show للهوية والمرفقات والعلاقات؛ الجداول التابعة تستخدم التصميم المشترك وحدود pagination.
- [ ] أوثق aliases بمالك وموعد مراجعة وإزالة، وأحافظ عليها إلى أن تتحدث الروابط والمستهلكات بأمان؛ لا أحذفها ضمن إصلاح شكلي.
- [ ] أحدث بطاقة `companies-tenants.md` لتعكس `Clients` الفعلية، وأصل نطاق الأرشيف A18 بالـPolicy نفسها بدل بقاء التسميات القديمة دليلًا مضللًا.

**الملفات:** Requests في `app/Http/Requests/Admin/Clients/`، `app/DataTables/Admin/Clients/ClientsDataTable.php`، views `resources/views/admin/clients/`، JS الفعلي الذي تستورده الصفحات، وPolicy المرتبطة بـ`App/Models/Admin/Client` في `app/Providers/AuthServiceProvider.php`.

**التحقق:** `tests/Feature/AdminClientsFeatureTest.php` وclient/company cases في `tests/Feature/AdminDashboardCrudFeatureTest.php` و`tests/Unit/CentralClientsGoldenPatternTest.php`، مع parity للـaliases واختبار provisioning failure/retry ودورة draft حقيقية.

**القبول:** Create/Edit لهما هيكل وخطوات موحدة دون تغيير عقد العمل المقصود؛ لا Tenant query مبهم من المركزي؛ ولا client/tenant/user/file يتكرر أو يتيتم عند الفشل.

## A06 — الباقات والتسعير والحدود

**النطاق:** `admin.plans.*`، بما فيها `details/show` و`reorder/update-sort-order` aliases و`toggle-active`؛ views `resources/views/admin/plans/`.

**الفجوات المؤكدة:** Create/Edit مستقلان؛ `create.blade.php:32,265` يحوي عمود `col-lg-9` ومعاينة `col-lg-3`، وCreate:175 وEdit:112,186 يخصصان نصوص الأزرار. dynamic features inputs خام في Create:88,273 وEdit:93,308. Controller:144 يعرض exception message.

**المطلوب — P1، قبل A05/A07:**

- [ ] أنشئ `resources/views/admin/plans/partials/_form.blade.php` وأستخدم `$is_edit`؛ أعرض الخطوات بعرض كامل، وأضع المعاينة المطلوبة داخل Review في شبكة متوازنة، مع الحفاظ على package-card component المعتمد.
- [ ] أستبدل raw feature fields بمكون/مجموعة حقول مشتركة تدعم validation لأسماء `features[index]` وإعادة الفهرسة، وأختبر مسودات المصفوفة وإضافة/حذف البنود.
- [ ] أزيل كل `text=` محلي لأزرار wizard وأحافظ على النصوص والاتجاهات الافتراضية.
- [ ] أراجع `app/Http/Requests/Admin/Plans/PlanRequest.php` لإبقاء اختلافات Store/Update واضحة واختبار uniqueness ignore؛ إذا لم يكن عقدهما واضحًا أفصلهما دون تكرار rules.
- [ ] أخصص Requests لتبديل النشاط والحذف، وأربط toggle بالـmethod العامة، وأحافظ على `PlanReorderRequest` ومصفوفة IDs المسموحة لكل عملية إعادة ترتيب.
- [ ] أنقل عمليات الكتابة المركبة من `app/Services/Admin/Plans/PlanService.php` إلى Actions فقط حيث transaction/relations تستدعي ذلك؛ أبقي الإحصائيات والقراءات في Service.
- [ ] أراجع Show والجداول التابعة والصورة والمرفقات، وأمنع حذف باقة مرتبطة بسجل مالي/تاريخي وفق القاعدة الموجودة واختبارها.
- [ ] أسجل مالكًا وموعدًا للـaliases، وأستبدل exception flash برسالة آمنة.

**التحقق:** `tests/Unit/CentralPlansGoldenPatternTest.php` وplan lifecycle داخل `tests/Feature/AdminDashboardCrudFeatureTest.php` و`tests/Feature/SubscriptionDatesAndPeriodsLifecycleTest.php`؛ أضيف tests للform parity وdraft وlimits mode/billing cycle عند التعديل.

## A07 — الاشتراكات المركزية

**النطاق:** `admin.subscriptions.index/create/store/destroy/toggle-status/plan-duration/company-details`؛ views `resources/views/admin/subscriptions/{index,create,action}.blade.php`.

**الموجود:** `app/Http/Controllers/Admin/Subscriptions/SubscriptionController.php` يستخدم resource Policy و`StoreSubscriptionRequest` و`app/Services/Admin/Subscriptions/AdminSubscriptionService.php`؛ wizard create يحتوي draft وReview. لا توجد Edit/Show مستقلة في route contract الحالي، ولذلك أوثقهما N/A ولا أضيفهما.

**الفجوات المؤكدة:** Controller:111 يسرب exception message؛ toggle/destroy يستخدمان `AdminAuthenticatedRequest`. `app/DataTables/Admin/Subscriptions/SubscriptionDataTable.php:102` يبني رابط Tenant dashboard بـ`url('/'.$row->tenant_id.'/dashboard')` بدل route/gateway موثق؛ يجب تصحيح renderer القائم أو إزالة غير المستخدم بعد إثبات عدم دخوله للcolumns، لا الادعاء بأن الرابط المعطل ظاهر الآن.

**المطلوب — P1، بعد A05/A06 وS:**

- [ ] أخصص Request وPolicy للـtoggle والحذف؛ أفرق بين نشاط boolean وانتقال workflow ولا أمرر subscription workflow إلى method نشاط عامة بلا invariant.
- [ ] أنقل query خيارات الشركات من Controller `create` إلى Service، وأثبت ownership وربط الشركة والباقة وصلاحية الاشتراك داخل transaction عند التزامن.
- [ ] أستخرج إنشاء الاشتراك/الفاتورة/الفترات إلى Action حيث توجد writes مركبة، مع locking ومنع duplicate creation وcache invalidation بعد النجاح.
- [ ] أوحد عرض subscription_number مع copyable-code، وأستخدم Tenant route gateway عند إتاحة رابط لوحة العميل.
- [ ] أراجع استعادة draft لكل الخطوات وحذفها بعد Store الناجح فقط؛ أضع form markup في `partials/_form.blade.php` لاستخدام بنية الإدارة الموحدة دون اختراع Edit غير موجودة.
- [ ] أراجع فلاتر DataTable من حيث enums/dates/IDs عبر عقد index/filter Request، وأمنع input غير المعتمد من تغيير query.

**التحقق:** subscription/bill lifecycle في `tests/Feature/AdminDashboardCrudFeatureTest.php` و`tests/Feature/SubscriptionDatesAndPeriodsLifecycleTest.php`؛ اختبارات خاصة لplan-duration/company-details permissions و422 وretry والكاش.

## A08 — مراجعة طلبات الاشتراك والموافقات

**النطاق:** `admin.subscriptionManage.pending/expiring/expired/accept/reject/mass-accept/mass-reject`؛ صفحة مشتركة `resources/views/admin/subscription-management/index.blade.php` و`action.blade.php` و`partials/confirm_modal.blade.php`.

**الموجود:** صلاحيات routes منفصلة للعرض/الموافقة/الرفض؛ الخدمة `app/Services/Admin/SubscriptionManagement/SubscriptionManagementService.php` تنفذ transactions، وbulk typed requests موجودة. لن أتعامل مع methods غير المربوطة routes في Controller كصفحات نشطة.

**الفجوات المؤكدة:** `app/Http/Controllers/Admin/SubscriptionManagement/SubscriptionManagementController.php:269,298,322,343` يضيف exception message إلى رسائل AJAX؛ rejection textarea في `partials/confirm_modal.blade.php:14` خام بلا reusable form field.

**المطلوب — P0 للسلوك المالي وP1 للواجهة، بعد A07:**

- [ ] أستبدل rejection control بالمكون المشترك وأعلن Request الفعلي للنموذج؛ أوحد 422/errors وloading وعدم تكرار modal implementation محليًا.
- [ ] أراجع فحص Policy لكل subscription في single/bulk، وقيد pending داخل transaction مع lock، وأختبر قبولين متزامنين وطلب رفض بعد قبول.
- [ ] أوثق all-or-nothing أو partial-failure contract للحالتين الجماعتين؛ النتيجة تعرض IDs/عدد الناجح والمرفوض بما يطابق القرار، ولا يوجد نجاح مبهم.
- [ ] أزيل عرض exception الداخلي وأحافظ على context آمن في logs؛ لا أستخدم 200 success عند فشل بعض السجلات دون عقد معلن.
- [ ] أوحد فلاتر القوائم الثلاث وتنسيق DataTable والأزرار، وأثبت أن كل تبويب يطبق حالته الحقيقية ولا يكتفي بتغيير العنوان.

**التحقق:** `tests/Feature/AdminSubscriptionManagementFeatureTest.php` مع scenarios إضافية للصلاحيات المختلطة وstale state وtransaction rollback وعدم تكرار الفاتورة/الإشعار.

## A09 — الفواتير والمعاملات المالية

**النطاق:** `admin.bills.index/show/print/verify/reject/reactivate` و`admin.archives.bills.show`؛ views `resources/views/admin/bills/{index,show,print,action}.blade.php`.

**الموجود:** Controller `app/Http/Controllers/Admin/Bills/AdminBillsController.php` يطبق resource Policy وverify/reject/reactivate abilities، ويستخدم `app/Services/Shared/Bills/BillService.php` و`app/Services/Shared/Subscription/SubscriptionService.php`. print وثيقة مالية وليست Show عادية؛ أحافظ على متطلبات طباعتها.

**الفجوات المؤكدة:** Controller:130 يعرض exception message؛ verify/reactivate يستخدمان Request مصادقة عامة. `bills/show.blade.php:286` يحتوي rejection textarea خام.

**المطلوب — P0، بعد A07/A08:**

- [ ] أخصص Requests للموافقة وإعادة التنشيط، وأراجع RejectBillRequest لتوحيد authorization/messages/attributes والبيانات المعتمدة.
- [ ] أنقل توجيه العمليات حسب نوع الفاتورة إلى use-case service/action بدل match أعمال داخل Controller، وأوثق أنواع الفواتير المدعومة فعلًا؛ لا أترك الفروع المعلقة كادعاء دعم للrenewal/license.
- [ ] أثبت state machine والمبالغ والعملة وحدود rounding ورفض التكرار/idempotency، مع قفل الفاتورة وربط دفع واحد بسجل واحد.
- [ ] أعرض إيصال الدفع وكل الملفات عبر file-card ومسار تنزيل مصرح به، وأستخدم مكون rejection المشترك.
- [ ] أحافظ على Print layout كوثيقة، وأختبر عدم كشف بيانات مالية لمجرد امتلاك ID وأرشفة الفاتورة وعدم الحذف النهائي إن كانت مرجعًا ماليًا.

**التحقق:** bill cases في `tests/Feature/AdminDashboardCrudFeatureTest.php` و`tests/Feature/SubscriptionDatesAndPeriodsLifecycleTest.php`، واختبارات متزامنة/مكررة للموافقة والرفض وإعادة التنشيط وbrowser/AJAX.

## A10 — الكوبونات

**النطاق:** `admin.coupons.index/create/store/edit/update/destroy/toggle-status/details`؛ views `resources/views/admin/coupons/`، Controller `app/Http/Controllers/Admin/Coupons/AdminCouponController.php`، Service `app/Services/Admin/Coupons/CouponService.php`، DataTable `app/DataTables/Admin/Coupons/CouponDataTable.php`.

**الفجوات المؤكدة:** Create:32 وEdit:37 فورمان مستقلان؛ toggle يعتمد على `AdminAuthenticatedRequest` ويقرأ status خارج Request متخصص؛ تفاصيل الاستخدام تعتمد queries داخل Controller:143–150.

**المطلوب — P1، بعد S وA06:**

- [ ] أنشئ `resources/views/admin/coupons/partials/_form.blade.php` وأستخدم `$is_edit`؛ أبقي النموذج single-card وأخفي header الخطوة الواحدة.
- [ ] أخصص status/delete Requests وأربط نشاط الكوبون بالـmethod المشتركة؛ أوثق status الهدف المطلوب بدل الاعتماد على double-toggle عند retry.
- [ ] أنقل قراءة تفاصيل الاستخدام والعلاقات والإحصائيات إلى الخدمة، وأحمي details بـPolicy السجل وأطبق pagination عند تضخم العلاقات.
- [ ] أفصل Store/Update validation حيث تختلف uniqueness أو قواعد المالك/الباقات مع reuse rules؛ أختبر expiration والحد الأقصى والاستخدام المتزامن وفق domain الموجود.
- [ ] أثبت draft save/restore/purge لقوائم الباقات والمستخدمين، وأراجع show/details والأرشيف والحذف الجماعي والردود.

**التحقق:** coupon lifecycle في `tests/Feature/AdminDashboardCrudFeatureTest.php` مع 403/422 وتفرد الكود وdraft وtoggle retry وحدود الاستخدام.

## A11 — التذاكر والدعم المركزي

**النطاق:** `admin.tickets.index/show/reply/updateStatus/toggleService`؛ pages `resources/views/admin/tickets/{index,show}.blade.php`، Controller `app/Http/Controllers/Admin/Tickets/AdminTicketController.php` وService `app/Services/Admin/Tickets/AdminTicketService.php` وDataTable `app/DataTables/Admin/Tickets/AdminTicketsDataTable.php`.

**الفجوات المؤكدة:** Controller:63 يعرض تفاصيل exception، و:88 يمررها إلى view. capability middleware وtyped Requests موجودة؛ هذا لا يغني عن إثبات policy للسجل والانتقالات.

**المطلوب — P1، بعد S وA05:**

- [ ] أوحد الأخطاء العامة وأفصل نتيجة provider غير المتاح عن التذكرة غير الموجودة، مع timeout/retry/idempotency في service/gateway وإشعار قابل للفهم.
- [ ] أثبت record policy على show/reply/updateStatus؛ رقم التذكرة وحده لا يمنح حق الوصول، وأختبر تذاكر عملاء مختلفين والمرفقات التابعة لكل reply.
- [ ] أحافظ على `system.settings` لتفعيل الخدمة و`tickets.reply` للرد/الحالة، وأمنع الطلبات من تجاوز الصلاحية بإرسال route يدوي.
- [ ] أراجع model/enum لحالات التذكرة وأبقيها workflow badges؛ لا أحولها إلى activity switch.
- [ ] أوحد Reply fields والملفات بـshared controls/file-card وتنزيلات مصرح بها، وأضع side effects بعد commit مع منع إرسال رد مكرر عند retry.
- [ ] أوثق عدم وجود Create مستقلة في Admin: opening ticket هنا N/A، فلا أضيف زر إنشاء أو مسودة غير موجودين في المجال.

**التحقق:** ticket cases في `tests/Feature/AdminDashboardCrudFeatureTest.php` مع provider mocks مضبوطة على حدود التكامل، واختبارات تفويض/ملفات/تكرار مستقلة.

## A12 — طلبات التواصل العامة داخل Admin

**النطاق:** `admin.contact-requests.index/show/reply`؛ `resources/views/admin/public-requests/contact/{index,show}.blade.php` و`app/Http/Controllers/Admin/PublicRequests/ContactRequestController.php` و`app/Actions/Admin/PublicRequests/ReplyToContactRequestAction.php` و`app/DataTables/Admin/PublicRequests/ContactRequestsDataTable.php` و`ReplyContactRequest` و`ContactRequestPolicy`.

**الموجود:** الصلاحية والـPolicy وAction والـenum موجودة؛ index يبدأ بثلاثة مؤشرات طبيعية وفلاتر مشتركة، لذلك لا أفرض خمس بطاقات أو create form داخل Admin.

**المطلوب — P1، بعد S:**

- [ ] أنقل حساب الإحصائيات وتجميع خيارات الحالة من Controller:27–39 إلى قراءة الخدمة، مع select/eager loading مناسبين.
- [ ] أثبت أن reply للطلب المسموح فقط، وأن الطلب الذي تم الرد عليه يعالج وفق قاعدة المجال داخل transaction، مع منع side effect مكرر.
- [ ] أراجع Show والرد باستخدام shared form contract و422 وعرض أسماء المرسل escaped، وأوثق Create/Edit/Archive/Drafts كـN/A في لوحة Admin لهذه الإدارة.
- [ ] أصلح الاختبار النصي الذي يتوقع `type="date"` محليًا، وأثبت بدلًا منه أن `x-filter.date` يرندر date input ويحمل filter key الصحيح ويرسل فترة معتمدة.
- [ ] أحدث البطاقة من Gap القديم فقط بعد أدلة جديدة، دون افتراض أن نقص شهادة قديمة يعني غياب implementation الموجود.

**التحقق:** `tests/Unit/Admin/PublicRequestsGoldenPatternTest.php` و`tests/Unit/Admin/PublicRequestsStatusTest.php`، وإنشاء `tests/Feature/Admin/ContactRequestsGoldenPatternFeatureTest.php` للعرض/الرد/الصلاحيات/التكرار.

## A13 — طلبات التجربة ومراجعة إنشاء الحساب

**النطاق:** `admin.trial-requests.index/show/approve/reject`؛ `resources/views/admin/public-requests/trial/{index,show}.blade.php`، `app/Http/Controllers/Admin/PublicRequests/TrialRequestController.php`، `ApproveTrialRequest/RejectTrialRequest`، `TrialRequestPolicy`، `app/Actions/Admin/PublicRequests/{ApproveTrialRequestAction,RejectTrialRequestAction}.php`.

**الموجود:** Requests وPolicies وActions فعلية، وتدفق provisioning يستهدف تجربة 7 أيام وحسابًا واحدًا ويحتفظ بحالة retry. أعمال Site registration خارج هذا الملحق، وأحتفظ بها كعقد تكامل يجب ألا ينكسر.

**الفجوات المؤكدة:** بطاقة `docs/baselines/superadmin-administration-cards/public-trial-requests.md` لا تتبع schema المطلوب وتستخدم `Status: implemented`؛ اختبارات الجرد تفشل عليها فعلًا. Controller:33–45 يجمع الإحصائيات في HTTP layer.

**المطلوب — P0 لتكامل الحساب وP1 للتوثيق، بعد A05/A07:**

- [ ] أنقل index statistics/options إلى service قراءة وأثبت status enum/filter contract.
- [ ] أراجع approve/reject مع قفل الحالة وownership وidempotency وعدم إمكانية رفض طلب تمت الموافقة عليه أو إنشاء tenant جديد بعد retry.
- [ ] أثبت أن فشل migration/provisioning يبقي الطلب قابلاً للاستئناف، وأن زر الموافقة يعرض فشلًا مفهومًا دون فقد الربط بالسجل المنشأ.
- [ ] أراجع Show ورابط دخول العميل عبر route/gateway مصرح، وأختبر انتهاء التجربة وحد المستخدم والعقار والمزاد والمساحة وفق العقد الفعلي، دون تكرار إدارة صلاحيات في Blade.
- [ ] أعيد كتابة البطاقة بقالب الحقول القياسي وحالة من `Gap/Partial/Compliant` مع N/A واضح للـCreate/Edit/Archive/Draft داخل Admin؛ أزيل شرط الاختبار الذي يفرض Gap على هذه الإدارة للأبد وأربط اعتمادها بأدلة فعلية.

**التحقق:** `tests/Feature/TrialRegistrationFlowTest.php` و`tests/Unit/SuperAdminGoldenPatternInventoryTest.php` و`tests/Unit/Admin/PublicRequestsStatusTest.php`، مع حماية تغييرات المستخدم الحالية في هذه الملفات وعدم استبدالها أثناء التنفيذ.

## A14 — إعدادات المنصة والتكاملات

**النطاق:** `admin.settings.index/update`؛ `resources/views/admin/settings/settings.blade.php` وpartials `general-settings/two-factor-settings/microsoft_settings/etmam-settings`؛ Controller `app/Http/Controllers/Admin/Settings/SettingsController.php` و`app/Http/Requests/Admin/Settings/AdminMainSettingsRequest.php` و`app/Actions/Admin/Settings/UpdatePlatformSettingsAction.php`.

**الموجود:** `system.settings`، Request، Action بـtransaction/lock وfile cleanup موجودون. هذه Update settings وليست Create resource؛ drafts/archive وDataTable N/A.

**المطلوب — P1، بعد S وA02:**

- [ ] أراجع كل tab أن حقوله تستخدم shared controls وتعلن نفس FormRequest مع subset واضح؛ حفظ tab لا يمسح قيم tab آخر بسبب checkbox غير مرسل.
- [ ] أعتمد من `validated()` الحقول والأعلام وactive_tab بعد allow-list، وأزيل القراءات الإضافية غير المثبتة بقواعد Request من action payload.
- [ ] أثبت عدم عرض secrets الحالية في HTML/JSON/log، وسلوك keep/replace/clear الصريح وتحقق الاتصال الخارجي timeout/failure دون كشف credentials.
- [ ] أوحد preview الشعار عبر file-card ورفع/rollback/delete القديم بعد نجاح الحفظ، وأراجع cache invalidation للهوية والإعدادات.
- [ ] أوثق policy الإعدادات القائمة أو سبب N/A للـmodel resource policy مقابل capability واحد محمي على singleton؛ لا أنشئ Policy شكلية بلا قرار تفويض.

**التحقق:** settings cases في `tests/Feature/AdminDashboardCrudFeatureTest.php` مع اختبارات tab partial submit وsecret retention وstorage failure وتغيير 2FA forced settings.

## A15 — إعدادات البنوك

**النطاق:** `admin.settings.banks.*` index/create/store/edit/update/destroy/toggle-status؛ views `resources/views/admin/settings/banks/` و`partials/form.blade.php`.

**الموجود:** `SettingsBankController` وStore/Update Requests وBank Policy و`app/Actions/Admin/Settings/ManageBankAction.php`؛ partial مشترك ودraft metadata موجودان. Action ينظف الملف الجديد عند الفشل ويحذف القديم بعد نجاح الاستبدال.

**الفجوات المؤكدة:** partial يستخدم `$isEdit` المستنتج من model بدل `$is_edit`؛ file:24 يبني `asset('storage/...')` داخل قيمة uploader؛ :39 نص submit محلي. toggle/delete Request مصادقة عامة في Controller.

**المطلوب — P1، بعد S:**

- [ ] أنقل partial إلى `partials/_form.blade.php` وتمرر الصفحتان `$is_edit` صراحة، وأوحد submit/actions/default texts وإخفاء مسودة Edit.
- [ ] أفصل current icon preview عن uploader باستخدام file-card مع keep/replace/remove وفق rules والـPolicy، وأحافظ على cleanup الصحيح الموجود.
- [ ] أخصص status/delete Requests وأستخدم activity method المشتركة؛ أثبت نفس رد السويتش في كل إدارة.
- [ ] أراجع SVG المسموح: MIME/الحجم/المعالجة/طريقة العرض والتنزيل؛ لا أعرض SVG غير موثوق inline، وأثبت سلوك نوع ملف مضلل.
- [ ] أثبت draft استعادة الأيقونة الفعلية وpreview/remove، مع عدم حذفها قبل نجاح الإنشاء وعدم إظهار الخطوات لنموذج البطاقة الواحدة.

**الملفات:** `app/Http/Controllers/Admin/Settings/Bank/SettingsBankController.php` و`app/Http/Requests/Admin/Settings/Bank/` و`app/DataTables/Admin/Setting/Bank/BankDataTable.php` وAction والviews المذكورة.

**التحقق:** bank cases في `tests/Feature/AdminDashboardCrudFeatureTest.php` مع file rollback/replacement/draft و403/422/HTML+AJAX.

## A16 — أسعار امتدادات النطاقات

**النطاق:** `admin.settings.domains.*` index/create/store/edit/update/destroy/toggle-status؛ `SettingsDomainController` و`StoreControlDomainRequest/UpdateControlDomainRequest` و`app/Actions/Admin/Settings/ManageControlDomainAction.php` و`app/DataTables/Admin/Setting/SettingDomainDataTable.php` وviews `resources/views/admin/settings/domains/`.

**الفجوات المؤكدة:** partial `partials/form.blade.php:2` يستخدم `$isEdit`، و:39 نص submit محلي؛ toggle/delete يستخدمان `AdminAuthenticatedRequest`. permission فعلي موحد `domain_extension_prices.settings`؛ لا أستبدله تلقائيًا بkeys غير seeded.

**المطلوب — P1، بعد S وعقد نطاقات Tenant:**

- [ ] أوحد `_form.blade.php/$is_edit` والنصوص وsingle-card وdraft؛ أبقي extension غير قابل للتغيير مع hidden value عند الحاجة للـRequest.
- [ ] أثبت uniqueness/canonical format للامتداد والأسعار والعملة وعدم قبول قيمة سالبة أو إعادة تفسير الفواصل بطريقة خاطئة.
- [ ] أخصص Requests للنشاط والحذف وأستخدم method النشاط المشتركة، وأثبت أن كل طلب يستخدم capability seeded نفسها والـPolicy المسجلة.
- [ ] أراجع أثر تعديل السعر على cache/options الخاصة بـTenant gateway؛ الطلبات المالية السابقة تحتفظ snapshot سعرها ولا يعاد تسعيرها تلقائيًا.
- [ ] أوثق files/archive N/A إن لم يدعمها model؛ لا أضيف رفعًا أو أرشيفًا بلا حاجة مجال.

**التحقق:** domain settings lifecycle في `tests/Feature/AdminDashboardCrudFeatureTest.php` مع readonly payload وdraft واستمرار snapshot وcache invalidation.

## A17 — سجل النشاط المركزي

**النطاق:** `admin.activity-logs.index/show`؛ `app/Http/Controllers/Admin/ActivityLog/AdminActivityLogController.php` و`app/Http/Requests/Admin/ActivityLog/AdminActivityLogIndexRequest.php` و`resources/views/admin/activity-logs/{index,show}.blade.php`.

**الفجوة المؤكدة:** Controller:27–75 يبني query وDataTables/rendering، و:130–173 يطبق الفلاتر والإحصائيات؛ خارج الفصل المطلوب بين HTTP/query/presentation. Request الفلاتر والمفاتيح الإنجليزية وصلاحية `activity_log.view` موجودة.

**المطلوب — P1، بعد S:**

- [ ] أنشئ `app/Services/Admin/ActivityLog/AdminActivityLogService.php` للقراءات والإحصائيات، و`app/DataTables/Admin/ActivityLog/AdminActivityLogDataTable.php` لـquery/columns/rendering/filters، وأنقل منطق العرض القابل للمشاركة إلى presenter المشترك الموجود.
- [ ] أثبت central scope للـActivity model والconnection وcauser/subject polymorphic resolution، وعدم عرض سجلات Tenant بمجرد مساواة numeric ID أو اسم المستخدم.
- [ ] أراجع ترخيص show على السجل نفسه أو أوثق scope singleton/log capability إن كان كل central audit متاحًا لهذه الصلاحية؛ لا أفترض أن permission عامة تكفي دون قرار موثق.
- [ ] أوحد table toolbar والـcolumns والنسخ/التصدير؛ لا أضيف حذف سجل تدقيق أو checkbox mass-delete إلا إذا وجد عقد احتفاظ وصلاحية معتمدين، وإلا أزيل تحديدًا لا يملك عملية فعلية.
- [ ] أختبر rendering لmetadata خبيثة وأخفي الأسرار والtokens وPII غير اللازمة؛ أعرض العلاقات الكبيرة بحدود واضحة.

**التحقق:** activity cases في `tests/Feature/AdminDashboardCrudFeatureTest.php` وإنشاء `tests/Feature/Admin/ActivityLogGoldenPatternFeatureTest.php` لاختبار العزل والفلاتر والتفويض وescaped metadata.

## A18 — الأرشيف المركزي بكل أنواعه

**النطاق:** `admin.archives.*` للمستخدمين والباقات والكوبونات والفواتير والعملاء/tenants؛ bill archived show في A09، وtoggle admin user في A03. أفحص كل endpoint ولا أكتفي بعينة أرشيف واحدة.

**الملفات:** Controllers تحت `app/Http/Controllers/Admin/Archive/{AdminUser,Plan,Coupon,Bill,Tenant}/`؛ DataTables تحت `app/DataTables/Admin/Archive/`؛ views `resources/views/admin/archive/{admin_user,plan,coupon,bill,tenant}/`؛ `app/Actions/Admin/Archive/ManageCentralArchiveAction.php` و`CompanyService/UserService` حيث لهما cleanup خاص.

**الفجوات المؤكدة:** كل restore/forceDelete يستقبل `AdminAuthenticatedRequest`؛ `BillArchiveController:49,58` يرجع redirect فقط، ومثله TenantArchiveController:44,58؛ `TenantArchiveController:39–61` يفوض إلى CompanyService دون استدعاء Policy صريح في هذا Controller، لذلك أراجع التفويض الفعلي داخل الخدمة وأضيف record authorization إن لم يوجد قبل write. لا أصف capability middleware بأنه مفقود؛ هو موجود.

**المطلوب — P0 للفواتير/tenant destruction وP1 للواجهة، بعد إدارات المصدر:**

- [ ] أخصص Restore/ForceDelete Requests لكل resource أو base موحد بعقد operation/resource صريح وPolicy لكل سجل؛ abilities الاسترجاع والحذف النهائي مستقلة عن مجرد العرض/الحذف العادي.
- [ ] أحمّل السجل المحذوف فقط `onlyTrashed` حيث العملية تتطلب ذلك، وأختبر ID نشط وID غير موجود وتعارض unique بعد soft delete.
- [ ] أوحد HTML redirect وAJAX JSON لكل method، بما فيها حالات الفشل، مع Toastr دون JSON خام في browser flow.
- [ ] أراجع retention والعلاقات المالية/history قبل force delete، وتسلسل حذف الملفات والبيانات وtenant database؛ لا ينفذ اختبار حذف حقيقي لمستأجر من قاعدة العمل.
- [ ] أراجع index layout لكل نوع: تنبيهات/فلاتر/جدول فقط، وcopy/excel دون add أو mass delete غير موثق، وأزيل status toggle من الأرشيف إذا لم يكن حالة نشاط مدعومة وظيفيًا وفق عقد المصدر.
- [ ] أثبت أن استعادة client تعيد كل العلاقات المطلوبة أو ترفض بسبب conflict برسالة مجال، وأن فشل file/db cleanup لا ينتج نجاحًا كاذبًا.

**التحقق:** archive lifecycle لكل نوع في `tests/Feature/AdminDashboardCrudFeatureTest.php`، واختبارات مخصصة لكل مورد للـ403/422/JSON/redirect/retention/conflict والملفات.

## A19 — الحذف الجماعي المركزي

**النطاق:** POST `admin.mass-delete` وbulk user endpoint المرتبط في A03؛ `app/Http/Controllers/Admin/DeleteBulk/AdminMassDeleteController.php` و`app/Actions/Admin/MassDelete/DeleteAdminRecordsAction.php` و`MassDeleteRequest` و`config/models.php`.

**الموجود:** Action يعالج Collection داخل central transaction؛ controller يملك allow-list وفحص Model. أراجع فحص Policy الفعلي لكل record ولا أستبدله بإثبات وجود Model في config.

**المطلوب — P0، قبل تفعيل mass delete لأي إدارة جديدة:**

- [ ] أوحد allow-list وقواعد IDs مع config؛ لا أقبل PHP class من العميل، ولا أسمح باسم model tenant تحت guard admin.
- [ ] أثبت Policy لكل row وحماية SuperAdmin والموارد المستخدمة ماليًا، وأعتمد all-or-nothing للـtransaction الحالية مع رسالة واضحة؛ أي قرار partial يحتاج contract واختبارات مستقلين.
- [ ] أوحد FormRequest authorization والـmodel operation mapping دون الاعتماد على مجرد تسجيل الدخول؛ أتأكد أن authorization لا يحدث بعد بدء أي حذف.
- [ ] أوحد delegation/selection/reset على draw في كل index يدعم الحذف، وأراجع عدم بقاء IDs محددة من draw/صفحة سابقة بلا عرض واضح.
- [ ] أختبر mixed permitted/forbidden IDs وduplicate IDs وunknown IDs وempty/oversized list والفشل في منتصف transaction وfile lifecycle للموارد التي تحذف ملفات.

**التحقق:** `tests/Unit/MassDeletePolicyContractTest.php` وbulk cases في `tests/Feature/AdminDashboardCrudFeatureTest.php`؛ لا أكفي باختبار أن زر «حذف المحدد» موجود.

## A20 — قوالب المزادات والمصمم والإصدارات

**النطاق:** `admin.auction-templates.*` index/create/store/show/edit/update/destroy/toggle-status/fields/versions.publish؛ views `resources/views/admin/auction-templates/` مع `resources/views/admin/auction-designs/partials/designer.blade.php`.

**الملفات:** `app/Http/Controllers/Admin/AuctionTemplates/AuctionTemplateController.php`؛ Requests في `app/Http/Requests/Admin/AuctionTemplates/`؛ Policy `app/Policies/Admin/AuctionTemplates/AuctionTemplatePolicy.php`؛ Actions `CreateAuctionTemplateAction/UpdateAuctionTemplateAction/PublishAuctionTemplateVersionAction`؛ Service `app/Services/Admin/AuctionTemplates/AuctionTemplateService.php` ومساعدات schema/assets؛ DataTable `app/DataTables/Admin/AuctionTemplates/AuctionTemplateDataTable.php`.

**الفجوات المؤكدة:** `Controller::publish` لا يحقن FormRequest متخصصًا، و`showFields` لا يغطيه `authorizeResource` تلقائيًا. `AuctionTemplatePolicy` لا يعرّف publish ability؛ **ملكية الإصدار للقالب محمية بالفعل** داخل `PublishAuctionTemplateVersionAction:26` فلا أعيد تسجيلها كثغرة غير موجودة. partial `partials/form.blade.php:4` يستخدم `$isEdit` ويحتوي checkbox محليًا :104. Show:121 يستخدم `table-hover` المسموح لجداول علاقات Show وفق AGENTS؛ سأوثق الاستثناء ولا أطلب تغييره لمجرد اختلافه عن نص Golden العام.

**المطلوب — P0 للنشر وP1 للنموذج، بعد S وقبل اعتماد مخرجات Tenant:**

- [ ] أنشئ `app/Http/Requests/Admin/AuctionTemplates/PublishAuctionTemplateVersionRequest.php`، وأضيف Policy publish مرتبطة بقدرة النشر المعتمدة (أستخدم `auction_templates.edit` الحالية ما لم أقرر permission مستقلة مع seed وترجمة واختبارات).
- [ ] أطبق record authorization صريحًا على publish/fields/toggle، وأحافظ على فحص parent/version داخل Action وأضيف اختبار version من قالب آخر وversion منشور/قديم وحالة متزامنة.
- [ ] أعيد تسمية partial إلى `_form.blade.php` وأمرر `$is_edit` من Create/Edit؛ أوحد checkbox والحقول المعادة الاستخدام بعقد controls، وأبقي أدوات canvas/range داخل designer component عام بعقد موثق بدل التعامل معها كـraw resource fields عادية.
- [ ] أنقل schema normalization والتحقق الأعمال من Controller إلى Request/Action مع عدم تكرار validation؛ أختبر schema خاطئة وخلفيات مختلفة ونوع immutable في Edit.
- [ ] أثبت draft استعادة schema وbackground uploads وmetadata الفعلية، وعدم purge قبل نجاح إنشاء القالب والإصدار، وعدم تسرب ملفات التصميم المركزي إلى Tenant بلا gateway.
- [ ] أوحد attachments/file-card وShow والـversions table والتصميم العام، وأثبت activity method المشتركة دون خلط is_active بحالة published.
- [ ] أراجع publish بعد commit والكاش وimmutable snapshots؛ تعديل القالب لا يغير تقارير مزادات سابقة أو snapshots محفوظة.
- [ ] أنشئ بطاقة إدارة في سجل المطابقة لهذه الإدارة بدل بقائها خارج الـinventory القديم.

**التحقق:** `tests/Feature/AuctionTemplatesFeatureTest.php` و`tests/Feature/Admin/AuctionTemplateSamplesRemovalTest.php` و`tests/Unit/AuctionTemplateFieldDefinitionsTest.php` واختبارات schema/snapshot الحالية؛ أضيف authorization وdraft وfailure cleanup وconcurrent publish.

## A21 — الأسئلة الشائعة

**النطاق:** `admin.faqs.index/create/store/edit/update/destroy`؛ `app/Http/Controllers/Admin/FaqController.php` و`app/Http/Requests/Admin/FaqRequest.php` و`app/Services/Admin/Faqs/FaqService.php` و`resources/views/admin/faqs/{index,form}.blade.php`.

**الفجوات المؤكدة:** `FaqRequest:4` يتطلب `faqs.edit` حتى Store ولا يعرّف `messages()`؛ `FaqController:21` يفرض `faqs.view` على جميع العمليات؛ `faqs/form.blade.php:3` بلا draft أو shared validation metadata؛ `faqs/index.blade.php:7–16` يبدأ بheader وزر إضافة خارج toolbar، ويستخدم table-hover، و:29–34 يعرض activity badges للمحرر. destroy يرجع redirect فقط في Controller:53–57.

**المطلوب — P1، بعد S:**

- [ ] أنشئ `app/Http/Requests/Admin/Faqs/StoreFaqRequest.php` و`UpdateFaqRequest.php` و`UpdateFaqStatusRequest.php` و`DeleteFaqRequest.php` بعقد authorize/rules/messages/attributes، وأعدل Controller لاستخدام capability العملية المناسبة دون فرض view على من يملك create فقط إلا بقرار صلاحيات موثق.
- [ ] أنشئ `resources/views/admin/faqs/{create,edit}.blade.php` و`partials/_form.blade.php` وأمرر `$is_edit`، وأعلن Request الحقيقي وأضيف draft resource key `admin.faq-create` ودورة restore/purge كاملة للنموذج أحادي البطاقة.
- [ ] أنشئ `app/DataTables/Admin/Faqs/FaqDataTable.php` فوق AbstractDataTable، وأنقل القائمة إلى stats/filters/toolbar/table مشتركة؛ أعرض total/active/inactive فقط، وأضيف checkbox/mass-delete إن اعتمدت دعمه وسجلت Faq في config مع policy.
- [ ] أضيف PATCH `admin.faqs.toggle-status` باستخدام عقد النشاط العام وصلاحية edit، وأبقي show/archive N/A إذا لا يوجد احتياج مجال أو SoftDeletes.
- [ ] أثبت وجود Policy مسجلة للـFaq وabilities CRUD، وأوحد HTML/AJAX delete responses وحذف المسودة بعد Store الناجح.
- [ ] أنشئ بطاقة FAQ في الـManifest واختبار جرد يثبت عدم إسقاطها مجددًا من نطاق Admin.

**التحقق:** إنشاء `tests/Feature/Admin/FaqGoldenPatternFeatureTest.php` و`tests/Unit/Admin/FaqPolicyTest.php`: create-only role يستطيع الإنشاء، edit-only لا ينشئ بلا permission، guest/403/422، draft restore/purge، toggle idempotent، delete JSON/redirect، filters/buttons.

## صفحات وملفات قديمة لا أعتبرها إدارات نشطة

أراجع التغطية من routes أولًا، ولا أعد وجود Blade وحده صفحة dashboard فعالة. `resources/views/admin/support/index.blade.php` يحتوي form بـ`action="#"` وعناصر خام، لكنه ليس view المرتبط بتدفق `AdminTicketController` النشط؛ الصفحة الفعلية هي `admin.tickets.*`. وكذلك `payment-transactions/` وبعض `settings/bills/` وmethods القديمة غير المربوطة في SubscriptionManagement لا تبرر اختراع إدارات إضافية. أضيفها إلى جرد legacy فقط وأثبت عدم وجود route/menu/include نشط قبل اقتراح إزالتها في مهمة منفصلة؛ لن أحذفها ضمن هذه الخطة اعتمادًا على الاسم.

## مراجعة القبول النهائية لملحق Admin

- [ ] لكل عقد Admin في route inventory owner واحد من A01–A21 أو S للإشعارات/المسودات؛ aliases تحفظ مع العقد الأصلي ولا تضاعف عدد الإدارات.
- [ ] كل mutation متخصص له FormRequest بالقدرة والبيانات الصحيحتين؛ وجود `AdminAuthenticatedRequest` عام لا يكفي لتسمية contract الإدارة مكتملًا.
- [ ] كل special operation خارج resource mapping تختبر Policy صراحة، ويشمل ذلك toggle/reorder/publish/profile/bulk وsubresources.
- [ ] لا inline business validation أو secret/exception leakage أو GET mutation أو عبور حدود غير معلن.
- [ ] كل Create يدعم draft lifecycle فعليًا؛ كل Create/Edit له shared partial و`$is_edit`؛ كل متعدد الخطوات له Review وNext contract الصحيح.
- [ ] كل جدول وصورة وكود ومرفق وأرشيف وأزرار يستخدم العقد المشترك، مع N/A مسبب للصفحات التي لا تمثل CRUD.
- [ ] الاختبارات الجديدة تثبت السلوك وتفويض السجل ودورة الملفات والتزامن، ولا تكتفي بالبحث عن أسماء classes أو فرض عدد بطاقات عشوائي.
- [ ] نتائج الاختبارات/build واللقطات المرئية حديثة ومرتبطة بالـcommit المنفذ، والسجل يعكس هذه الأدلة بدل أرقام baseline القديمة.
