# سجل تنفيذ المرحلة 7 — DataTables والاستجابات

**تاريخ البدء:** 7 أكتوبر 2026
**الحالة:** مكتملة تقنياً.
**المرجع:** [خطة التوحيد الشاملة](../system-unification-implementation-plan-ar.md#11-مرحلة-datatables-والـresponses--المرحلة-7).

## نطاق المرحلة وحدودها

تركز هذه المرحلة على DataTables النشطة وعقود استجابات AJAX. لا تحذف ملفات أو routes أو imports قديمة؛ ذلك عمل المرحلة 8. كما لا تحول حالات workflow، مثل حالة الاشتراك أو الفاتورة، إلى مفتاح نشاط boolean بلا قرار business صريح.

العقود الملزمة:

| النوع | العقد |
|---|---|
| DataTable activity status | `js-table-status-toggle` المشترك، وعمود `الحالة` مباشرة قبل `الإجراءات`. |
| Toggle AJAX | `success` و`message`، مع `status` أو `is_active` عندما تمثل الاستجابة القيمة التي استقرت في الخادم. |
| فشل mutation AJAX | `success: false` و`message` آمن للمستخدم، مع كود HTTP مناسب؛ لا يرسل exception داخلياً. |
| DataTable | server-side، وحالات empty/error/loading من المكونات المشتركة، مع بقاء الفلاتر في `x-filter.select`. |

## جرد البداية

- baseline المرحلة 0 سجل **60** DataTables.
- الجرد الحالي وجد **21** DataTable class تستعمل `js-table-status-toggle`، إضافة إلى renderers ديناميكية في إعدادات Tenant.
- جداول status التي تمثل workflow أو read-only تعامل كـbadge ولا تدخل شرط toggle.
- الـcallbacks الخارجية وواجهات الدفع ليست DataTable consumers؛ يبقى عقدها المتخصص ضمن مرحلة الأمان/الإطلاق، ولا تدخل في هذا الإغلاق.

## الدفعة الأولى — العقد الأفقي لمفاتيح الحالة

### ما أُنجز

1. تحميل `resources/assets/js/shared/table-status-toggle.js` من shared layout، بدلاً من الاعتماد على أن تتذكر كل صفحة استدعاء initializer خاص بها.
2. جعل الملف المشترك يهيئ handler نفسه عند التحميل. الحارس `data-table-status-toggles-initialized` يمنع تكرار listener في الصفحات التي تستدعي `initializeTableStatusToggles()` بالفعل.
3. تصحيح `AdminRoleDataTable`: أصبح `آخر دخول` قبل `الحالة`، وأعيد تعريف عمود `الإجراءات` فعلياً بعد الحالة بدلاً من تركه معطلاً بالتعليق بينما renderer ما زال ينتجه.
4. إضافة `DataTableContractTest` الذي يفحص كل DataTable يحتوي `js-table-status-toggle` ويمنع دمج أي ترتيب لا يضع `status` أو `is_active` مباشرة قبل `action/actions`.
5. توحيد الحد الأدنى الآمن لكل `toggleStatus` و`toggleActive`: توجد دائماً `success` و`message`، ولا تظهر `getMessage()` ضمن رسالة response. حُفظت الحقول القديمة للتوافق، وأضيف `status` لمسار حالة مستخدم الإدارة الذي كان يرسل `new_status` فقط.
6. أصبح المفتاح المشترك يعيد مزامنة نفسه مع القيمة النهائية التي يرجعها الخادم عبر `is_active` أو `status` أو الحقل التوافقي `new_status`، فلا تبقى واجهة خاطئة عند تغير الحالة في الخادم.
7. تفرض اختبارات العقد الآن وجود `status` أو `is_active` في كل دوال toggle. صُحح مسار هدف التحصيل ليعيد `status` إلى جانب `new_status` التوافقي.

### أدلة القبول

| الفحص | النتيجة |
|---|---|
| `php artisan test --testsuite=Unit --filter=DataTableContractTest` | **3 passed، 178 assertions**. |
| `npm run build` | ناجح؛ تحذيرات الأصول والخطوط الموروثة لا تمنع البناء. |
| `php artisan test --compact` | **120 passed، 2,757 assertions**. |

## قرار الإغلاق

1. جميع DataTables ذات مفتاح النشاط تستخدم handler مشتركاً، ويمنع اختبار العقد ترتيباً خاطئاً للحالة والإجراءات.
2. كل دوال `toggleStatus` و`toggleActive` في Controllers تعيد `success` و`message` آمنين و`status` أو `is_active`؛ الاختبار الساكن يغطيها كلها.
3. feedback الخاص بالـDataTables صار مركزياً: processing قابل للوصول، ورسائل empty/zero-results/error آمنة وموحدة.
4. مرّت اختبارات Admin وTenant، بما فيها DataTable AJAX، الحذف الجماعي، authorization والعزل، في suite التطبيق الكامل.

لا توجد فجوة تنفيذية ضمن نطاق المرحلة 7 تمنع الانتقال إلى المرحلة 8.

## عناصر غير متخذة كتنفيذ الآن

- حذف wrappers أو imports زائدة لمفاتيح الحالة بعد إثبات عدم الاستهلاك: المرحلة 8.
- تغيير بنية الإدارات أو نقلها إلى Golden Pattern: المرحلة 10 بعد إغلاق 7–9.
- تفعيل مدفوعات أو خدمات خارجية production: قرار إطلاق منفصل.
