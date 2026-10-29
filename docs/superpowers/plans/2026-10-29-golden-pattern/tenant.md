# خطة مطابقة إدارات Tenant مع Golden Pattern

> هذا الجزء من خطتي مبني على المسارات المحملة فعليًا في 29 أكتوبر 2026 وعلى قراءة الكود. أريد تنفيذ البنود بعد اعتماد الخطة، دون اعتبار حالة `Compliant` القديمة دليلًا كافيًا. هذه الوثيقة لا تعني أنني نفذت الإصلاحات أو اختبرت كل شاشة داخل المتصفح.

## حدود المقارنة وطريقة إغلاق المهام

أعتمد `route-inventory.json` في هذا المجلد مصدرًا للجرد: **193 عقد route طبيعيًا لـTenant** بعد إزالة تكرار central-domain وdomain/path، تشمل 13 عقدًا للمسودات وإشعارات الداشبورد المشتركة. سأحافظ على دعم الدخول عبر دومين المستأجر وعبر مسار المستأجر، ولا أحسب النسختين إدارتين مختلفتين. الـclosure الجذرية في `routes/tenant.php:252` تحويل تنقل إلى الداشبورد وليست عملية أعمال؛ تبقى ضمن اختبار سلامة التنقل.

أقسم العمل إلى T01–T15 أدناه. الإشعارات الجديدة والمسودات المشتركة تعالج في الجزء المشترك، وتختبر هنا من جهة Tenant. لا أعيد Teams أو General Settings أو Permission CRUD أو Version controllers أو Customer Finance إلى المنتج لأن السجل القديم ذكرها؛ لا توجد لها routes Tenant نشطة في الجرد الحالي. وجود ملفات باقية أو كود مساعد قديم لا يجعلها صفحات متاحة. كذلك `AuctionOutputController` غير موصول بـroute نشط؛ المسار الحالي للمخرجات هو `AuctionReportController`.

لكل مهمة سأطبق الآتي:

- [ ] أكتب اختبارًا يفشل بسبب الفجوة المحددة، وأوثق فشله قبل الإصلاح.
- [ ] أعدل الملفات المذكورة فقط وأحتفظ بالمنطق الصحيح والمكونات المشتركة الموجودة.
- [ ] أختبر المستخدم المسموح والمرفوض، والسجل الصحيح والسجل خارج النطاق، ونمطَي domain/path.
- [ ] أشغل اختبارات المهمة والعقود الأفقية المتأثرة؛ أي Feature test يستخدم قواعد Tenant سيعمل على قواعد معزولة ومخصصة للاختبار، لا على قاعدة التطوير.
- [ ] أشغل `npm run build` بعد أي تعديل JS/CSS/SCSS، وأراجع الشاشات فعليًا على RTL ومقاسات 375 و768 و1440px.
- [ ] أضيف الأدلة إلى بطاقة الإدارة، ولا أغلقها بسبب نجاح اختبار بحث نصي وحده.

الأولوية `P0` تعني رابطًا أو صفحة أو handler مكسورًا أو عقدًا يمنع التشغيل، و`P1` تعني صلاحيات/عزل/سلامة بيانات أو ملفات، و`P2` توحيد واجهة وتغطية اكتمال. هذا ترتيب تنفيذ داخل الخطة وليس ادعاء أن جميع السيناريوهات شُغلت.

## T01 — الداشبورد والإحصائيات وإعدادات الرسوم

**النطاق:** `dashboard`, `dashboard.statistics`, `dashboard.auction-activity`, `charSetting.update`، صفحة `resources/views/tenant/dashboard/index.blade.php`.

**الموجود الذي سأحافظ عليه:** Controller مفصول عن `DashboardService` في أغلب القراءة، وGateway لسياق الاشتراك، وكروت إحصائية ومكونات panels مشتركة. لا أنشئ له CRUD أو Draft أو Archive مصطنعًا؛ هذه `not_applicable` مع السبب.

**الفجوات المثبتة:** `app/Http/Controllers/Tenants/dashboard/DashboardController.php:38` يقبل `range` من Request عام دون FormRequest يحدد القيم؛ و`:46` يحوي queries الإحصائيات داخل Controller. `resources/views/tenant/dashboard/index.blade.php:197,277` يستخدم `table-hover` بدل عقد الجداول العام. `app/Http/Requests/Tenants/ChartSetting/UpdateChartVisibilityRequest.php:12` يقبل مفاتيح قديمة `compare_effort/daily_tasks/Overdue_tasks`، و`:16` يكتفي بوجود مستخدم، بينما الصفحة الحالية تعرض حركة المزادات وحالة العقارات؛ ارتباط هذه القدرة بالواجهة يحتاج قرار توثيق وإثبات استعمال، لا تبديل أسماء في البيانات اعتباطًا.

**ما أريد تنفيذه — P1 ثم P2:**

- [ ] إنشاء `app/Http/Requests/Tenants/Dashboard/AuctionActivityRequest.php` بعقد `authorize/rules/messages/attributes` وصلاحية `dashboard.view`؛ القيم المدعومة فعلًا هي `1month,3months,6months,1year`. أرفض القيمة غير المدعومة بـ422 بدل التحويل الصامت.
- [ ] نقل إحصائيات `getStatisticsData` إلى `DashboardService::getStatistics(): array`، وتوحيد المعاني بين الصفحة وJSON: إجمالي المستخدمين مقابل النشطين والمقاعد، والعقارات الفريدة المباعة مقابل عدد عمليات البيع.
- [ ] تثبيت عقد chart visibility: إن كانت هناك واجهة نشطة تستعمل المسار، أعتمد مفاتيح الرسوم الحالية وأضيف migration للقيم المحفوظة عند الحاجة؛ وإن لم توجد، أوثقه كقدرة يتيمة وأزيل ربطه النشط ضمن تغيير موثق بدل ترك mutation لا يخدم صفحة. لا أضيف charts جديدة.
- [ ] تطبيق المكوّن العام للجداول مع empty/loading/error states، مع بقاء الكروت والمؤشرات الفعلية دون اختراع عدد كروت ثابت.
- [ ] إضافة `tests/Feature/Tenants/Dashboard/GoldenPatternDashboardTest.php`: 403 بلا الصلاحية، 422 لـrange خاطئ، تطابق counts مع بيانات A فقط، صفر بيانات بلا أخطاء JS، نجاح domain/path، وعدم حصول إحصائيات B في A.
- [ ] تشغيل `php artisan test --filter="GoldenPatternDashboardTest|TenantDashboard"` داخل بيئة الاختبار المعزولة؛ مقارنة بصرية لكروت ورسوم وجداول الصفحة قبل الإغلاق.

**التبعيات ومعيار القبول:** يعتمد على عقد الجداول المشترك والجرد؛ أغلقه عندما تتطابق الصفحة والـAPI في معنى المؤشر، ويثبت التحقق من الفلاتر والعزل، وتختفي queries الخاصة بالمؤشرات من Controller.

## T02 — المصادقة و2FA وكلمة المرور والتحقق والانتحال المصرح

**النطاق:** Login/logout/register، forgot/reset/OTP/edit-email، confirm-password، تحديث password، verification notice/send/verify، `tenants.two-factor.*`، و`impersonate`. الصفحات الفعلية تحت `resources/views/tenant/auth/`، بالإضافة إلى modal/صفحة تغيير كلمة المرور بالملف الشخصي في T03.

**الموجود:** FormRequests متعددة للمصادقة وActions لتسجيل حالة الدخول وتسجيل المستخدم وإعادة كلمة المرور، وتدفق OTP واختبارات مخصصة. لن أضيف زر مسودة لبيانات كلمات المرور أو OTP؛ أوثق ذلك `not_applicable` لأنها أسرار وتدفقات مصادقة وليست Create لإدارة أعمال.

**الفجوات المثبتة:** `app/Http/Controllers/Tenants/Auth/EmailVerificationPromptController.php:20` يشير إلى `auth.verify-email` غير الموجود، بينما الموجود `tenant.auth.verify-email`. `app/Http/Controllers/Tenants/Auth/Fortify/TenantTwoFactorSettingsController.php:9,74` يستورد Central Tenant ويستعلم داخل Controller، وقد فشل اختبار الحد المعماري الحالي بسبب ذلك. `AuthenticatedSessionController.php:122–132` يبني مسارات login يدويًا. `routes/tenant.php:428,451,457` يحتوي GET للانتحال وتعديل سياق OTP وإلغاء التحدي؛ أتعامل معها كاستثناءات مصادقة صريحة تحتاج تقييمًا وتوثيقًا، ولا أسمح بتعميمها على عمليات الأعمال.

**ما أريد تنفيذه — P0/P1:**

- [ ] توجيه verification notice إلى `tenant.auth.verify-email` واستخدام `user('tenant')` صراحة، مع اختبار render لغير verified وredirect للصحيح. أتحقق من عرض روابط إعادة الإرسال والخروج على domain/path.
- [ ] إنشاء `app/Services/Tenants/Shared/CentralSecurityPolicyGateway.php` بواجهة `isTwoFactorEnforced(string $tenantId): bool`؛ نقل قراءة سياسة الشركة إليه، وإبقاء Settings المحلية داخل Service لتجميع قرار الإلزام. لا يبقى Central Model أو `Tenancy::central` داخل Tenant Controller.
- [ ] توحيد استجابات 2FA الجديدة مع `success/message/status` مع تحديث المستهلكين معًا؛ منع تعطيل 2FA المفروض، وحماية QR/recovery codes من مستخدم آخر، وعدم تسجيل الأسرار في logs.
- [ ] استخدام مساعد route المستأجر للخروج. أثبت أن انتهاء الاشتراك أو فرض 2FA لا يصنع redirect loop يمنع المستخدم من صفحة الأمان أو الخروج.
- [ ] إخراج parsing والتحقق من impersonation token إلى Request/Action محددين، والتحقق من tenant/user/expiry واستهلاك الرمز مرة واحدة. إن أبقيت GET كمدخل SSO، أوثقه استثناءً محدودًا ومختبرًا وأجعل إنشاء جلسة الدخول عبر تبادل آمن؛ وإن انتقل التنفيذ إلى POST، أحدث مولّد الرابط المركزي والاختبارات مع نافذة توافق موثقة. لا أكسر عقد Admin للدخول إلى المستأجر بتغيير منفرد.
- [ ] نقل مسح جلسة OTP وإلغاء التحدي إلى POST مع CSRF وتحديث الروابط المتصلة، أو توثيق الاستثناء الأمني إذا فرض تدفق المصادقة ذلك؛ الهدف أن تبقى GET الخاصة بالأعمال للقراءة فقط.
- [ ] استكمال كل mutation Request بـ`authorize/rules/messages/attributes` حتى ما لا يملك body، دون إزالة السماح المقصود لمسارات login/OTP العامة المحددة.
- [ ] توسيع `tests/Feature/TenantAuthenticationValidationFeatureTest.php`, `tests/Feature/TenantPasswordResetOtpFeatureTest.php` وإضافة `tests/Feature/Tenants/Auth/GoldenPatternSecurityFlowTest.php` لتغطية replay/expiry/wrong tenant، force 2FA، OTP resend/throttle، والأخطاء العامة دون تسريب.
- [ ] تشغيل `php artisan test --filter="TenantAuthentication|TenantPasswordResetOtp|GoldenPatternSecurityFlow"` في البيئة المعزولة، ثم `php vendor/bin/phpunit tests/Unit/TenantCentralBoundaryContractTest.php tests/Unit/TenantAuthenticationContractTest.php`.

**معيار القبول:** كل صفحات المصادقة ترندر، والسياق tenant صريح، واختبار الحدود ناجح، ولا يمكن إعادة استعمال رمز الانتحال أو عرض أكواد مستخدم آخر. لا أنشئ Policy لسجل غير موجود؛ أوثق كيف تحمي guard/Request/Action الحالة الشخصية.

## T03 — المستخدمون والملف الشخصي والصور وكلمة المرور

**النطاق:** `users.*` بما فيه `profile`, `password.change/update`, `update-image`, `update-background`, `toggle-status`. الصفحات `resources/views/tenant/user-management/users/{index,create,edit,show,reset}.blade.php` و`partials/_form.blade.php`.

**الموجود:** الفورم المشترك موجود ويدار بـ`$is_edit`، وCreate يدعم resource key `tenant.user-create`، وStore/Update Requests و`UserPolicy` و`UserService` وUsersDataTable وصلاحيات CRUD. سأحتفظ بهذا وأصلح الفجوات فقط.

**الفجوات المثبتة:** `UserController.php:44` يضيف `auth` عامًا فوق route ذي guard tenant؛ أريد إزالة الغموض. `:258` يحتوي تنفيذ toggle مستقلًا بدل التفويض إلى عقد النشاط الموحد. `:341,362` يضم رسالة exception في JSON رفع الصور. صفحة Show تتضمن إجراءات تعديل شخصية، لذا تحتاج مراجعة عقد read-only: لا تغييرات عبر GET وإجراءات POST محددة ومحمية. لا أعد وجود نموذج داخل modal بحد ذاته تسريبًا أو فشلًا.

**ما أريد تنفيذه — P1/P2:**

- [ ] تثبيت `auth:tenant` في نقطة الحماية المعتمدة دون تكرار guard مختلف، وإثبات self-profile مقابل مشاهدة مستخدم آخر في Policy وRequests الصور.
- [ ] توصيل نشاط المستخدم بالعقد المشترك مع الحفاظ على منع تعطيل المدير المحمي ومنع تجاوز حدود المقاعد؛ أي domain guard خاص بالمستخدم يظل في Service/Action المتخصص.
- [ ] إزالة exception text من الاستجابة، وتسجيل السياق الآمن داخليًا؛ رفع صورة غير صالحة يرجع422، وملف مستخدم خارج scope يرجع403/404، وفشل التخزين لا يزيل الصورة السابقة.
- [ ] اختبار المسودة بصورة ومعلومات دور، استعادتها بالكامل بعد refresh ثم purge بعد النجاح فقط، وعدم ظهور مسودة Create في Edit. توثيق precedence `old()` ثم المدخلات المستعادة بقرار المستخدم ثم قيم السجل/الافتراضيات.
- [ ] مراجعة `show/reset` وحقول الصورة والخلفية لضمان shared controls وfile-card للمعاينات، وعدم تحويل صورة avatar التعريفية إلى بطاقة مرفق. مراجعة جدول النشاط والجلسات وفق العقد العام وpagination عند الحاجة.
- [ ] توسيع `tests/Feature/TenantUserManagementValidationFeatureTest.php`, `tests/Feature/TenantProfileUploadFeatureTest.php` وإضافة `tests/Feature/Tenants/Users/GoldenPatternUserLifecycleTest.php`: CRUD+Policy، manager protection، limit/race، self/other images، فشل Storage، draft isolation.
- [ ] تشغيل `php artisan test --filter="TenantUserManagementValidation|TenantProfileUpload|GoldenPatternUserLifecycle"` ثم `php vendor/bin/phpunit tests/Unit/TenantPersistedIdentifierContractTest.php`.

**معيار القبول:** أنشئ وأعدل من نفس الفورم، أسترجع مسودتي دون تسرب، تبقى الصورة القديمة عند الفشل، وتمنع جميع مداخل تعديل المستخدم نفس الحالات التي تمنعها الـPolicy.

## T04 — الأدوار والصلاحيات وتعيين الدور والنشاط

**النطاق:** `roles.index/create/store/show/edit/update/destroy` و`roles.user.get/update/status`، وصفحات `resources/views/tenant/user-management/roles/`.

**الموجود:** `RolePolicy` مشترك بين Central/Tenant ويتحقق من guard السجل، وRoleService يستخدم transaction ويصفي permissions بـ`guard_name=tenant`، والفورم مشترك ويحمل `$is_edit` إلى alias داخلي. تسميات العرض تمر عبر AccessLabels ومفاتيح DB الإنجليزية محفوظة.

**الفجوات المثبتة:** `roles/partials/_form.blade.php:46,73,86` يكتب checkbox controls محلية لا تستخدم component المشروع. `RoleController.php:209–263` يعدل المستخدمين عبر accessible query وصلاحية `roles.assign`، لكن لا يوجد استدعاء Policy للسجل المستهدف في هذه الطرق؛ سأضيف تفويضًا صريحًا مناسبًا لقدرة تعيين الدور، مع الحفاظ على قاعدة النطاق الموجودة. `RoleService.php:202` وUserService يملكان مسارات تحديث نشاط منفصلة؛ أربطها بالعقد المشترك دون نسخ handlers أخرى.

**ما أريد تنفيذه — P1/P2:**

- [ ] إضافة ability واضحة لتعيين دور مستخدم داخل `UserPolicy` أو Policy مخصصة للعملية، وربط GET lookup وPOST assign وPATCH status بنفس record scope وقواعد حماية المدير؛ أختبر أيضًا أن الدور من tenant guard فقط.
- [ ] تحويل مصفوفة الصلاحيات واختيار المجموعة/الكل إلى component checkbox-group مشترك بعقد validation وaccessibility، وإدراج `permissions/select_all` ضمن حقول الخطوة المطلوبة بدل التحقق من `name` وحده.
- [ ] الحفاظ على `partials/_form.blade.php` وأسماء `$is_edit` العامة، وتوحيد المعالجة داخله؛ لا أنشئ نسختين للفورم أو مسودة Edit. رفض role names العربية كمفاتيح مع السماح بعرض labels مترجمة، وعدم تحويل أسماء الصلاحيات المخصصة إلى مفاتيح جديدة تلقائيًا.
- [ ] توحيد استجابات أخطاء domain المعروفة وتمييزها عن exceptions الداخلية في الحذف، واختبار cache invalidation بعد create/update/delete/assign.
- [ ] إنشاء `tests/Feature/Tenants/Roles/GoldenPatternRoleLifecycleTest.php` لتغطية create/edit/form parity/draft، منع role من admin guard، منع تغيير مستخدم خارج النطاق/مدير محمي، صلاحيات view/create/edit/assign مستقلة، وحذف دور مستخدم وفق قواعد المجال.
- [ ] تشغيل `php artisan test --filter="GoldenPatternRoleLifecycle|TenantCrud"` و`php vendor/bin/phpunit tests/Unit/AccessLabelsTest.php tests/Unit/EnglishAuthorizationKeysTest.php`.

**معيار القبول:** كل قدرة تعيين أو تعديل دور تفوض المستخدم الهدف صراحةً، ومصفوفة الصلاحيات قابلة للاستخدام بالكيبورد وتعرض الأخطاء دون نسخ rules في JS.

## T05 — الإسنادات واختيار العقارات

**النطاق:** `assignments.*`، و`selected-properties` و`{assignment}/properties`، وصفحات `resources/views/tenant/assignments/{index,create,edit,show,action}.blade.php`، وأرشيفها في T14.

**الموجود:** `Store/UpdateAssignmentRequest`, `AssignmentPolicy`, `Create/UpdateAssignmentAction`, `AssignmentService`, `CentralCityOptionsGateway`, `AssignmentsDataTable`, `AssignmentPropertySelectionDataTable`؛ قفل العقارات أثناء الإنشاء وguard لمنع حذف إسناد له عقارات/مزادات؛ stats/image/status/checkbox وأزرار داخل الجدول ومسودة `tenant.assignment-create`.

**الفجوات المثبتة:** Create يعرّف أربع خطوات في `create.blade.php:28`، بينما Edit يعرّف خطوتين فقط في `edit.blade.php:23` ولا مراجعة نهائية، ولا يوجد `_form.blade.php` موحد للإدارة. `create.blade.php:142,220` و`edit.blade.php:89,106` تخصص نصوص الأزرار. `show.blade.php:115,163` يخالف عقد الجداول. `AssignmentsController.php:91` يجهز query/rendering لنتيجة العقارات المختارة داخل Controller. الـtoggle في `:181` له business flow خاص متكرر. لا أساوي هذه الفجوات بفقدان Action/Policy الموجودة.

**ما أريد تنفيذه — P1/P2:**

- [ ] إنشاء `resources/views/tenant/assignments/partials/_form.blade.php` ومشاركة basic/properties/files/review بين Create/Edit، مع `$is_edit` فقط. يبقى رقم الإسناد ثابتًا readonly مع payload صحيح، وتعرض Edit العقارات والملفات الحالية وتسمح بما يدعمه UpdateRequest فعلًا.
- [ ] تمديد `UpdateAssignmentRequest`, `UpdateAssignmentAction`, `AssignmentService` لدعم تعديل العلاقات والمرفقات التي تظهر في الفورم الموحد، بمعاملة وقفل وإعادة فحص أن العقار متاح/تابع للإسناد، دون فك ارتباط عقار داخل مزاد بصورة غير مسموحة.
- [ ] اعتماد Review بعرض كامل والتنبيه والكروت المتوازنة وروابط edit icon، واستخدام النص الافتراضي للأزرار وحفظ المسودة مع التالي داخل `d-flex gap-2`، والـfinal submit من المراجعة فقط.
- [ ] استخراج selected-properties presentation/query إلى `AssignmentService` أو presenter مخصص مع حد لعدد IDs، والحفاظ على Gateway المدينة ومكوّن الصورة المشترك.
- [ ] إصلاح دورة استبدال صورة الإسناد: `AssignmentService.php:51–76` يحذف القديم قبل اكتمال استبداله؛ أخزن الجديد أولًا، أؤكد DB، ثم أنظف القديم؛ أختبر فشل store/commit وعدم فقدان الملف السابق.
- [ ] نقل toggle إلى العقد العام مع Policy على السجل، وتحويل Show tables إلى التصميم العام وإضافة pagination للـproperties/auctions عندما تكبر العلاقات بدل `load` كامل بلا حد.
- [ ] إنشاء `tests/Feature/Tenants/Assignments/GoldenPatternAssignmentLifecycleTest.php` وتوسيع `tests/Feature/TenantAssetModulesFeatureTest.php`: تكافؤ خطوات Create/Edit، تعديل عقارات مملوكة فقط، سباق إسناد العقار، full draft files restore، منع حذف ذي علاقات، وفشل الملف/rollback.
- [ ] تشغيل `php artisan test --filter="GoldenPatternAssignmentLifecycle|TenantAssetModules"` في البيئة المعزولة ثم build عند تغير assets.

**معيار القبول:** Edit يعرض نفس القدرات والخطوات الحقيقية للإنشاء مع إدارة الملف الحالي، ويمنع التغيير غير المشروع، ولا يضيع ملف قديم عند فشل التحديث.

## T06 — العقارات والحدود والملاك والوحدات والخريطة

**النطاق:** `properties.*` وصفحات `resources/views/tenant/properties/` بما فيها `_boundaries-table.blade.php` و`_location-map-card.blade.php`؛ الأرشيف في T14.

**الموجود:** Store/Update Requests، `PropertyPolicy`, Create/Update/Restore/ForceDelete Actions، `PropertyService`, `CentralCityOptionsGateway`، جدول بصور وسويتش نشاط وفلاتر، ومسودة `tenant.property-create`، وReview مصنف للملفات.

**الفجوات المثبتة:** Create/Edit يكرران الاستمارة كاملة بدل `_form`؛ حقول owners/units خام داخل `edit.blade.php:255–372`، ورفع صورة الخريطة خام في `_location-map-card.blade.php:7`. نصوص مخصصة للأزرار في `create.blade.php:472` و`edit.blade.php:544`. `PropertiesController.php:89,135` يكشف exception في flash. `UpdatePropertyAction.php:188–205` يحذف ملفات الوثائق الأحادية قبل نجاح store للبديل داخل transaction؛ rollback يعيد سجل DB ولا يعيد bytes القديمة. `show.blade.php:155,188,218,251` يستخدم `table-hover`، ويحمل Controller كل العلاقات في `:94`.

**ما أريد تنفيذه — P1 ثم P2:**

- [ ] إنشاء `resources/views/tenant/properties/partials/_form.blade.php` وpartials مشتركة للخطوات، وإبقاء IDs التي يعتمد عليها محرك الخريطة والحدود حتى تحديثها مع الاختبارات في نفس المهمة.
- [ ] إنشاء/استعمال shared controls للـowner rows/unit rows ورفع صورة الخريطة مع full DOM validation contract، وإعادة فهرسة المصفوفات وإطلاق change بعد الإضافة والحذف؛ لا أضيف قواعد JavaScript موازية لـFormRequest.
- [ ] تحويل استبدال الوثيقة الأحادية إلى مرحلتين: تخزين الجديد وتسجيله داخل العملية مع تتبع الملفات الجديدة، تنظيف الجديد عند الفشل، ثم حذف القديم بعد commit آمن قابل لإعادة المحاولة. أحافظ على المعنى الوظيفي: وثيقة واحدة حالية بلا orphaned records، دون تطبيق التعارض النصي في الدليل بطريقة تفقد البيانات.
- [ ] فحص `aerial_image` و`boundary_geojson`: الحجم/النوع/نطاق الإحداثيات وعدد النقاط والتأكد أن أي path مستعاد من مسودة يعود لمالكها، وعدم الثقة بمسار أرسله المتصفح.
- [ ] استخراج خيارات الإسناد المعتمدة إلى Service عند إعادة استخدام query، وإبقاء الحالي غير النشط ظاهرًا في Edit دون فتح باب اختيار إسناد غير مسموح.
- [ ] توحيد المراجعة والأزرار وShow والجداول والـstatus handler، وحصر رسالة الاستثناء في log آمن؛ pagination للعلاقات الكبيرة مع بقاء العدادات صحيحة.
- [ ] توسيع `tests/Feature/PropertyManagementFeatureTest.php` وإنشاء `tests/Feature/Tenants/Properties/GoldenPatternPropertyFilesTest.php`: old file survives failed replacement، كل فئة أحادية لا تتضاعف، اختيار owner/unit/assignment خارج النطاق مرفوض، draft arrays/maps/files restore، ومنع حذف عقار مرتبط بمزاد.
- [ ] تشغيل `php artisan test --filter="PropertyManagement|GoldenPatternPropertyFiles|TenantAssetModules"` ثم `php vendor/bin/phpunit tests/Unit/TenantPropertyStatusContractTest.php tests/Unit/TenantPropertyImageOverlayContractTest.php`.

**معيار القبول:** نفس الفورم في Create/Edit دون فقد خرائط أو مرفقات، والفشل لا يسبب ملفًا مفقودًا أو سجلًا يشير لملف محذوف، وتعمل subtables على RTL والجوال.

## T07 — المزادات وربط العقارات وبيانات التقارير

**النطاق:** `auctions.index/create/store/show/edit/update/destroy/toggle-status`، صفحات `resources/views/tenant/auctions/` و`partials/steps/`، والتقارير في T08.

**الموجود:** `AuctionPolicy`, Store/Update Requests، Create/Update Actions بمعاملات وقفل الإسناد والعقارات، `AuctionReportInputService`، generation service، دعم حفظ حقول التقارير والملفات وإعادة التوليد؛ لا أرجع إلى تدفق قديم يلغي هذه القدرات.

**الفجوات المثبتة:** Create/Edit يشتركان في بعض step partials لكن غلاف form والخطوات والأزرار مكرر ولا يوجد `_form`. عنوان المراجعة يختلف في `create.blade.php:40` و`edit.blade.php:55`. `show.blade.php:183` يمرر `layout="list"` للمرفقات، و`:199` يستخدم جدولًا محليًا. `AuctionsDataTable.php:27–38` لا يعرض عمود صورة مستقل رغم وجود cover_image، ويعرض workflow فقط. `AuctionService.php:46–53` يغير **is_active وworkflow status معًا** عند toggle؛ هذا خلط صريح بين النشاط وسير العمل. Controller يعرض exception في `:89,151`.

**ما أريد تنفيذه — P1/P2:**

- [ ] إنشاء `resources/views/tenant/auctions/partials/_form.blade.php` يشمل create/edit بنفس الخطوات والعناوين والعقد، مع حفظ حقول report_image/marketing_evidence/board_images ونتائج العقارات دون سقوط أي input أثناء الاستخراج.
- [ ] فصل activity عن workflow: PATCH toggle يغير `is_active` فقط عبر المكوّن العام؛ الحالة التشغيلية تبقى Enum/اختيار مصرح داخل تحديث سير العمل. أعرض `الحالة` القابلة للتعديل قبل الإجراءات مباشرة وأبقي workflow badge بعمود واضح منفصل. لا أحول المزاد إلى active/suspended تلقائيًا بسبب سويتش النشاط.
- [ ] إضافة image column عبر presenter المشترك وبـrounded-circle وfallback شعار Tenant/المنصة؛ cover اختياري كما يدعمه العقد الحالي، دون جعل الملف required لمجرد تصميم الجدول.
- [ ] تحويل كل معاينات Show/Edit/Review إلى file-card 150×150، توحيد الجداول والمراجعة والأزرار، وإخفاء زر retry عن مستخدم لا يملك ability المطلوبة مع استمرار تفويض الخادم في T08.
- [ ] إزالة exception من رسائل المستخدم، ونقل خيارات القراءة المتكررة إلى Service، وعدم تحميل علاقات Show بلا حد.
- [ ] إنشاء `tests/Feature/Tenants/Auctions/GoldenPatternAuctionLifecycleTest.php`: إنشاء وتعديل بلا cover، نشاط لا يغير workflow، عقار من إسناد آخر مرفوض حتى مع تعديل payload، حالات pending/failed reports، كل ملفات المسودة تستعاد وتُنظف بعد النجاح، file replacement rollback، وعزل A/B.
- [ ] تشغيل `php artisan test --filter="GoldenPatternAuctionLifecycle|TenantAssetModules|AuctionReportInputContract"` ثم اختبارات report-inputs JS الموجودة وبناء assets عند تعديلها.

**التبعيات ومعيار القبول:** بعد T05/T06 والعقد المشترك للنشاط. أغلقه عندما تعمل القدرات الحالية كاملة من فورم واحد، ويثبت الفصل بين `is_active` وworkflow والملفات الاختيارية.

## T08 — حالة التقارير وتنزيلها وإعادة المحاولة والمخرجات

**النطاق الفعلي:** `auctions.reports.status` GET، `auctions.reports.download` GET بنوعي outputs/report، `auctions.reports.retry` POST. لا أنشئ routes لـ`AuctionOutputController` لمجرد وجوده على القرص.

**الموجود:** `AuctionReportController`, `AuctionReportService`, `GenerateAuctionReportsJob`، تخزين `tenant_private`، record Policy قبل status/download/retry، tenant identifier وrevision token، وdispatch بعد commit. الاختبارات الحالية تحت `tests/Feature/Tenants/Auctions/AuctionReport*` و`tests/Unit/Auctions/`.

**الفجوات المثبتة:** `AuctionReportController.php:113` mutation نشطة بلا FormRequest، و`:134` يرجع exception للمستخدم. status في `:39` وDataTable في `AuctionsDataTable.php:104` يعرضان error_message الخام. `app/Jobs/Tenants/Auctions/GenerateAuctionReportsJob.php:8,51,182` يقرأ Central Tenant مباشرة داخل Tenant Job بدل بوابة إعادة سياق معلنة. يوجد `AuctionOutputService` قديم يستورد Central AuctionTemplate لكن لا يستدعيه المسار النشط؛ أصلح/أعزل legacy بقدر ما يتطلبه اختبار حدود المشروع، دون توصيله كقدرة جديدة.

**ما أريد تنفيذه — P0/P1:**

- [ ] إنشاء `app/Http/Requests/Tenants/Auctions/RetryAuctionReportsRequest.php` كامل العقد، يتحقق من `user('tenant')->can('update', $auction)` ومن route binding؛ تمرير actor ID وسياق Tenant إلى Service.
- [ ] جعل استجابة async تحمل `success/message/generation_id/status`، وتقديم error code/message آمن للمستخدم مع internal exception في log فقط. أختبر أن absolute paths/SQL/credentials لا تصل إلى JSON أو tooltip.
- [ ] نقل تحميل central Tenant اللازم لإعادة سياق queue إلى Gateway/Context resolver صريح، مع استعادة السياق السابق في finally، واختبار job يبدأ بلا tenant ثم يعود إلى الحالة السابقة بعد النجاح والفشل.
- [ ] إثبات idempotency: ضغط retry المتكرر لا يكتب ملفات جيل قديم فوق الجديد، ولا يجعل الجيل غير الأحدث ready؛ الحذف النهائي ينظف ملفات generations الخاصة بالمزاد فقط بعد توقف/تجاهل الأعمال القديمة وفق العقد.
- [ ] إبقاء downloads خاصة ومقيدة بالمزاد الحالي، وحصر type في outputs/report؛ ready مع ملف مفقود يرجع404 مفهومًا، ولا يشتغل GET status/download على إنشاء تقرير أو تعديل سجل أعمال.
- [ ] توسيع `tests/Feature/Tenants/Auctions/AuctionReportIsolationTest.php`, `AuctionReportWorkflowTest.php` وإضافة `tests/Feature/Tenants/Auctions/AuctionReportAuthorizationTest.php`؛ حالات 403/404/422/202، replay/revision، missing disk file، tenant recovery، فشل renderer ونظافة الملفات.
- [ ] تشغيل `php artisan test --filter="AuctionReportAuthorization|AuctionReportIsolation|AuctionReportWorkflow"` في البيئة المعزولة ثم `php vendor/bin/phpunit tests/Unit/Auctions/AuctionReportQueueTest.php tests/Unit/TenantCentralBoundaryContractTest.php`.

**معيار القبول:** يوجد Request لكل retry، لا تسريب داخلي، ولا تنزيل متبادل بين المستأجرين، ولا كتابة جيل قديم فوق الجديد. الشكل البصري النهائي لملف PDF له خطة التقارير المتخصصة القائمة؛ لا أغير تصميمه ضمن توحيد الداشبورد إلا لتصحيح عقد مؤثر هنا.

## T09 — إعدادات المستأجر ومركز الإعدادات وAPI Token

**النطاق:** `settings.index/update/center`, `api-token-settings.index/regenerate`؛ الصفحات `tenant/settings/settings`, `center`, `api_token/index` والـpartials العامة والطباعة وSMS و2FA.

**الموجود:** `MainSettingsRequest`, `UpdateTenantSettingsAction` يملك transaction وقفل وتنظيف ملفات جديدة/قديمة، و`CentralTenantApiTokenGateway` لتوليد التوكن، وصلاحية `system.settings` لـindex/update/token.

**الفجوات المثبتة:** `resources/views/tenant/settings/center.blade.php:38,77,116` يستخدم routes `general-settings.*` غير المحملة. `SettingsController.php:17` يحمي index/update فقط بينما center باقٍ بلا نفس capability، و`:35–50` يقرأ active_tab/flags مباشرة من Request خارج array البيانات المعتمدة؛ `MainSettingsRequest.php:26` يعرف عدة flags كnullable فقط ولا يحتوي `attributes()`. API token logs query في Controller؛ ليست هذه وحدها ثغرة، لكن القراءة المشتركة تنقل إلى خدمة عند توحيد view-data.

**ما أريد تنفيذه — P0/P1:**

- [ ] إزالة بطاقات General Settings غير النشطة من center أو استبدالها بروابط إعدادات نشطة مطابقة للمنتج الحالي؛ لا أضيف routes legacy لإرضاء Blade قديم. إضافة `settings.center` إلى نفس صلاحية الإعدادات.
- [ ] اعتماد `active_tab` والـflags من validated data بعد normalization في MainSettingsRequest، وتقييد الـbooleans والـarrays والملفات لكل tab، وإضافة `attributes()` ورسائل field-level. مراجعة `failedValidation()` حتى يبقى HTML redirect وJSON422 متوافقين مع التحقق المشترك.
- [ ] توحيد raw controls والمرفقات المعروضة في partials الإعدادات، مع إظهار الاحتفاظ بالصورة الحالية واستبدالها. Settings تحديث configuration وليست Create لإدارة، لذلك لا أفرض مسودة إنشاء جديدة عليها دون عقد مجال مستقل.
- [ ] إبقاء سر API الجديد flash لمرة واحدة دون تضمينه مجددًا في logs/view JSON، وتوضيح أن regenerate يلغي السابق فعليًا؛ إخفاء سجل الاستخدام والتوكن عن غير المصرح وتصفية/إخفاء أي بيانات سرية في log payload.
- [ ] اختبار cache invalidation وعزل `app_settings` وtoken logs بين A/B، وفشل رفع logo/print image دون فقد القديم، وإلزام 2FA بعد حفظ tab دون تعطيل الوصول لصفحة الإعداد الأمني.
- [ ] توسيع `tests/Unit/TenantSettingsArchitectureContractTest.php` وإضافة `tests/Feature/Tenants/Settings/GoldenPatternSettingsTest.php`: render center بلا RouteNotFound، صلاحيات كل tab/token، invalid active_tab/boolean، rotation، وفشل الملفات.
- [ ] تشغيل `php artisan test --filter=GoldenPatternSettings` في البيئة المعزولة و`php vendor/bin/phpunit tests/Unit/TenantSettingsArchitectureContractTest.php`.

**معيار القبول:** كل بطاقة إعدادات لها وجهة فعالة، ولا حقول flags خارج العقد، وrotation والعزل والملفات مثبتة باختبارات.

## T10 — النطاقات وDNS وcPanel والحجز والنقل والتجديد

**النطاق:** كل `domains.*` المحملة، وتوزيعها بين DomainController وDomainLookupController وCheckMultipleDomainController وDomainPaymentController وRenewalDomainController وGetDomainInfoController وCpanelController. الصفحات النشطة `resources/views/tenant/domains/{setting,index,linkdomain,linksubdomain,faq,explain,explain-create-subdomain,renewalpayment}.blade.php` و`tenant/payment/moyasar-checkout`.

**الموجود:** Domain Requests، `CentralDomainGateway`, `DomainDnsLookupService`, Namecheap/Cpanel/DomainPayment Services، إعدادات الاتصال عبر `config/services.php`، stats/table موحدان نسبيًا، صلاحيات view/connect/reserve/transfer عند الطلبات المختصة. لا أعيد `transferdom.blade.php` كصفحة نشطة؛ `DomainController@transferDom` غير موصول حاليًا.

**الفجوات المثبتة — P0/P1:**

- `routes/tenant.php:197` POST renewal بلا route parameter، بينما `RenewalDomainController.php:30` يطلب `$domain` بدائيًا بلا Request؛ لا يوجد عقد binding صالح لهذا المدخل.
- `domains/linkdomain.blade.php:119,173` و`linksubdomain.blade.php:111` تبني URLs يدويًا بـtenant id، فتخالف domain/path helper contract.
- `domains/explain-create-subdomain.blade.php:390` يستعمل `domains.subdomain` غير الموجود؛ الصفحة نفسها نشطة عبر subdomainblade.
- `DomainController.php:847–915` ينفذ orchestration طويلًا لـWHM، يولد username/password ويستدعي provider ويكتب central domain/cpanel من داخل Controller؛ `:884` يضع contact email ثابتًا واسم plan ثابتًا. هذه إعدادات منصة يجب إخراجها إلى config مع service مسؤول.
- `CentralDomainGateway.php:14` و`:54` يعرضان queries عامة find/pendingTransfers؛ لا أستنتج تسريبًا من التوقيع وحده، لكن أمنع استعمالها في صفحات Tenant لقراءة/تعديل ملكية غير المستأجر الحالي، وأغطي paths النشطة باختبار.
- الحقول في صفحات الربط والتجديد خام وتصاميمها محلية؛ التحقق البصري والـvalidation الموحد يحتاجان استكمالًا.

**ما أريد تنفيذه:**

- [ ] إنشاء `RenewDomainRequest` كامل داخل `app/Http/Requests/Tenants/Domains/` وربط renewal إلى domain_id مع ownership وصلاحية التجديد المعتمدة، ثم تفويض الخدمة بدل primitive غير مربوط. إن كان مسار التجديد مدخلًا قديمًا مكررًا للتدفق المدفوع، أوحده مع المسار الحالي ضمن compatibility window موثقة ولا أترك side effect لتجديد provider دون تحقق دفع.
- [ ] استبدال URLs بالـ`tenant_route_name` الصحيح؛ تصحيح/حذف نموذج subdomain غير المدعوم أو توصيله بالفعل المصرح الموجود إن كان يمثل نفس العملية، دون اختراع إدارة نطاقات فرعية جديدة. إضافة route-render contracts لكل صفحة نشطة.
- [ ] استخراج `ConnectTenantDomainAction::execute(array $data, string $tenantId): ...` وService لتنسيق provider/database/reconciliation؛ تحفظ credentials في config/storage آمن، وتكون نتائج الأعمال DTO/value لا RedirectResponse من provider service.
- [ ] فحص DNS من الخادم ضمن ConnectAction نفسها؛ تعليق `DomainController.php:866` يفترض أن المستخدم مر بـverifyDNS سابقًا، وهذا لا يكفي عند استدعاء POST مباشرة. أرفض الربط غير المثبت حتى لو تجاوز المستخدم خطوة الواجهة.
- [ ] اعتماد timeouts/retries/idempotency لكل createacct/modify/renew/transfer، وتوثيق التعويض إذا نجح provider وفشل DB. قفل domain/tenant عند جعل نطاق واحد نشطًا، وعدم قبول tenant_id أو الأسعار من المتصفح كمصدر حقيقة.
- [ ] فصل lookup العام عن domain-owned operations، وإثبات ownership في cPanel limits/info/modify/activate/renew/cache clear. لا أعرض حساب منصة Namecheap أو بيانات نطاق مستأجر آخر لمجرد امتلاك domain.view.
- [ ] توحيد نماذج الربط والحجز مع shared controls وserver validation والمسودة إذا كانت Create فعلية؛ بيانات EPP/password/API secrets لا تحفظ في مسودة عامة. أوثق N/A مسببًا لأي خطوة دفع/provider لا تصلح لإعادة الاستعادة دون تفويض جديد.
- [ ] تحويل صفحات التعليمات إلى components الجداول/الكروت/الهوية المشتركة دون تغيير محتواها الوظيفي، ومراجعة كل روابط شرح/FAQ/support على mode الدومين والمسار.
- [ ] إنشاء `tests/Feature/Tenants/Domains/GoldenPatternDomainLifecycleTest.php` و`GoldenPatternDomainPagesTest.php`: render جميع الصفحات، bad/missing domain_id، A/B ownership، bypass DNS step، duplicate provider response، timeout وpartial failure، callback retry؛ كل provider عبر fake مضبوط في الاختبار ولا اتصال حقيقي.
- [ ] تشغيل `php artisan test --filter="GoldenPatternDomain|CentralDomainTenantRoutesAccess"` في البيئة المعزولة و`php vendor/bin/phpunit tests/Unit/TenantCentralBoundaryContractTest.php`.

**معيار القبول:** لا route أو view مكسورة، ولا mutation خارج FormRequest/Policy أو حجز/تجديد بلا نتيجة دفع موثوقة، وController لا يدير provider transaction بنفسه. لا أدعي فحص تكامل Namecheap/WHM الحقيقي قبل اختبار staging منفصل مصرح.

## T11 — الاشتراك والفواتير والتجديد والتراخيص والدفع

**النطاق:** `subscribe.*`, `subscriptions.*`, `bills.*`, `licenses.assign/release`؛ الصفحات `tenant/subscriptions/{index,new_subscription/index,new_subscription/checkout,renew/checkout,upgrade/checkout}` و`tenant/bills/{index,show,checkout,bank_transfer}` و`tenant/payment/moyasar-checkout`. Version غير إدارة نشطة الآن.

**الموجود:** Gateways للـbilling/license/subscription/trial، ownership في `CentralBillingGateway::billForCurrentTenant`، قفل فاتورة unpaid قبل رفع receipt، Actions للـreceipt، callback verifier وخدمات اشتراك مشتركة، Requests للترخيص ورفع الإيصال. الفواتير حالات workflow؛ لا أحول paid/unpaid إلى activity toggle.

**الفجوات المثبتة:** `bills/checkout.blade.php:20` يرسل إلى `bills.checkout.store` بينما المحمل `bills.checkout.process` ويتطلب `{id}`. `PaymentRequest.php:9` يكتفي بمستخدم tenant ويعتمد في معظم الصلاحيات على Controller بدل قدرة العملية المحددة. callbacks في `BillsController.php:140`, `NewSubscriptionController.php:100`, `TenantSubscriptionController.php:123` وما يليها تستخدم Request عامًا لعمل مالي فعلي عبر GET؛ يجب توثيقها callback استثنائية والتحقق من النوع/التوقيع/المعرف/الملكية/idempotency. Controller ينسق السعر/الكوبون والضريبة/الفاتورة في عدة طرق، وGateway يعيد Central Eloquent Models إلى العرض. لا أدعي أن نتيجة الدفع مزورة لمجرد وجود GET؛ خدمة verify موجودة ويجب إثبات سلوكها.

**ما أريد تنفيذه — P0/P1:**

- [ ] تصحيح action نموذج فاتورة checkout إلى `tenant_route_name('bills.checkout.process', $bill->id)` واختبار render بما فيه id الصحيح، وعدم بقاء route strings قديمة في coupon/receipt/back links.
- [ ] فصل Requests العملية: subscription checkout/renew/bill payment/add licenses، مع الصلاحية المعتمدة والمطابقة لـController، وعدم قبول كمية مقاعد أو price/discount من hidden fields كقيم موثوقة. الأسعار والضرائب والكوبونات تحسب من الخدمة الموجودة في الخادم، دون إعادة تعريف سياسة مالية جديدة ضمن هذه المهمة.
- [ ] إنشاء `TenantPaymentCallbackRequest` أو Requests متخصصة حسب نوع الدفع، تكشف identifiers المطلوبة وتفوض tenant-owned bill؛ تظل verification مع provider في الخدمة. أضيف signature أو التحقق server-to-server المتوافق مع العقد الفعلي، وidempotency key/payment id وقفل الحالة، ولا أعتبر GET callback بديلًا لـPOST عادي داخل الإدارة.
- [ ] استخراج orchestration بدء الدفع/التجديد/شراء الترخيص من Controllers إلى Actions/Services متخصصة؛ توحيد callback result وترجمة الخطأ لرسالة مستخدم آمنة، مع الحفاظ على planless single-user trial وعدم تمريرها إلى renewal يفترض وجود plan.
- [ ] تخفيض crossing إلى DTO/read models للعرض حيث يكفي ذلك، والتحقق من tenant context صراحة في gateways المشتركة؛ لا أجعل Tenant Blade ينفذ relation lazy-load على Central Model خارج السياق.
- [ ] مراجعة ManageLicenseRequest وLicenseService لمنع تخصيص/تحرير رخصة لمستخدم خارج النطاق أو مستخدم محمي، وإثبات lock/race عند آخر مقعد. لا تنشأ شاشة Version أو CRUD مالي جديد.
- [ ] استخدام shared form controls للcoupon/quantity/bank/receipt، وfile-card لمعاينة الإيصال. تنشأ مسودة للإدخال غير النهائي فقط إذا كانت العملية Create قابلة للاستعادة، دون حفظ card/token/OTP أو تكرار دفع عند استرجاع المسودة؛ ما لا ينطبق يوثق N/A بسبب أمني/وظيفي.
- [ ] توسيع `tests/Feature/SubscriptionDatesAndPeriodsLifecycleTest.php` وإضافة `tests/Feature/Tenants/Billing/GoldenPatternBillingFlowTest.php`: same callback مرتين لا يضاعف الاشتراك/المقاعد، فاتورة B مرفوضة، paid لا يقبل receipt جديدًا، فشل upload يبقي القديم، unpaid/pending/rejected views، trial انتهاء/تحويل، تزامن تخصيص آخر مقعد، provider fakes فقط.
- [ ] تشغيل `php artisan test --filter="GoldenPatternBillingFlow|SubscriptionDatesAndPeriodsLifecycle"` في البيئة المعزولة، مع تقرير يدوي لتدفقات الرجوع من مزود الدفع في staging عند التنفيذ.

**معيار القبول:** جميع صفحات الدفع ترندر، والتحقق والصلاحية والعزل موجودة في كل مدخل، والنتيجة المالية تطبق مرة واحدة، ولا ترسل استعادة مسودة طلب دفع جديدًا.

## T12 — تذاكر الدعم والردود والتقييم والاستبيان

**النطاق:** `tickets.index/create/store/show/reply/replies.rate/survey.submit`، صفحات `resources/views/tenant/tickets/{index,create,show}.blade.php`.

**الموجود:** typed Requests لكل write، صلاحيات view/create، `EtmamTicketService` يدعم provider وfallback محليًا، و`tenantTicketQuery` يقيد التذاكر المركزية بالمستأجر؛ unit contracts للعزل والتفويض. Create يتضمن draft key `tenant.ticket-create`. لا توجد Edit للتذكرة؛ أوثق shared create/edit partial غير منطبق حاليًا ولا أنشئ صفحة Edit جديدة.

**الفجوات المثبتة:** `TicketController.php:58–85` ينشئ أقسام fallback ثابتة داخل Controller عند فشل المصدر، و`:129–181` يجهز view-data بتفاصيل كثيرة. `show.blade.php:46,52,82,283,304` يحتوي controls محلية للاستبيان والرد والمرفقات. `TicketsDataTable.php:20–55` يحمّل نتائج في collection ويفلترها بدل server-side query/pagination. لا أقول إن scoped query يغني عن Policy/ownership في مسار provider؛ يلزم إثبات كلا الوضعين.

**ما أريد تنفيذه — P1/P2:**

- [ ] إبقاء الGateway/Service مسؤولًا عن central integration، وإضافة عقد تفويض صريح للتذكرة/الرد/الاستبيان يثبت tenant وreply.parent، محليًا وعبر provider؛ لا يكفي وجود ticket number في URL.
- [ ] نقل sections fallback/view-data إلى خدمة أو presenter بقرار واضح: unavailable يعرض حالة قابلة لإعادة المحاولة، أو fallback من قاموس محلي معتمد؛ لا أخترع IDs أقسام قد يرفضها provider.
- [ ] جعل list query/pagination server-side أو adapter يطلب صفحة محددة من provider؛ حصر copy/excel بالمخرجات المصرح بها. لا أضيف mass-delete لتذاكر لا تدعم الحذف.
- [ ] استخدام shared checkbox/radio/rating/textarea/file controls، وإبقاء file-card لجميع مرفقات التذكرة والردود مع روابط تحميل مصرح بها؛ نقل أي validation مكرر في `resources/assets/js/tenant/tickets/form-validation.js` و`support/ticket-form-validation.js` إلى FormRequest إذا كان الملف محملًا فعلًا، وإزالة الازدواج من entrypoint فقط بعد إثبات الاستخدام.
- [ ] اختبار المسودة بموضوع ورسالة وقسم ومرفقات، والاستعادة الفعلية/purge بعد نجاح service فقط؛ لا تُحذف المسودة لأن provider أعاد failure message في redirect.
- [ ] إنشاء `tests/Feature/Tenants/Tickets/GoldenPatternTicketLifecycleTest.php`: provider/local isolation A/B، reply غير تابع، إغلاق/إعادة فتح/تقييم حسب قواعد المجال، service disabled، sections unavailable، files/draft rollback، قائمة كبيرة لا تحمل كل السجلات.
- [ ] تشغيل `php artisan test --filter=GoldenPatternTicketLifecycle` في البيئة المعزولة و`php vendor/bin/phpunit tests/Unit/TenantTicketIsolationContractTest.php tests/Unit/TenantTicketAuthorizationContractTest.php`.

**معيار القبول:** تظهر حالات التعطل بوضوح، ولا يكشف رقم التذكرة بيانات غير مملوكة، ويعمل الاستبيان والرد والمرفقات والتحقق بنفس العقود المشتركة.

## T13a — الإشعارات القديمة وتكامل إشعارات الداشبورد

**النطاق:** `notifications.index/all/unread-count/mark-as-read/mark-all-read/destroy/mass-delete`، مع ربط واضح إلى `dashboard-notifications.*` في الجزء المشترك دون خلط موديلَي الإشعارات.

**الموجود:** `ManageUserNotificationsAction` وtyped Requests لكل mutation، queries مرتبطة بالمستخدم الحالي، وNotificationsDataTable وملف all.

**الفجوات المثبتة — P0:** `NotificationController.php:30` يرندر `tenant.notifications.index` المفقود؛ الموجود `all.blade.php` و`action.blade.php`. `destroy` يرجع JSON دائمًا؛ هذا مقبول لعميل AJAX الحالي، لكنه يحتاج عقدًا موثقًا واختبارًا لو تحول إلى shared hidden form. لن أفرض HTML redirect ثم أترك JavaScript يقرأ JSON منقوصًا.

**ما أريد تنفيذه:**

- [ ] اختيار صفحة موحدة موجودة لـindex/all مع filters props أو إنشاء index كغلاف صغير يعيد استخدام partial مشترك؛ لا أنسخ DataTable كاملة. لا redirects إلى route قديم غير موجود.
- [ ] استعمال مكونات الفلاتر بدل `<select>` المحلي في `all.blade.php:48,56`، وإضافة stats حقيقية مثل total/unread/read إذا كانت الصفحة index داخلية، دون Add button لأن المستخدم لا ينشئ notification من هذه الإدارة.
- [ ] توثيق أن actions ملكية مستخدم/notification scoped، وإضافة Policy أو owner authorization صريح مناسب للموديل، مع response counts متسقة بعد read/delete/all. UUID غير مملوك لا يحذف ولا يكشف وجود الإشعار.
- [ ] توحيد confirmation/feedback مع shared actions وحصر inline handlers في العقد المشترك؛ لا أضيف per-page AJAX للنشاط.
- [ ] إنشاء `tests/Feature/Tenants/Notifications/GoldenPatternNotificationsTest.php`: render index/all، فلاتر read/type، counts، مستخدم آخر داخل نفس tenant ومستخدم في B، mixed IDs، JSON/browser contract بحسب العميل الفعلي.
- [ ] تشغيل `php artisan test --filter="GoldenPatternNotifications|DashboardNotifications"` في البيئة المعزولة ومراجعة أن القائمتين لا تكرران عدادًا واحدًا بمعنيين مختلفين.

**معيار القبول:** لا view مفقودة، وownership على كل notification، وعدادات navbar/صفحة الإشعارات متسقة لكل مصدر.

## T13b — سجل النشاط والتصدير وأرشيف ملفات السجل

**النطاق:** `activity-logs.index/show/get-all-ids/export/export-download/archives`، صفحات `resources/views/tenant/activity-logs/{index,show,archives}.blade.php`.

**الموجود:** `ActivityLogIndexRequest`, `ExportActivityLogsRequest`, `DownloadActivityLogExportRequest` مع owner-key، وActivityLogPolicy مسجلة، وfilters وتصدير محدد بـ20000 سجل.

**الفجوات المثبتة — P0/P1:** `ActivityLogController.php:264` يطلب `Request` دون import لـIlluminate، لذلك نوعه المحلي غير صالح للحقن؛ وحتى إضافة import وحدها لا تكفي لأن `applyFilters` في `:200` يقبل `ActivityLogIndexRequest`. `:40–125` يبني DataTable كاملًا في Controller و`:232` يحسب stats فيه. `:306` يكتب export في `tenant_public` رغم أن الملفات مرتبطة بصاحبها؛ الطلب المحمي وحده لا يثبت عدم إمكان الوصول المباشر للملف العام. archive في `:342` يسرد `activity-logs` بينما export يكتب `exports`؛ لا أخلط أرشيف rotation الدوري بمخرجات التصدير المؤقتة دون تعريف.

**ما أريد تنفيذه:**

- [ ] ربط getAllIds بـ`ActivityLogIndexRequest` واستعمال validated filters مشتركة، واختبار route الفعلي حتى يمنع فشل injection/type mismatch؛ تحديث `applyFilters` إلى filter DTO/array تستخدمه القائمة والتصدير وIDs بنفس المعنى.
- [ ] إنشاء `app/DataTables/Tenants/ActivityLog/ActivityLogsDataTable.php` يمتد AbstractDataTable، و`app/Services/Tenants/ActivityLog/ActivityLogService.php` للquery/statistics/view-data، مع presenter للـlabels وraw HTML escaped فقط.
- [ ] نقل export orchestration/storage/owner/retention إلى Action/Service، وتخزين الملفات في `tenant_private` مع download policy/path allow-list؛ توثيق حذفها بعد التنزيل وTTL. لا يعتمد filename على timestamp بالثانية وحده عند طلبين متزامنين؛ أستخدم معرفًا غير قابل للتخمين.
- [ ] تعريف أرشيف السجل الدوري كقدرة قراءة خاصة لها manifest وownership عند التنزيل؛ إن لم توجد route تنزيل فعلية للروابط المعروضة أضيفها بعقد آمن أو أحذف الإجراء غير المدعوم من الصفحة. `downloadFile` الحالي غير موصول ولا يعد route محمية بمجرد وجوده.
- [ ] تطبيق عقد archive layout على `archives` وتوحيد جداول show/filters، وإخفاء الحقول الحساسة في Activity properties قبل العرض والتصدير حسب policy، والحفاظ على حد20000 وإظهار truncated بوضوح.
- [ ] إنشاء `tests/Feature/Tenants/ActivityLog/GoldenPatternActivityLogTest.php`: render/get-all-ids injection، نفس filters للcount والexport، 20001 limits، filenames متزامنة، owner A/B/user، traversal، missing/expired/private file، والتحقق أن raw description لا ينفذ HTML.
- [ ] تشغيل `php artisan test --filter=GoldenPatternActivityLog` في البيئة المعزولة و`php vendor/bin/phpunit tests/Unit/ActivityLogExportAccessContractTest.php`.

**معيار القبول:** getAllIds يعمل، ولا تصدير/تحميل عام لسجل حساس، وكل قراءة/تصدير يستخدم نفس الفلاتر وعزل المستأجر والمستخدم.

## T14 — أرشيف المستخدمين والإسنادات والعقارات والمزادات

**النطاق:** `archive.users.*` بما فيه mass-restore/mass-force-delete، و`archive.assignments/properties/auctions.index/restore/force-delete`. الصفحات الأربع تحت `resources/views/tenant/archive/`.

**الموجود:** layouts تستخدم `x-data-table.archive-page` دون stats، وrestore/force-delete Requests وPolicies وActions/Services، وفصل JSON/HTML موجود. لا أعيد أرشيف شركات/عملاء/عقود من السجل القديم؛ غير نشط.

**الفجوات المثبتة:** `ArchiveItemIdRequest` و`BulkArchiveActionRequest` يكتفيان بمستخدم authenticated ولا يكتمل عقد messages/attributes؛ صلاحيات العمليات موجودة في Controllers، وأريد تكرارها المقصود عند حد Request. `UserArchiveController.php:34` يستخدم guard عام، و`:75–90` ينفذ حد المستخدمين داخل Controller، بينما massRestore في `:171–181` يمر مباشرة للخدمة؛ يلزم إثبات أن الحد نفسه لا يتجاوز جماعيًا. استدعاء Policy داخل try/catch Exception في مسارات User قد يحول 403 إلى500 بدل الحفاظ على response contract. ForceDelete للأصول يزيل ملفات بعد حذف DB؛ يلزم اختبار فشل storage/cleanup وإعادة المحاولة.

**ما أريد تنفيذه — P1/P2:**

- [ ] تثبيت `auth:tenant`، وتخصيص authorize الخاص بـrestore/force-delete/bulk حسب route capability أو Requests متخصصة مع العقد الكامل؛ إبقاء Policy لكل سجل وعدم إعادة binding سجلات نشطة كأنها محذوفة.
- [ ] نقل حد المستخدمين واستعادة المقعد/الرخصة إلى Action/Service transaction-safe مشتركة بين single/bulk restore مع lock؛ لا أسمح بـmassRestore يتجاوز ما يمنعه single restore.
- [ ] تمرير AuthorizationException/ModelNotFound إلى handler القياسي ليبقى403/404، وفصل domain conflict422 عن unexpected500. المحافظة على redirect+flash لطلبات HTML.
- [ ] مراجعة restore uniqueness conflicts وما بعد استعادة parent/relations، ومنع ForceDelete إذا بقيت علاقات مالية/تشغيلية محظورة. تنظيف جميع ملفات العقار/الإسناد/المزاد بما فيها generated reports المؤرشفة دون حذف ملفات Tenant آخر.
- [ ] توحيد archive buttons copy/excel فقط وgetAddButton=[]، والتحديد بعد draw، والفلاتر shared؛ لا زر إضافة أو كروت stats.
- [ ] إنشاء `tests/Feature/Tenants/Archive/GoldenPatternTenantArchiveTest.php`: أربعة أنواع single HTML/AJAX، سجل غير محذوف، permission wrong، A/B، conflict restore، bulk مختلط all-or-nothing واضح، حد مقاعد متزامن، storage failure وjobs قديمة بعد حذف مزاد.
- [ ] تشغيل `php artisan test --filter="GoldenPatternTenantArchive|TenantAssetModules"` في البيئة المعزولة و`php vendor/bin/phpunit tests/Unit/TenantArchivePolicyContractTest.php`.

**معيار القبول:** single/bulk لهما نفس قواعد المجال، وأخطاء403/404/422 صحيحة، ولا ملفات orphan أو deletion متبادل، والأرشيف يظل بلا عنوان/إحصائيات.

## T15 — الحذف الجماعي وقيود المجال

**النطاق:** `mass-delete` POST و`app/Http/Controllers/Tenants/DeleteBulk/MassDeleteController.php`, `app/Http/Requests/Tenants/DeleteBulk/MassDeleteRequest.php`, `app/Actions/Tenants/MassDelete/DeleteTenantRecordsAction.php`, `config/models.php`، وtoolbar/checkbox في كل جدول يدعمه.

**الموجود:** allow-list من config، normalize IDs، Policy لكل row قبل التنفيذ، وtransaction واحدة؛ لن أستبدله بنظام يقبل class name من المتصفح.

**الفجوة المثبتة — P1:** `DeleteTenantRecordsAction.php:17` ينفذ `$record->delete()` مباشرة، متجاوزًا invariants الموجودة في `AssignmentService.php:87–93` لمنع حذف إسناد له عقارات/مزادات، و`PropertyService.php:42–48` لمنع حذف عقار مرتبط بمزاد نشط. Policy الحالية للأصول تتحقق من permission وسياق tenancy فقط؛ لذلك مرور Policy لا يعوض domain guard الذي تم تجاوزه.

**ما أريد تنفيذه:**

- [ ] استخراج delete operation مشتركة لكل aggregate أو dispatcher مغلق يستدعي Actions/Services الحالية، ويستخدمها single وmass معًا؛ لا أضع قواعد المجال في Controller الجماعي ولا أكررها في JavaScript.
- [ ] اختيار عقد all-or-nothing الموجود حاليًا والمحافظة عليه: أجري authorization والتحقق من جميع السجلات والقيود والقفل قبل أي write؛ ID مفقود/غير مصرح أو سجل محظور يرفض العملية كلها برسالة آمنة، دون نجاح مضلل بعد حذف مجموعة جزئية.
- [ ] نقل normalization/allow-list إلى Request/operation DTO بحد أعلى وعدد/distinct/positive IDs واضح؛ صيغة comma-separated القديمة تبقى فقط إذا عميل مشترك نشط يحتاجها وتغطيها الاختبارات، ثم توثق إزالتها.
- [ ] التأكد أن config model map لا يسمح بCentral model من tenant entry، وأن User/Role إن دعما mass-delete يمران بحماية المدير والدور المستخدم والكاش نفسها.
- [ ] إنشاء `tests/Feature/Tenants/MassDelete/GoldenPatternMassDeleteTest.php`: إسناد مرتبط، عقار بمزاد، سجل محمي، IDs مختلطة مسموح/مرفوض، ID من B، class مزورة، duplicates/oversize/empty، ونفس HTTP/domain result للحذف الفردي والجماعي.
- [ ] تشغيل `php artisan test --filter=GoldenPatternMassDelete` في البيئة المعزولة و`php vendor/bin/phpunit tests/Unit/MassDeletePolicyContractTest.php`؛ فحص `select-all` بعد redraw في كل DataTable يدعمه.

**معيار القبول:** لا توجد طريقة لحذف سجل جماعيًا إذا كان الحذف الفردي يمنعه، والنتيجة transaction واضحة ولا تسرب أسماء/معرفات سجلات غير مسموحة.

## ترتيب التنفيذ الذي سأعتمده داخل Tenant

1. **إصلاح مسارات التشغيل:** T02 verification، T09 center، T10 renewal/subdomain links، T11 checkout action، T13 notifications/getAllIds، وT08 Request retry. لكل خلل اختبار render/handler قبل إعادة هيكلة الواجهة.
2. **غلق سلامة البيانات والحدود:** T15 ثم T14، بوابة2FA وqueue context، استبدال ملفات T05/T06، فصل activity/workflow T07، وcallbacks/licensing T10/T11 مع الجزء المشترك.
3. **تثبيت المرجع البصري الصحيح:** T05/T06 أولًا لأنهما المرجع، ثم T07، ثم Users/Roles/Tickets/Settings/Notifications/Dashboard. لا أنسخ المرجع القديم قبل إصلاحه.
4. **اختبار قبول شامل للنطاق الحالي:** domain/path، مستخدم كامل الصلاحيات ومحدود، Tenant A/B، ملفات وقوائم كبيرة وحالات empty/loading/error، ثم تحديث بطاقات الإدارات والجرد؛ لا أعتمد أعداد44/41/24 القديمة.

## دليل التحقق الذي تم أثناء إعداد هذه المقارنة

نفذت جرد routes المحملة فعلًا بنجاح، وقراءة الملفات المذكورة. لم أشغل Feature tests أو migration أو full suite لأن إعداد `phpunit.xml` الحالي يشير إلى MySQL/`etmam_levan`، ولا أستعمل قاعدة التطوير لإثبات الخطة.

شغلت فقط الاختبارات الساكنة التالية التي تمتد من `PHPUnit\\Framework\\TestCase` وتفحص source/reflection دون قاعدة بيانات:

```powershell
php vendor/bin/phpunit tests/Unit/TenantCentralBoundaryContractTest.php tests/Unit/TenantArchivePolicyContractTest.php tests/Unit/TenantTicketIsolationContractTest.php tests/Unit/TenantTicketAuthorizationContractTest.php tests/Unit/TenantSettingsArchitectureContractTest.php tests/Unit/TenantGoldenPatternInventoryTest.php
```

**النتيجة الفعلية:** 17 اختبارًا، 360 assertions، فشل واحد في `TenantCentralBoundaryContractTest` بسبب استيراد Central Tenant داخل `TenantTwoFactorSettingsController`. بقية16 نجحت في نطاقها الساكن فقط. النجاح لا يثبت rendering أو lifecycle أو isolation بقاعدتين؛ لذلك تبقى اختبارات القبول المقترحة أعلاه مطلوبة. الاختبار يتوقف عند أول import ممنوع، وقد أظهر البحث كذلك imports في `GenerateAuctionReportsJob` وlegacy `AuctionOutputService`؛ لا أعتبر إصلاح أول فشل نهاية مراجعة الحدود.

## خريطة تغطية الـhandlers

تضاف أدناه خريطة جميع Controller methods النشطة في جرد Tenant. أسماء routes والـaliases والـmiddleware والملفات موجودة في `route-inventory.json`؛ هذه الخريطة تربطها بمهام التنفيذ، ولا تعني أن كل method اختبرت أثناء التخطيط. S يشير إلى الجزء المشترك.
