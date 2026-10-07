# سجل تنفيذ المرحلة 7 — DataTables والاستجابات

**تاريخ البدء:** 7 أكتوبر 2026
**الحالة:** قيد التنفيذ — الدفعة الأولى مغلقة ومتحققة.
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
- بعض الاستجابات القديمة خارج مفاتيح الحالة ما زالت تتضمن نص `$exception->getMessage()`؛ تعامل كدفعات منفصلة بحسب consumer حتى لا ينكسر import/export أو polling أو payment flow.

## الدفعة الأولى — العقد الأفقي لمفاتيح الحالة

### ما أُنجز

1. تحميل `resources/assets/js/shared/table-status-toggle.js` من shared layout، بدلاً من الاعتماد على أن تتذكر كل صفحة استدعاء initializer خاص بها.
2. جعل الملف المشترك يهيئ handler نفسه عند التحميل. الحارس `data-table-status-toggles-initialized` يمنع تكرار listener في الصفحات التي تستدعي `initializeTableStatusToggles()` بالفعل.
3. تصحيح `AdminRoleDataTable`: أصبح `آخر دخول` قبل `الحالة`، وأعيد تعريف عمود `الإجراءات` فعلياً بعد الحالة بدلاً من تركه معطلاً بالتعليق بينما renderer ما زال ينتجه.
4. إضافة `DataTableContractTest` الذي يفحص كل DataTable يحتوي `js-table-status-toggle` ويمنع دمج أي ترتيب لا يضع `status` أو `is_active` مباشرة قبل `action/actions`.
5. توحيد الحد الأدنى الآمن لكل `toggleStatus` و`toggleActive`: توجد دائماً `success` و`message`، ولا تظهر `getMessage()` ضمن رسالة response. حُفظت الحقول القديمة للتوافق، وأضيف `status` لمسار حالة مستخدم الإدارة الذي كان يرسل `new_status` فقط.
6. أصبح المفتاح المشترك يعيد مزامنة نفسه مع القيمة النهائية التي يرجعها الخادم عبر `is_active` أو `status` أو الحقل التوافقي `new_status`، فلا تبقى واجهة خاطئة عند تغير الحالة في الخادم.

### أدلة القبول

| الفحص | النتيجة |
|---|---|
| `php artisan test --testsuite=Unit --filter=DataTableContractTest` | **3 passed، 152 assertions**. |
| `npm run build` | ناجح؛ تحذيرات الأصول والخطوط الموروثة لا تمنع البناء. |

## الدفعات التالية

1. **استجابات Toggle:** مطابقة جميع مسارات `toggle-status` و`toggle-active` النشطة مع العقد أعلاه، بما في ذلك 403 و422 و500 دون كشف exception.
2. **AJAX consumers:** جرد كل consumer في Admin وTenant إلى mutation، validation، async import/export، API resource، أو external callback؛ ثم توحيد payload لكل فئة مع اختبارها.
3. **DataTable pages:** التحقق من delegation للـselection، empty/error/loading، mass-delete، وtenant scope لكل الجدول النشط؛ والـarchive يبقى وفق عقد الأرشيف في `AGENTS.md`.
4. **بوابة الإغلاق:** feature tests للـauthorized/unauthorized toggle وDataTable JSON، build كامل، suite التطبيق، وتقرير نهائي لا يخلط هذه المرحلة مع cleanup أو smoke production.

## عناصر غير متخذة كتنفيذ الآن

- حذف wrappers أو imports زائدة لمفاتيح الحالة بعد إثبات عدم الاستهلاك: المرحلة 8.
- تغيير بنية الإدارات أو نقلها إلى Golden Pattern: المرحلة 10 بعد إغلاق 7–9.
- تفعيل مدفوعات أو خدمات خارجية production: قرار إطلاق منفصل.
