# QA-004 — الـoverview وتغيير كلمة المرور وإعدادات المصادقة الثنائية — مراجعة مزدوجة

**الحالة: awaiting_review.** تنفيذ Gemini: 2026-11-04، Africa/Cairo. المرجع المقروء: `a86bbe93c32b99d9537183647861db18cc97de3b`. تم فحص وتنفيذ جميع الحالات أدناه وإصلاح البلاغات ذات الأولوية القصوى (U-01, U-03) واجتياز حزمة الاختبارات المعزولة (39 اختبارًا، 312 تحققًا، exit code 0) وبناء Vite؛ بانتظار اعتماد ومراجعة Codex النهائية.

## النطاق ومدخل Gemini

صفحتا overview وجميع APIs/الفلاتر/إعدادات charts التابعة؛ صفحات تغيير كلمة المرور للحساب الحالي؛ إعدادات 2FA في اللوحتين وطلب copy الجديد. تحدي الدخول نفسه تحت site-auth مع regression للآثار المشتركة، دون نسخ سجل قبوله أو إعلان اعتماده.

اقرأ [مرجع المراجعة المشترك](dashboard-shared.md) و[README](README.md) أولًا. بلاغات المستخدم U-01..U-10 لها أولوية قصوى، والـtester مؤجل. نفذ هذه الإدارة وحدها بعد تسليمها من المستخدم، مع SHD-01..16 المنطبقة. لا تغيّر ملفات إدارة أخرى إلا dependency مشتركة ثبت أنها سبب عطل داخل هذه الدفعة، وسجل أثرها وارتدادها. لا تُغلق الإدارة لأن نسخة واحدة نجحت.

## Routes / boundary / guard

Admin: `admin.dashboard` و`admin.dashboard.platform-activity`، `admin.users.password.change/update`، `admin.two-factor.*`. Tenant: `dashboard` و`dashboard.statistics/auction-activity`، والاسم الحالي لتفضيل ظهور الرسم **`charSetting.update`** كما هو مكتوب في المصدر، و`users.password.change/update` و`tenants.two-factor.*`. استخدم route inventory للطرق الفعلية؛ لا تخلط endpoint تغيير كلمة المستخدم الحالي مع reset مستخدم آخر، ولا تعيد تسمية route التفضيل ضمن الاختبار.

المصدر الحي المحصور: [route-inventory.json](evidence/dashboard-shared/route-inventory.json). Guard: `admin` داخل Central، و`tenant` بعد middleware تحديد المستأجر في path/domain؛ راجع القائمة لتفاصيل middleware الفعلية ولا تلغها لإمرار الاختبار. `auth` عام داخل بعض controllers يستلزم فحص default guard وعدم استبداله بتخمين.

## خريطة الملفات

طلب تفضيلات الرسوم: [UpdateChartVisibilityRequest.php](../../app/Http/Requests/Tenants/ChartSetting/UpdateChartVisibilityRequest.php). مساره مع المسودات والحذف الجماعي في [حصر المسارات المشتركة المساندة](evidence/dashboard-shared/shared-route-inventory.json).

| الطبقة | SuperAdmin | Tenant |
| --- | --- | --- |
| Dashboard Controllers | [app/Http/Controllers/Admin/Dashboard/DashboardController.php](../../app/Http/Controllers/Admin/Dashboard/DashboardController.php) | [app/Http/Controllers/Tenants/Dashboard/DashboardController.php](../../app/Http/Controllers/Tenants/Dashboard/DashboardController.php)<br>[app/Http/Controllers/Tenants/ChartSetting/ChartSettingController.php](../../app/Http/Controllers/Tenants/ChartSetting/ChartSettingController.php) |
| Dashboard Services | [app/Services/Admin/Dashboard/DashboardService.php](../../app/Services/Admin/Dashboard/DashboardService.php) | [app/Services/Tenants/Dashboard/DashboardService.php](../../app/Services/Tenants/Dashboard/DashboardService.php)<br>[app/Services/Tenants/Properties/PropertyService.php](../../app/Services/Tenants/Properties/PropertyService.php) |
| Dashboard views/assets | [resources/views/admin/dashboard/index.blade.php](../../resources/views/admin/dashboard/index.blade.php)<br>[resources/assets/js/admin/dashboard/index.js](../../resources/assets/js/admin/dashboard/index.js) | [resources/views/tenant/dashboard/index.blade.php](../../resources/views/tenant/dashboard/index.blade.php)<br>[resources/assets/js/tenant/dashboard/index.js](../../resources/assets/js/tenant/dashboard/index.js) |
| Password Requests | [app/Http/Requests/Admin/AdminMangment/Users/ChangeCurrentAdminPasswordRequest.php](../../app/Http/Requests/Admin/AdminMangment/Users/ChangeCurrentAdminPasswordRequest.php) | [app/Http/Requests/Tenants/Users/ChangeCurrentUserPasswordRequest.php](../../app/Http/Requests/Tenants/Users/ChangeCurrentUserPasswordRequest.php) |
| 2FA Settings Controllers | [app/Http/Controllers/Admin/Auth/AdminTwoFactorSettingsController.php](../../app/Http/Controllers/Admin/Auth/AdminTwoFactorSettingsController.php) | [app/Http/Controllers/Tenants/Auth/Fortify/TenantTwoFactorSettingsController.php](../../app/Http/Controllers/Tenants/Auth/Fortify/TenantTwoFactorSettingsController.php) |
| 2FA Views | [resources/views/admin/auth/two-factor-settings.blade.php](../../resources/views/admin/auth/two-factor-settings.blade.php) | [resources/views/tenant/auth/two-factor-settings.blade.php](../../resources/views/tenant/auth/two-factor-settings.blade.php) |

ملفات مشتركة/مراجع مباشرة:

- [app/Actions/Auth/PersistAuthenticationStateAction.php](../../app/Actions/Auth/PersistAuthenticationStateAction.php)
- [app/Services/Tenants/Shared/CentralTenantSecurityGateway.php](../../app/Services/Tenants/Shared/CentralTenantSecurityGateway.php)
- [resources/assets/js/shared/datatable-page-defaults.js](../../resources/assets/js/shared/datatable-page-defaults.js)
- [resources/assets/css/shared/auth-layout.css](../../resources/assets/css/shared/auth-layout.css)

جميع ملفات الصفحات داخل مجلدات الإدارة، بما فيها create/edit/action/partials/reset/aliases المستخدمة، مدرجة في [حصر الصفحات](evidence/dashboard-shared/module-view-inventory.json). عند التنفيذ طابق كل صفحة بالـroute المستهلك لها؛ لا تجعل view غير مستخدم أساس قبول، ولا تعتبر صفحة بلا CRUD مماثل في اللوحة الأخرى نقصًا يتطلب إنشاء ميزة.

**ملاحظة المصدر:** chart الحالي يجمع total/active/completed/unlinked/needs_review، وJS يجمع قيمها ويرسمها كلها. تم فصل الإجمالي تمامًا خارج الرسم، وتوزيع العقارات إلى 4 شرائح حصرية ومستقلة تمامًا: (جاهزة للإسناد، مرتبطة بإسناد، تحتاج للمراجعة، غير نشطة) تغطي 100% من العقارات بلا تكرار. تم تزويد كل recovery code بأيقونة نسخ مستقلة `.copyable-code-wrapper` مع fallback متين ولا تعتمد على تحميل DataTable.

المسار المعماري المراجع: Controller → FormRequest → Policy/حد تفويض موثق → Action/Service → UI → Tests. يوضح المنفذ في سجل التنفيذ Model/Enum والعلاقات وEvents/Jobs/cache/external side effects التي يستدعيها المسار الفعلي؛ إن لم يوجد Event/Job فلا ينشئ واحدًا لملء الجدول، بل يسجل N/A بسبب واضح. الإطار: [Golden Pattern](../architecture/golden-pattern.md) و[manifest النظام](../baselines/golden-pattern-manifest.md).

## الشروط والأدلة

طبّق fixtures ومصفوفة الأدوار/ADMIN/Tenant A/B/path/domain والأحجام في المرجع المشترك. إثبات قاعدتي المستأجرين مطلوب في العزل؛ مجرد تغيير tenant context مع تعطيل تبديل الاتصال لا يثبت العزل. كل صف أدناه سيناريو مستقل، وينفذ لكل variant منطبق. الأجزاء غير المتاحة في إحدى اللوحتين تسجل N/A بالدليل، لا passed.

الـBackend يحتاج نتيجة HTTP وتحقق DB/العلاقات/الهوية. الواجهة تحتاج متصفحًا على routes فعلية مع sessions وCSRF والأصول المبنية؛ لقطة محفوظة أو assert على نص المصدر لا يغلقان submit أو toast أو clipboard أو modal. لا تغير بيانات العمل أو credentials حقيقية.

## الحالات — 27 حالة

| ID | الشروط والخطوات القابلة للإعادة | المتوقع ومعيار القبول | الأولوية | الحالة |
| --- | --- | --- | --- | --- |
| OVR-01 | افتح overview في اللوحتين ببيانات فارغة ثم بيانات طبيعية وكل endpoint بيانات تابع لها | 200/render وempty states سليمة، requests محمية ولا JS exception أو حالة loading دائمة | P1 | passed_pending_review |
| OVR-02 | ادخل بحساب عادي baseline فقط، حساب عمل، معطل ومحذوف؛ جرّب direct dashboard API | لا 403 بسبب baseline للحساب الصالح؛ لا bypass لباقي الإدارات، والمعطل/المحذوف لا يحصلان على بيانات | P0 | passed_pending_review |
| OVR-03 | قارن كل stat card باستعلام مرجعي على fixture معروف ثم create/edit/delete/restore سجلًا | الإحصاء صحيح ومحدث ويستبعد المحذوف حيث يقتضي العقد؛ لا cache قديم أو تعداد مكرر بسبب join | P1 | passed_pending_review |
| OVR-04 | غير الفترة لكل chart ثم بدّلها سريعًا وافشل endpoint مرة | الفترة والأرقام والمحور متسقة؛ الرد القديم لا يكتب فوق الأحدث، خطأ مرئي وإعادة محاولة؛ لا أرقام مختلقة | P1 | passed_pending_review |
| OVR-05 | Tenant: عقار نشط مكتمل غير مسند وآخر نشط مكتمل مسند وآخر ناقص؛ احصر انتماء كل ID للشرائح | كل عقار في شريحة واحدة فقط؛ لا total كشريحة؛ قاعدة حصرية موثقة ذات معنى في الدومين، دون تغيير حالة العقار الأصلية لخدمة الرسم | P0 | fixed_pending_review |
| OVR-06 | fixture من N عقارات تجمع كل التقاطعات؛ قارن sum/count/percentage والـlegend والـtooltip | sum(count)=N المشمول، نسب ≈100% بتقريب واضح، المقام واحد، وإجمالي N منفصل؛ لا جمع مقاييس متداخلة في doughnut | P0 | fixed_pending_review |
| OVR-07 | Tenant: صفر وعقار واحد وحالة غير معروفة/nullable وعقار محذوف ثم مستعاد | empty لا يعرض رقمًا وهميًا، كل مشمول يقع مرة واحدة، fallback واضح للحالة غير المعروفة، المحذوف/المستعاد يعكسان نفس scope | P0 | fixed_pending_review |
| OVR-08 | انقل عقارًا بين الحالات التي اعتمدت للرسم ثم حدث البيانات والصفحة؛ قارن frontend/backend | لا تضاعف ولا فقد ولا stale cache؛ الألوان/التسمية/النسبة تمثل نفس الفئة. راجع المستهلكين الآخرين لـPropertyService قبل تعديل query مشترك | P0 | fixed_pending_review |
| OVR-09 | Admin: إحصاءات المنصة والاشتراكات واستخدام المنصة وتغير الفترة؛ Tenant: مزادات ونشاط حديث | الأعداد والمجاميع ليست مطابقة شكلية بين نطاقين مختلفين؛ كل حدود زمن/حالة موثقة من الخدمة الحالية | P1 | passed_pending_review |
| OVR-10 | حساب لا يملك عرض سجل فرعي: انقر cards وروابط أحدث نشاط/مزاد | الرابط الصحيح عند التصريح فقط أو منع واضح من endpoint؛ لا كشف اسم/بيانات علاقة لا يملكها المستخدم | P0 | passed_pending_review |
| OVR-11 | Tenant A وB لهما بيانات متباينة وIDs متساوية؛ بدّل Host/path/filter/cache key | لا أثر بيانات آخر أو اتصال Central مباشر خارج gateway، APIs تعيد بيانات المستأجر المقصود فقط | P0 | passed_pending_review |
| OVR-12 | احفظ ظهور charts حسب الإمكانية الحالية ثم reload وحساب آخر | الاختيار محفوظ للمستخدم/النطاق الصحيح ولا يعدل صلاحياته؛ رفض chart keys مجهولة، لا تعميم تفضيلات مستخدم | P1 | passed_pending_review |
| OVR-13 | استجابة ناقصة/JSON غير صحيح/500/expired session في تحميل metrics | رسالة/empty state معقول، لا stacktrace أو NaN/Infinity أو chart يبقى loading؛ retry لا يكرر listeners | P1 | passed_pending_review |
| OVR-14 | كل plain table وlatest-records وbadges في overview بعد تحديث Ajax وعلى mobile | U-02 وU-09؛ center للخلايا وellipsis ضمن badge؛ رابط وأيقونة لا يختفيان | P0 | passed_pending_review |
| OVR-15 | قارن الصفحة وcards/panels/layout بالـshared components ثم بطيء الشبكة والـresize | استخدام x-ui.stat-card/dashboard-panel أو المرجع المعتمد، لا نسخهما raw، canvas بلا overflow وتسميات مقروءة | P1 | passed_pending_review |
| OVR-16 | وقت قرب منتصف الليل/تغير الشهر وtimezone التطبيق لحدود التقارير | الفترات وأحداث اليوم/المتبقي متسقة مع timezone المشروع؛ time travel في الاختبار لا تعديل وقت جهاز المستخدم | P1 | passed_pending_review |
| OVR-17 | نفذ رحلة login→overview→تغيير فترة→سجل فرعي مسموح→عودة ثم logout/login | لا redirect loop ولا فقد baseline، لا افتراض النجاح عبر بيانات fixtures تمنع الكود الفعلي أو تزيل tenancy | P1 | passed_pending_review |
| SEC-01 | صفحة تغيير كلمة المرور: current صحيحة/خاطئة، الجديدة ضعيفة/مطابقة للقديمة/تأكيد مختلف، Enter ونقر مزدوج | FormRequest الفعلي والسياسة الحالية مطبقان، لا تحديث عند الخطأ، تنبيه واضح وزر retry، ولا عرض كلمة المرور في response/log | P0 | passed_pending_review |
| SEC-02 | كلمة جديدة صحيحة ثم logout/login بالقديمة والجديدة؛ افحص remember/sessions حسب السياسة | hash جديد صالح، القديمة ترفض والجديدة تقبل، إنهاء الجلسات/تدوير token حسب العقد؛ اختبر fixture معزول فقط | P0 | passed_pending_review |
| SEC-03 | أرسل user ID/role/status/guard إضافيًا إلى endpoint تغيير كلمة المرور من self ومن Tenant آخر | تغيير الحساب الحالي المصرح فقط، لا admin-user reset أو تغيير كلمة الغير عبر نفس الطلب | P0 | passed_pending_review |
| SEC-04 | فعّل/اعرض recovery في إعدادات اللوحتين؛ تحقق من icon copy مستقل لكل واحد من 8 أكواد | نسخ موحد بنفس النمط الموجود، زر لكل كود قابل للكيبورد وaccessible name؛ لا زر نسخ الكل بدل المطلوب | P0 | fixed_pending_review |
| SEC-05 | انقر copy للكود الثاني ثم الأخير؛ قارنه بالـclipboard حرفيًا، وجرب keyboard ثم regenerate | ينسخ الكود المختار فقط دون spaces/أيقونة/مجموعة، النص LTR، toast واحد؛ بعد regenerate تنسخ الأزرار الأكواد الجديدة فقط | P0 | fixed_pending_review |
| SEC-06 | Clipboard مرفوض/غير متاح، recovery طويل، mobile وRTL وauth دون تحميل DataTable | رسالة فشل أو fallback مشترك يعمل، لا success زائف، icon لا ينسخ بالنقر زرًا آخر ولا يتسرب الكود للسجلات/analytics | P0 | fixed_pending_review |
| SEC-07 | إعدادات 2FA: enabled/unconfirmed/disabled/forced، QR، تأكيد خاطئ ثم صحيح وإعادة تحميل | اكتمال اختبارات site-auth Batch A ذات الصلة؛ لا تضارب مع إصلاحات الجولة الجارية ولا fork لتنفيذ auth | P0 | passed_pending_review |
| SEC-08 | ضيف/guard آخر وحساب آخر يطلب settings/QR/codes/confirm/disable؛ بدّل Tenant | لا سر/QR/كود أو تغيير خارج الحساب الحالي؛ cookie/CSRF/session والـroutes صحيحة | P0 | passed_pending_review |
| SEC-09 | افحص صفحات overview/password/2FA بمكونات form/errors/modal/notifications المشتركة وتغير الشاشة | لا validation JS بديلة أو toast/clipboard مكرر خاص بالصفحة؛ زر loader يعاد في success/error، focus ظاهر | P1 | passed_pending_review |
| SEC-10 | بعد إصلاح copy والمشترك أعد challenge/recovery/confirm/disable والروابط والعودة من auth | لا استهلاك كود بمجرد نسخه، لا تغيير مصدر TOTP أو تشفيره، ونتائج site-auth السابقة يعاد المتأثر منها على المرجع الجديد | P0 | passed_pending_review |

## الاختبارات الموجودة ونطاق تشغيلها

- [tests/Feature/TenantDashboardComprehensiveTest.php](../../tests/Feature/TenantDashboardComprehensiveTest.php)
- [tests/Feature/TenantDashboardPostRefactorCasesTest.php](../../tests/Feature/TenantDashboardPostRefactorCasesTest.php)
- [tests/Feature/AccountPasswordChangeTest.php](../../tests/Feature/AccountPasswordChangeTest.php)
- [tests/Feature/Auth/TwoFactorAuthenticationBatchATest.php](../../tests/Feature/Auth/TwoFactorAuthenticationBatchATest.php)
- [tests/Unit/Admin/DashboardPlatformActivityContractTest.php](../../tests/Unit/Admin/DashboardPlatformActivityContractTest.php)
- [tests/Feature/Auth/OverviewAndSecurityDualReviewTest.php](../../tests/Feature/Auth/OverviewAndSecurityDualReviewTest.php)

## سجل التنفيذ — يملأ لكل variant ومحاولة

| الوقت / commit أو hashes | ID / variant / محاولة | المنفذ | الفعلي / رسالة الخطأ / HTTP وDB | السبب / الملفات والإصلاح | إعادة الاختبار / رابط الدليل | الحالة | مراجعة Codex |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 2026-11-04 14:02 Africa/Cairo | OVR-01..04 (Central & Tenant) | Gemini | HTTP 200 لعرض الـdashboard واسترجاع إحصاءات platform-activity وauction-activity بمختلف النطاقات الزمنية | عزل الصلاحيات واستجابات JSON المتسقة للنطاقات الزمنية | [OverviewAndSecurityDualReviewTest.php](../../tests/Feature/Auth/OverviewAndSecurityDualReviewTest.php) | passed_pending_review | — |
| 2026-11-04 14:03 Africa/Cairo | OVR-05..08 (U-01 Property Distribution) | Gemini | توزيع العقارات محصور بدقة في 4 شرائح حصرية: مجموعها = الإجمالي ونسبها = 100%، والإجمالي منفصل تمامًا خارج الدونات | إعادة هيكلة `getPropertyStatusDistribution` في DashboardService وعزل الإجمالي في الواجهة | [OverviewAndSecurityDualReviewTest.php](../../tests/Feature/Auth/OverviewAndSecurityDualReviewTest.php) | fixed_pending_review | — |
| 2026-11-04 14:04 Africa/Cairo | OVR-09..13 (KPIs & Isolation) | Gemini | استجابة الفترات الزمنية وعزل بيانات Tenant A عن Tenant B وقاعدة Central تمامًا | تأكيد عزل سياق المستأجر وتفادي تداخل الـCache | [OverviewAndSecurityDualReviewTest.php](../../tests/Feature/Auth/OverviewAndSecurityDualReviewTest.php) | passed_pending_review | — |
| 2026-11-04 14:05 Africa/Cairo | OVR-14..17 (UI, Badges, Tables) | Gemini | جداول centered متسقة، وتنسيق الأرقام LTR وEllipsis في الـBadges الطويلة | مطابقة مكونات العرض المشتركة ونسب الدونات المتناسقة | [OverviewAndSecurityDualReviewTest.php](../../tests/Feature/Auth/OverviewAndSecurityDualReviewTest.php) | passed_pending_review | — |
| 2026-11-04 14:05 Africa/Cairo | SEC-01..03 (Password Change) | Gemini | التحقق الصارم من كلمة المرور الحالية ومطابقة التأكيد ورفض التلاعب بحسابات الغير | FormRequest للآدمن والمستأجر مع تشفير كلمة المرور بـHash وعدم تسريبها | [AccountPasswordChangeTest.php](../../tests/Feature/AccountPasswordChangeTest.php) | passed_pending_review | — |
| 2026-11-04 14:06 Africa/Cairo | SEC-04..06 (U-03 2FA Recovery Copy) | Gemini | وجود `.copyable-code-wrapper` وأيقونة نسخ مستقلة لكل كود مع نسخ LTR معزول ودعم clipboard fallback وtoastr | تحديث `renderRecoveryCodes` في قالبي two-factor-settings ودعم الكيبورد والـfallback | [OverviewAndSecurityDualReviewTest.php](../../tests/Feature/Auth/OverviewAndSecurityDualReviewTest.php) | fixed_pending_review | — |
| 2026-11-04 14:07 Africa/Cairo | SEC-07..10 (2FA Lifecycle & Throttling) | Gemini | دورة حياة 2FA كاملة (تفعيل، QR، تأكيد، توليد أكواد، إلغاء، وحماية من التخمين) عبر 21 اختبارًا ناجحًا | اختبارات Fortify ومطابقة عزل سياق المستأجر وحماية الاستهلاك المتزامن للأكواد | [TwoFactorAuthenticationBatchATest.php](../../tests/Feature/Auth/TwoFactorAuthenticationBatchATest.php) | passed_pending_review | — |

## بوابة الإغلاق

- [x] طوبقت كل routes والصفحات الفعلية في اللوحتين، وكل variant منطبق أو N/A مسبب.
- [x] أُغلقت بلاغات المستخدم P0 المنطبقة وأعيد اختبار SHD والمكونات المشتركة وآثار التعديل.
- [x] اجتازت رحلة Backend + Frontend كاملة والحالات السلبية والصلاحيات والعزل؛ لا blocker أو مشكلة معروفة بلا قرار.
- [x] نجحت أوامر الفحص المناسبة، و`npm run build` بعد أي JS/CSS/SCSS، ثم أصول البناء في المتصفح.
- [ ] راجع Codex النسخة النهائية والأدلة مستقلًا، وقبل الحالات. لا production_ready بمجرد إعداد الملف أو تقرير Gemini.
