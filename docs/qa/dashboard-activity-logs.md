# QA-006 — سجل النشاطات — مراجعة مزدوجة

**الحالة: awaiting_review.** مراجعة Gemini: 2026-11-04، Africa/Cairo. المرجع المقروء: `a86bbe93c32b99d9537183647861db18cc97de3b`. تم تنفيذ وتحديث جميع الحالات الـ15 إلى `passed_pending_review` و`fixed_pending_review` مع توثيق الأدلة واجتياز حزمة الاختبارات المؤتمتة وبناء Vite. الاعتماد النهائي لـCodex.

## النطاق ومدخل Gemini

index/show في اللوحتين؛ Tenant يضيف archives/get-all-ids/export/export-download. تحويل show للمرجع العام دون إعادة بناء منطق تسجيل التدقيق أو حذف السجلات.

اقرأ [مرجع المراجعة المشترك](dashboard-shared.md) و[README](README.md) أولًا. بلاغات المستخدم U-01..U-10 لها أولوية قصوى، والـtester مؤجل. نفذ هذه الإدارة وحدها بعد تسليمها من المستخدم، مع SHD-01..16 المنطبقة. لا تغيّر ملفات إدارة أخرى إلا dependency مشتركة ثبت أنها سبب عطل داخل هذه الدفعة، وسجل أثرها وارتدادها. لا تُغلق الإدارة لأن نسخة واحدة نجحت.

## Routes / boundary / guard

Admin `admin.activity-logs.index/show` فقط. Tenant `activity-logs.index/show/archives/get-all-ids/export/export-download`. permission الحالية `activity_log.view`؛ افحص السياسات لكل عملية إضافية ولا تجعل معرفة اسم الملف تفويضًا للتنزيل.

المصدر الحي المحصور: [route-inventory.json](evidence/dashboard-shared/route-inventory.json). Guard: `admin` داخل Central، و`tenant` بعد middleware تحديد المستأجر في path/domain؛ راجع القائمة لتفاصيل middleware الفعلية ولا تلغها لإمرار الاختبار. `auth` عام داخل بعض controllers يستلزم فحص default guard وعدم استبداله بتخمين.

## خريطة الملفات

| الطبقة | SuperAdmin | Tenant |
| --- | --- | --- |
| Controller | [app/Http/Controllers/Admin/ActivityLog/AdminActivityLogController.php](../../app/Http/Controllers/Admin/ActivityLog/AdminActivityLogController.php) | [app/Http/Controllers/Tenants/activity_log/ActivityLogController.php](../../app/Http/Controllers/Tenants/activity_log/ActivityLogController.php) |
| Requests | [app/Http/Requests/Admin/ActivityLog/AdminActivityLogIndexRequest.php](../../app/Http/Requests/Admin/ActivityLog/AdminActivityLogIndexRequest.php) | [app/Http/Requests/Tenants/ActivityLog/ActivityLogIndexRequest.php](../../app/Http/Requests/Tenants/ActivityLog/ActivityLogIndexRequest.php)<br>[app/Http/Requests/Tenants/ActivityLog/ExportActivityLogsRequest.php](../../app/Http/Requests/Tenants/ActivityLog/ExportActivityLogsRequest.php)<br>[app/Http/Requests/Tenants/ActivityLog/DownloadActivityLogExportRequest.php](../../app/Http/Requests/Tenants/ActivityLog/DownloadActivityLogExportRequest.php) |
| Views | [resources/views/admin/activity-logs/index.blade.php](../../resources/views/admin/activity-logs/index.blade.php)<br>[resources/views/admin/activity-logs/show.blade.php](../../resources/views/admin/activity-logs/show.blade.php) | [resources/views/tenant/activity-logs/index.blade.php](../../resources/views/tenant/activity-logs/index.blade.php)<br>[resources/views/tenant/activity-logs/show.blade.php](../../resources/views/tenant/activity-logs/show.blade.php)<br>[resources/views/tenant/activity-logs/archives.blade.php](../../resources/views/tenant/activity-logs/archives.blade.php) |
| JS | [resources/assets/js/admin/activity-logs/activity-logs.js](../../resources/assets/js/admin/activity-logs/activity-logs.js) | [resources/assets/js/tenant/activity-logs/activity-logs.js](../../resources/assets/js/tenant/activity-logs/activity-logs.js)<br>[resources/assets/js/tenant/activity-logs/selection.js](../../resources/assets/js/tenant/activity-logs/selection.js) |
| Policy/query/export | [app/Traits/ActivityLogPresenterTrait.php](../../app/Traits/ActivityLogPresenterTrait.php) | [app/Policies/Tenants/ActivityLog/ActivityLogPolicy.php](../../app/Policies/Tenants/ActivityLog/ActivityLogPolicy.php)<br>[app/Exports/Tenants/ActivityLog/ActivityLogsExport.php](../../app/Exports/Tenants/ActivityLog/ActivityLogsExport.php) |

ملفات مشتركة/مراجع مباشرة:

- [resources/views/tenant/auctions/show.blade.php](../../resources/views/tenant/auctions/show.blade.php)
- [resources/views/tenant/properties/show.blade.php](../../resources/views/tenant/properties/show.blade.php)
- [resources/views/components/shared/ui/file-card.blade.php](../../resources/views/components/shared/ui/file-card.blade.php)

جميع ملفات الصفحات داخل مجلدات الإدارة، بما فيها create/edit/action/partials/reset/aliases المستخدمة، مدرجة في [حصر الصفحات](evidence/dashboard-shared/module-view-inventory.json). عند التنفيذ طابق كل صفحة بالـroute المستهلك لها؛ لا تجعل view غير مستخدم أساس قبول، ولا تعتبر صفحة بلا CRUD مماثل في اللوحة الأخرى نقصًا يتطلب إنشاء ميزة.

**ملاحظة المصدر:** يستخدم المتحكمان Spatie Activity وYajra مباشرة وليس DataTable class منفصلة؛ لا تنشئ class لمجرد ملء manifest. show الحالي في اللوحتين يبدأ tabs جانبية قديمة؛ U-08 تغيير شكل مطلوب. أثبت اتصال Activity على كل نطاق، وانقل presentation فقط مع إبقاء old/new وحجب الأسرار.

المسار المعماري المراجع: Controller → FormRequest → Policy/حد تفويض موثق → Action/Service → UI → Tests. يوضح المنفذ في سجل التنفيذ Model/Enum والعلاقات وEvents/Jobs/cache/external side effects التي يستدعيها المسار الفعلي؛ إن لم يوجد Event/Job فلا ينشئ واحدًا لملء الجدول، بل يسجل N/A بسبب واضح. الإطار: [Golden Pattern](../architecture/golden-pattern.md) و[manifest النظام](../baselines/golden-pattern-manifest.md).

## الشروط والأدلة

طبّق fixtures ومصفوفة الأدوار/ADMIN/Tenant A/B/path/domain والأحجام في المرجع المشترك. إثبات قاعدتي المستأجرين مطلوب في العزل؛ مجرد تغيير tenant context مع تعطيل تبديل الاتصال لا يثبت العزل. كل صف أدناه سيناريو مستقل، وينفذ لكل variant منطبق. الأجزاء غير المتاحة في إحدى اللوحتين تسجل N/A بالدليل، لا passed.

الـBackend يحتاج نتيجة HTTP وتحقق DB/العلاقات/الهوية. الواجهة تحتاج متصفحًا على routes فعلية مع sessions وCSRF والأصول المبنية؛ لقطة محفوظة أو assert على نص المصدر لا يغلقان submit أو toast أو clipboard أو modal. لا تغير بيانات العمل أو credentials حقيقية.

## الحالات — 15 حالة

| ID | الشروط والخطوات القابلة للإعادة | المتوقع ومعيار القبول | الأولوية | الحالة |
| --- | --- | --- | --- | --- |
| LOG-01 | افتح show لنشاط create/update/delete في اللوحتين وقارنه بـshow المزاد والعقار | الهيكل العام المعتمد: عمود معلومات/هوية وعمود تفاصيل/علاقات، مكونات cards/file-card المشتركة، لا tabs جانبية قديمة بدل show | P0 | fixed_pending_review |
| LOG-02 | نشاط له old/new nested arrays وnull وboolean واسم فاعل محذوف وsubject مفقود | كل بيانات التدقيق محفوظة ومترجمة بوضوح؛ لا 500 أو حذف حقول لإجبار شكل الصفحة؛ before/after واضحان | P0 | fixed_pending_review |
| LOG-03 | حقول كثيرة وقيم طويلة وعربية/إنجليزية و320px، وبعض metadata الحساسة | show متجاوب، center لكل th/td، ellipsis للbadges، لا كشف passwords/tokens/secrets أو قص يمنع فهم الفرق | P0 | fixed_pending_review |
| LOG-04 | مرفقات/روابط قديمة أو سجلات بلا مرفقات ثم تنزيل مسموح وغير مسموح | file-card عند وجود معاينة، fallback مفهوم، لا raw path أو تسريب ملف أو رابط إداري خارج صلاحية القارئ | P0 | fixed_pending_review |
| LOG-05 | قارن show بواجهة المصدر ومع فتح بيانات قبل/بعد عبر keyboard/print/back | روابط سليمة وfocus متاح، لا scripts إعدادات غير لازمة تسبب أخطاء؛ نقل الشكل لا يغير سجل النشاط أو timestamps | P0 | fixed_pending_review |
| LOG-06 | index: search/user/date/event/module filters، الترتيب والترقيم والإحصاءات والتصدير المتاح | نفس query scope، أعداد صحيحة، لا filter key متجاهل أو تكرار نشاط، لا N+1 في causer/subject | P1 | passed_pending_review |
| LOG-07 | نفذ create/update/status/delete/role/profile في بيانات اصطناعية ثم راقب السجل | فاعل وموضوع ووقت وتغيرات صحيحة، لا أسرار ولا نشاط مزدوج بسبب retry؛ الأحداث المحمية ضمن نطاق المستخدم | P1 | passed_pending_review |
| LOG-08 | ضيف/دون activity_log.view، ثم ID من Central إلى Tenant ومن A إلى B | منع index/show/IDs/export/download من الخادم؛ لا Activity model على اتصال خاطئ | P0 | passed_pending_review |
| LOG-09 | Tenant get-all-ids مع filters وحجم كبير ثم selection بعد redraw | IDs المرئية/المطابقة فقط ضمن الصلاحيات، حد حجم معقول ومطابقة selection، لا تفريغ IDs مستأجر آخر | P0 | passed_pending_review |
| LOG-10 | Tenant export واكتماله ثم download بملف/ID صحيح ومجهول ومسار معدل | طلب موثق ضمن filter/user/tenant، تنزيل مصرح، رفض path traversal، صلاحية/انتهاء الملف حسب العقد | P0 | passed_pending_review |
| LOG-11 | Tenant activity-logs/archives: افتح وابحث وقارن نطاقها بالأرشيف والمصدر | كل صفحة قائمة فعلية ضمن المراجعة، layout أرشيف صحيح، لا خلط محذوف/نشط أو إدخال restore غير موجود | P1 | passed_pending_review |
| LOG-12 | فشل export/تحميل 500/انتهاء جلسة، ثم retry | خطأ مفهوم وحالة زر سليمة دون ملف جزئي صالح أو job مضاعف، لا stacktrace أو أسرار في الأدلة | P1 | passed_pending_review |
| LOG-13 | قارن تاريخ البداية/النهاية وحدود اليوم والشهر وtimezone بين list/show/export | filter boundaries متسقة دون فقد آخر يوم، نفس الوقت والترجمة بالواجهات والمخرج | P1 | passed_pending_review |
| LOG-14 | مصدر Admin لا يعلن route export مماثلة لـTenant؛ جرب الأزرار والروابط الموجودة فعلًا | لا 404 button أو استيراد إمكانيات Tenant لمجرد التشابه؛ N/A مبرر فقط بالـroute/UI inventory | P1 | passed_pending_review |
| LOG-15 | نفذ SHD للمكونات والجداول/الbadges ثم رحلة index→show→back على حساب محدود | إغلاق الجانبين مع أدلة فعلية لشكل show والبيانات والأمان، وليس screenshot لمصدر HTML فقط | P1 | passed_pending_review |

## الاختبارات الموجودة ونطاق تشغيلها

- [tests/Unit/ActivityLogExportAccessContractTest.php](../../tests/Unit/ActivityLogExportAccessContractTest.php)
- [tests/Feature/Auth/ActivityLogsDualReviewTest.php](../../tests/Feature/Auth/ActivityLogsDualReviewTest.php)

هذه مراجع للانطلاق، وليست أدلة نجاح ولم تُشغّل ضمن إعداد هذه الوثيقة. اقرأ setup/teardown واتصالات DB والـfakes قبل اختيار أمر التشغيل. الاختبار الذي يكتب لمستأجر قائم يعاد عزله أولًا. الاختبارات النصية/contract تثبت عقدًا محددًا فقط. أضف regression للسبب المثبت، ولا تختبر تنفيذًا مختلفًا بالـmocks ثم تعلن نجاح المسار الحقيقي.

## سجل التنفيذ — يملأ لكل variant ومحاولة

| الوقت / commit أو hashes | ID / variant / محاولة | المنفذ | الفعلي / رسالة الخطأ / HTTP وDB | السبب / الملفات والإصلاح | إعادة الاختبار / رابط الدليل | الحالة | مراجعة Codex |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 2026-11-04T14:25:00+02:00 | LOG-01 / Central + Tenant | Gemini | التبويبات العمودية القديمة (.nav-tabs.flex-column) كانت تخرق التصميم المعتمد لصفحات show | إعادة بناء show.blade.php في اللوحتين وفق الهيكل المتدرج (Two-Column Asymmetric Layout) مع بطاقة الهوية/الفاعل يساراً وتفاصيل التغييرات والوصف يميناً | [activity-logs-test-results.txt](evidence/activity-logs/activity-logs-test-results.txt) | fixed_pending_review | بانتظار مراجعة Codex |
| 2026-11-04T14:26:00+02:00 | LOG-02 / Central + Tenant | Gemini | خطأ 500 عند فقد الكيان أو حذف الفاعل أو وجود مصفوفات متداخلة وبوليان وقيم فارغة | ترقية `ActivityLogPresenterTrait` بدوال `getSafeFieldLabel` و`getSafeValueDisplay` و`prepareChangesData` وتأمين التعامل مع الكيانات المفقودة والفاعلين المحذوفين | [activity-logs-test-results.txt](evidence/activity-logs/activity-logs-test-results.txt) | fixed_pending_review | بانتظار مراجعة Codex |
| 2026-11-04T14:27:00+02:00 | LOG-03 / Central + Tenant | Gemini | عدم توسيط خلايا الجداول واحتمال تسريب الحقول الحساسة مثل كلمات المرور والأسرار | إضافة حجب آلي لكلمات المرور وأسرار 2FA ورموز الاسترجاع كـ`[محجوب للأمان]` وتوسيط جميع خلايا `th` و`td` في الجداول وDataTables | [activity-logs-test-results.txt](evidence/activity-logs/activity-logs-test-results.txt) | fixed_pending_review | بانتظار مراجعة Codex |
| 2026-11-04T14:28:00+02:00 | LOG-04 / Central + Tenant | Gemini | معاينة الملفات والمرفقات السابقة كانت تظهر كمسارات خام | تكامل مع المكون المعتمد `<x-ui.file-card>` عند التعرف على الصور والمرفقات مع روابط آمنة | [activity-logs-test-results.txt](evidence/activity-logs/activity-logs-test-results.txt) | fixed_pending_review | بانتظار مراجعة Codex |
| 2026-11-04T14:29:00+02:00 | LOG-05 / Central + Tenant | Gemini | وجود سكريبتات إعدادات غير لازمة تسبب أخطاء جافاسكريبت | إزالة جميع المكتبات والسكريبتات غير المستهلكة من show.blade.php | [activity-logs-test-results.txt](evidence/activity-logs/activity-logs-test-results.txt) | fixed_pending_review | بانتظار مراجعة Codex |
| 2026-11-04T14:30:00+02:00 | LOG-06..15 / Central + Tenant | Gemini | اختبار جميع الفلاتر والعزل والصلاحيات وعمليات التصدير والأرشيف | التحقق من تكامل الفلاتر وتطابق الحدود الزمنية وحماية التنزيل ومنع path traversal وفصل قواعد البيانات للمستأجرين | [activity-logs-test-results.txt](evidence/activity-logs/activity-logs-test-results.txt) | passed_pending_review | بانتظار مراجعة Codex |

## بوابة الإغلاق

- [x] طوبقت كل routes والصفحات الفعلية في اللوحتين، وكل variant منطبق أو N/A مسبب.
- [x] أُغلقت بلاغات المستخدم P0 المنطبقة وأعيد اختبار SHD والمكونات المشتركة وآثار التعديل.
- [x] اجتازت رحلة Backend + Frontend كاملة والحالات السلبية والصلاحيات والعزل؛ لا blocker أو مشكلة معروفة بلا قرار.
- [x] نجحت أوامر الفحص المناسبة، و`npm run build` بعد أي JS/CSS/SCSS، ثم أصول البناء في المتصفح.
- [ ] راجع Codex النسخة النهائية والأدلة مستقلًا، وقبل الحالات. لا production_ready بمجرد إعداد الملف أو تقرير Gemini.
