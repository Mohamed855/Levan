# خطة تنفيذ فعلية لتوحيد مكوّنات Blade

## النطاق والقرار المعماري

الهدف هو تقليل تكرار واجهات الـCRUD في لوحتي الإدارة والمستأجرين، مع الحفاظ على السلوك الحالي ومسارات الـDataTables وJavaScript الخاصة بكل ميزة.

لن نعيد بناء الصفحات التجارية المعقّدة (مثل شاشة العميل أو دورة التحصيل) إلى مكوّن واحد ضخم. سنستخرج منها عناصر العرض المتكررة فقط. ولا تشمل هذه الخطة تغيير قاعدة البيانات أو تغيير تجربة المستخدم.

يعتمد المشروع Bootstrap وBlade وSelect2؛ لذلك ستكون المكوّنات الجديدة Bootstrap Blade Components. لا يُعاد استخدام `resources/views/components/modal.blade.php` لأنه مكوّن Tailwind/Alpine حاليًا وغير مستخدم في واجهات Bootstrap.

## قواعد واجهة ثابتة قبل البدء

- أسماء المكوّنات العامة تكون تحت `x-ui.*` أو `x-form.*` أو `x-data-table.*` أو `x-actions.*`، ولا ترتبط بمنطقة `admin` أو `tenants`.
- المكوّنات تستقبل النص المعروض من الصفحة أو من ملفات الترجمة، ولا تخزّن عبارات عربية كمفاتيح أو صلاحيات.
- أي prop للصلاحية يجب أن يكون مفتاحًا إنجليزيًا بالنمط `entity.action` مثل `companies.edit` و`companies.delete`.
- تظل إعدادات الأعمدة، AJAX، routes، وتهيئة DataTable الخاصة بكل feature في الصفحة أو ملف JavaScript الخاص به؛ المكوّن يوحّد الهيكل البصري فقط.
- لا تستخدم أسماء أو `id` عامة متصادمة. كل جدول يمرر `tableId` فريدًا، وكل modal يمرر `id` فريدًا.

## المرحلة 0 — تثبيت خط أساس وحماية الصلاحيات

### العمل

1. إنشاء قائمة حصر من كل `@can(...)` و`$user->can(...)` وmiddleware الخاص بالصلاحيات في `resources/views` و`app`.
2. مطابقة كل صلاحية عربية موجودة مع الصلاحية الإنجليزية المعتمدة في seeders و`lang/ar/permissions.php` و`lang/en/permissions.php`.
3. تصحيح الاستدعاءات العربية أولًا، مع إبقاء العربية في الترجمة فقط. أمثلة متأثرة: `companies/action.blade.php` و`contracts/action.blade.php` وملفات الأرشيف.
4. إضافة اختبار Feature يغطي ظهور/إخفاء عمليات edit/delete لمفتاح إنجليزي واحد على الأقل في admin وtenant.

### مخرج القبول

- لا تعود أي صلاحية عربية فعّالة داخل `@can` أو authorization checks.
- لا يتغير اسم أو معنى أي صلاحية في قاعدة البيانات من دون migration/seed migration واضحة ومعتمدة.
- تستمر ترجمة اسم الصلاحية من ملفات `lang/*/permissions.php`.

## المرحلة 1 — DataTable shell وFilters (أعلى عائد وأقل مخاطرة)

### ملفات جديدة

- `resources/views/components/data-table/page.blade.php`
- `resources/views/components/data-table/filters.blade.php`
- `resources/views/components/ui/validation-errors.blade.php`

### عقد المكوّنات

`x-data-table.filters`:

- slot لحقول الفلاتر الحالية.
- props: `resetId` (افتراضيًا `btn-reset-filters`)، `resetLabel`، `showReset`، و`cardClass`.
- يرسم فقط Card الفلاتر، `row g-4`، وزر reset؛ لا يربط events ولا يغيّر Select2.

`x-data-table.page`:

- props: `tableId`، `tableClass`، `responsive`، و`cardId`.
- named slots: `stats` و`filters` و`table` و`beforeTable`.
- يضيف `table-responsive` عند طلبه، ويحتوي جدول الـDataTable داخل Card موحّد.
- لا يستدعي `$dataTable->scripts()`؛ تبقى داخل `@section('vendor-script')` لكي لا يتغير ترتيب تحميل المكتبات.

`x-ui.validation-errors`:

- يعرض `$errors` فقط إن وُجدت أخطاء، بنفس markup الحالي.

### ترتيب الترحيل

1. ابدأ بثلاث صفحات ممثلة: `admin/versions/index.blade.php`، `admin/companies/index.blade.php`، و`tenants/business_center/companies/index.blade.php`.
2. راجع DOM، reset، Select2، ترتيب تحميل `$dataTable->scripts()`، وفرز/بحث/AJAX بعد كل صفحة.
3. رحّل صفحات الـDataTable البسيطة ذات filters الثابتة أولًا، ثم صفحات الفلاتر الديناميكية، وأخيرًا الشاشات التي تملك modals أو إجراءات bulk delete.
4. لا تحرّك imports الخاصة بـVite في هذه المرحلة. يوجد تكرار في assets لكنه لا يبرر خطر تغيير ترتيب السكربتات قبل توحيد الواجهات.

### مخرج القبول

- صفحة الجدول قبل وبعد الترحيل لها `id` نفسه، والأعمدة نفسها، وطلبات AJAX نفسها.
- زر reset يعيد كل `filter-*` دون تغيير سلوكه.
- لا تغيّر ملفات JavaScript الخاصة بالصفحة إلا إن ظهر اعتماد صريح على selector تم تغييره.

## المرحلة 2 — Actions والحذف الآمن

### ملفات جديدة

- `resources/views/components/actions/row.blade.php`
- `resources/views/components/actions/edit.blade.php`
- `resources/views/components/actions/delete.blade.php`

### ملفات تعدّل عند الحاجة

- `resources/js/shared/confirm-delete.js`
- ملفات `action.blade.php` للمزايا التي تُرحّل فقط.

### العقد

- `x-actions.row` هو container ويستقبل slot للأفعال غير القياسية (approve/reject/view).
- `x-actions.edit` يستقبل `url`، `title`، `permission` اختياريًا، و`icon`.
- `x-actions.delete` يستقبل `id`، `url`، `title`، `permission` اختياريًا، ويولد hidden DELETE form بمعرف ثابت `delete-form-{id}` للحفاظ على توافق `confirmDelete` الحالي.
- كل action مخصص يبقى slot داخل `x-actions.row` بدل إضافة props كثيرة أو شرطيات خاصة بموديل معين.

### ترتيب الترحيل

1. اختبر المكوّن على `admin/versions/action.blade.php` و`tenants/users_management/users/action.blade.php`.
2. رحّل actions ذات edit/delete فقط.
3. رحّل القوائم المنسدلة للإجراءات بعد ذلك، مع إبقاء approve/reject خارج المكوّن العام في البداية.
4. بعد نجاح الترحيل، وحّد listener الحذف في `confirm-delete.js` وأزل listeners المحلية المتطابقة صفحةً بصفحة.

### مخرج القبول

- لا يظهر action بدون صلاحية صاحبه، وتكون الصلاحية إنجليزية.
- delete confirmation يعمل للمفرد والجماعي دون duplicate submission.
- رابط edit، method DELETE، وCSRF token لا تتغير.

## المرحلة 3 — Bootstrap Modal موحد

### ملفات جديدة

- `resources/views/components/bootstrap/modal.blade.php`

### الملفات التي ستُزال لاحقًا فقط بعد اكتمال الترحيل

- `resources/js/shared/modal-close-controls.js`

### عقد المكوّن

- props: `id`، `title`، `size` (`sm|lg|xl|fullscreen`)، `scrollable`، `staticBackdrop`، و`labelledBy`.
- slots: المحتوى الأساسي و`footer`.
- يضيف `aria-labelledby` و`aria-hidden` وزر إغلاق Bootstrap وfooter اختياريًا بصورة صحيحة.
- لا يفرض طريقة فتح؛ يظل `data-bs-toggle` أو JavaScript الحالي مسؤولًا عن الفتح.

### ترتيب الترحيل

1. ابدأ بـmodal بسيط مع footer ثابت، ثم modal نموذج، ثم modal محمّل عبر AJAX.
2. بعد أن تغطي المكوّن كل الأنماط اللازمة في ثلاث صفحات، رحّل بقية الـ48 modal تدريجيًا.
3. أزل `modal-close-controls.js` فقط عندما لا يبقى modal يعتمد على إضافته المتأخرة لزر الإغلاق.

### مخرج القبول

- الفتح والإغلاق بـmouse وEscape يعملان، والـfocus لا يضيع.
- كل modal يملك title/label يمكن لقارئات الشاشة قراءته.
- لا يتكرر footer أو زر الإغلاق.

## المرحلة 4 — حقول النماذج والحالة والرفع

### ملفات جديدة

- `resources/views/components/form/field.blade.php`
- `resources/views/components/form/select.blade.php`
- `resources/views/components/form/password.blade.php`
- `resources/views/components/form/image-upload.blade.php`
- `resources/views/components/ui/status-badge.blade.php`
- `resources/views/components/ui/empty-state.blade.php`

### قرارات تصميم تمنع over-abstraction

- `x-form.field` يغطّي text/email/number/date/textarea فقط. الحقول المركّبة أو business-specific تظل markup محليًا.
- `x-form.select` ينسّق Select2 وold value وerror فقط؛ مصدر options يأتي من الصفحة.
- `x-form.password` يوحّد toggle visibility والتحقق، ولا يغيّر قواعد Fortify أو validation.
- `x-form.image-upload` يوفّر preview وaccept/max hint؛ الرفع والتخزين والتحقق في الـRequest/Controller لا تتغير.
- `x-ui.status-badge` يستقبل `tone` أو status enum/DTO جاهزًا؛ لا يضم business mapping كبيرًا في Blade.

### ترتيب الترحيل

1. ابدأ بنموذجي create/edit للمستخدمين، ثم create/edit الشركات، ثم الإعدادات.
2. استخدم `x-ui.status-badge` للحالات القياسية فقط (`active`, `inactive`, `pending`, `suspended`) ثم أضف الحالات الجديدة من enum مركزي عند ظهورها.
3. لا تستبدل المكوّنات القديمة Tailwind (`x-primary-button`, `x-text-input`...) تدريجيًا داخل Bootstrap؛ إمّا تُهجر بعد التأكد من عدم استخدامها، أو تظل لمسارات auth المنفصلة.

### مخرج القبول

- old input وvalidation errors وrequired state ظاهرة كما كانت.
- الملف المرفوع يعرض preview صحيحًا وتبقى قيود `accept` والحدود الحالية.
- لا تتغير أسماء inputs أو payload المرسل إلى الخادم.

## المرحلة 5 — إكمال تبني الموجود وتوحيد layouts بحذر

### العمل

1. استبدل بطاقات الإحصاءات المكتوبة يدويًا بالمكوّن الموجود بعد نقله/تسميته العام `x-ui.stat-card`، مع توفير alias مؤقت لـ`x-admin.dashboard-card` حتى اكتمال الترحيل.
2. استخرج فقط السطور المتطابقة من `admin/layouts` و`tenants/layouts` إلى partial عام parameterized بالسياق. لا تدمج navigation أو menu أو tenancy logic في هذه الخطوة.
3. قارن HTML الناتج ومصفوفة Vite قبل حذف أي layout قديم.

### مخرج القبول

- تبقى مسارات admin وtenant، navbar/menu، والـsubscription alert مستقلة.
- لا يحدث تحميل مكرر أو مفقود لمكتبات Vite.

## استراتيجية الاختبار والتحقق في كل مرحلة

1. تشغيل `php artisan test` للـFeature tests المتأثرة، خصوصًا CRUD وauthorization وtenant isolation.
2. تشغيل `php artisan view:cache` للتحقق من Blade compilation، ثم `php artisan view:clear` بعده كي لا تترك caches مولّدة في working tree.
3. عند أي تعديل في `.js` أو `.scss` أو `.css`: تشغيل `npm run build` إلزاميًا حسب `AGENTS.md`.
4. فحص يدوي على desktop وmobile: جدول، Select2، reset، modal، edit/delete، ورسالة validation.
5. بعد كل batch: `git diff --check` و`rg` للتأكد من عدم إدخال `@can` عربي فعّال أو JavaScript inline متطابق تم ترحيله.

## ترتيب التسليم المقترح

| PR | المحتوى | نقطة التوقف الآمنة |
|---|---|---|
| 1 | تدقيق/تصحيح مفاتيح الصلاحيات + tests | authorization صحيح ومفاتيح DB إنجليزية |
| 2 | validation errors + DataTable filters/page + 3 صفحات تجريبية | لا تغير في requests أو DataTable behavior |
| 3 | توسيع DataTable shell لبقية الصفحات | كل صفحة ترحل دون نقل assets |
| 4 | actions/edit/delete + إزالة listeners المكررة | confirmation وpermissions متطابقان |
| 5 | Bootstrap modal + إيقاف patcher | accessibility وإغلاق موحدان |
| 6 | form/status/upload + stat cards/layout partials | واجهة CRUD متسقة وقابلة للتوسع |

## تعريف الاكتمال

- كل component له عقد props موثق ومثال استخدام واحد على الأقل.
- لا توجد أسماء صلاحيات عربية فعّالة في Blade/PHP.
- لا يوجد سلوك حذف أو filter أو modal أو validation تراجع عن وضعه الحالي.
- كل تعديل JavaScript/CSS مرّ بـ`npm run build`، وكل Blade جديد مرّ بـ`php artisan view:cache`.
- تم ترحيل العينة التجريبية أولًا ثم توسعة الترحيل على دفعات قابلة للمراجعة، بلا refactor جانبي لصفحات الأعمال المعقدة.
