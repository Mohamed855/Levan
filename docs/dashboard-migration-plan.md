# الخطة الشاملة لاسترجاع وإغلاق مشروع Levan

> **خطة الإقفال التنفيذية الحالية:** [خطة الإقفال الصارمة](superpowers/plans/2026-10-21-production-closure.md). تكمل فجوات التنفيذ القائم وتحافظ على مرجع الأعمال أدناه. الحالة والأدلة في [manifest](baselines/golden-pattern-manifest.md) و[سجل التنفيذ](closure/implementation-ledger.md).
>
> **النتيجة المطلوبة:** نسخة Production كاملة يمكن رفعها مباشرة بعد ضبط قيم البيئة السرية فقط. لا تقبل الخطة صفحة بلا backend، أو route بلا handler، أو عملية بلا صلاحية، أو واجهة بلا بيانات حقيقية، أو كود/ملف ميت بلا قرار حذف موثق.
>
> **المرجع المعماري الإلزامي:** [Golden Pattern القديم](../old/docs/architecture/golden-pattern.md). هذه الخطة لا تستبدله؛ بل تطبقه على البنية الجديدة المتفق عليها وتضيف قرارات النقل والإغلاق.
>
> **عقد الفهارس والنقل:** [عقد DataTable ونقل قواعد الأعمال](architecture/datatable-contract.md) إلزامي لكل صفحة فهرس. لا يعد جدول Blade paginated بديلًا مرحليًا لـDataTable، ولا يسمح تحسين الـstructure بتغيير workflow أو حالات أو صلاحيات الأعمال المرجعية.

## 1. قاعدة الاسترجاع

-   **الأصل هو المحافظة على الأعمال:** أي إدارة لم يصرح مالك المنتج بتغيير منطقها أو بياناتها تعاد بكامل behavior وroutes وpermissions وdata lifecycle من `old`، بعد إعادة ترتيب الواجهة والبنية فقط.
-   **التغيير البصري/الهيكلي لا يغير العقد:** نعيد بناء Blade وSCSS وJavaScript وطبقات التطبيق وفق البنية الجديدة، مع الحفاظ على أسماء routes ومفاتيح DB والصلاحيات والحالات وتدفقات الأعمال. أي تغيير كاسر يحتاج قرارًا مكتوبًا وخطة توافق أو ترحيل بيانات.
-   **الإدارة slice رأسي:** `Route → middleware/guard → Controller → FormRequest → Policy → Action/Service → Model/Enum/Event/Job → DataTable/Blade → JavaScript مشترك → Tests`.
-   لا تغلق إدارة إذا كانت أي طبقة مطلوبة مفقودة، إلا إذا سجلت `not_applicable` بسبب واضح في بطاقة الإدارة.
-   Admin يعمل على البيانات المركزية مع `auth:admin`، وTenant يعمل بعد تهيئة tenancy مع `auth:tenant`. لا يستدعي كود Tenant نموذجًا مركزيًا مباشرة؛ العبور يتم عبر Gateway/Service صريح يحافظ على tenant context.

## 2. البنية الجديدة المشتركة

```text
resources/views/layouts/app.blade.php
└── resources/views/layouts/dashboard.blade.php
    ├── resources/views/partials/dashboard/admin/{navbar,sidebar,footer}.blade.php
    ├── resources/views/partials/dashboard/tenant/{navbar,sidebar,footer}.blade.php
    └── resources/views/components/{shared,admin,tenant}/...

resources/views/admin/{module}/{index,create,edit,show,action,partials}.blade.php
resources/views/tenant/{module}/{index,create,edit,show,action,partials}.blade.php
resources/assets/{js,scss,css}/{shared,admin,tenant}/...
```

-   `layouts.dashboard` يضم head، المكتبات، body، slots والتحميل المشترك فقط. محتوى dashboard نفسه وnavbar/sidebar/footer يظل في ملفات منفصلة لكل نطاق.
-   لا تنقل أي asset أو Blade دون تحديث كل مستهلكاته: `view`, `@extends`, `@include`, component tag، Vite entry، mailables، menus، وerrors.
-   لا يحذف مصدر قديم أو ملف مرحلي إلا بعد `rg` يؤكد أن مراجعته صفر، ثم يمر الاختبار والبناء.

## 3. Golden Pattern الإلزامي لكل إدارة

### بطاقة الإدارة قبل الكود

لكل إدارة نفتح بطاقة تنفيذ تسجل: boundary، route prefix/names، guard/middleware، permissions، aggregate root والعلاقات، العمليات، الحالات، الملفات والديسك، side effects، نوع الواجهة، عقد المسودة، الاختبارات، وكل بند غير مطلوب مع سببه.

### Backend

-   تستخدم routes `resource` حيث ينطبق، وتضاف فقط العمليات غير القياسية مثل toggle/reorder/restore/callback. لا mutation عبر GET ولا business logic في route.
-   كل mutation له FormRequest typed يضم `authorize`, `rules`, `messages`, `attributes` عربية. لا تنسخ validation إلى JavaScript.
-   كل إدارة تملك Policy بمفاتيح صلاحيات إنجليزية؛ الأزرار والـDataTable والـRequest والـController يطبقون القدرة نفسها.
-   Controller نحيف؛ الكتابة وtransactions والعلاقات والملفات والتنظيف والـcache وafter-commit داخل Action أو Service. Model يحمل العلاقات وcasts/scopes البسيطة فقط.
-   الحالة التشغيلية `is_active` منفصلة عن workflow enum. قيم الحالات ومفاتيح DB والصلاحيات إنجليزية، والترجمة والعرض بالعربية.
-   migrations في boundary الصحيح، قابلة للعكس، بمفاتيح وفهارس وقيود uniqueness ملائمة. لا تعديل migrations منشورة؛ أي تعديل schema له migration جديدة.
-   لكل إدارة Factory وSeeder عند الحاجة، وبالأخص بيانات Tenant الاختبارية التي تغطي العلاقات والصلاحيات والحالات والملفات التجريبية.

### Index وDataTable

1. خمسة Stat Cards حقيقية في بداية الصفحة بلا banner فوقها: الإجمالي، النشط/الجاهز، المكتمل/قيد المراجعة، المعلق/غير المرتبط، والمتوقف/الذي يحتاج تدخلاً. إن لم تكن للمجال خمس حالات، تستبدل بمؤشرات حقيقية مفيدة.
2. الفلاتر بعدها مباشرة عبر `x-data-table.filters`، وخياراتها تأتي من Controller/Service. التاريخ `input type="date"` منفصل من/إلى، ولا يستخدم Select2.
3. DataTable server-side بعد الفلاتر. أول عمود check/check-all حيث توجد عمليات جماعية. الصورة دائرية موحدة، والأكواد قابلة للنسخ، وآخر عمود Actions فقط عندما توجد إجراءات.
4. زر الإضافة داخل DataTable toolbar ومشروط بالصلاحية. لا زر إضافة خارجي.
5. الشارات الافتراضية `bg-primary`. لون الحالة فقط من Enum/Presenter في الخلفية. كل `is_active` يملك `js-table-status-toggle` قبل عمود الإجراءات مع Request وPolicy.
6. queries scoped للـboundary، eager loading صحيح، HTML مسموح فقط في `rawColumns()` المتعمدة، وكل فلتر يعدل query ولا يحمل البيانات كاملة.

### Create / Edit والملفات والمسودات

-   create وedit يستدعيان `partials/form.blade.php` واحدًا، بالحقول والترتيب نفسيهما. غير القابل للتعديل يظهر في edit معطّلًا ومعبأً؛ Update Request يتحقق فقط مما يسمح المجال بتعديله.
-   كل control يستعمل مكونات المشروع المشتركة. الملفات والصور تستخدم uploader مستوحى من Tiryaq بهوية Levan؛ لا ينقل سلوك الرفع المتعدد القديم كما هو.
-   كل Create يدعم حفظ مسودة. عند فتحه يفحص المسودة المؤرشفة ويعرض Modal للاسترجاع أو البدء من جديد. الملفات تحفظ في مساحة drafts، تستعاد بدقة، وتحذف عند رفض الاسترجاع أو إكمال الإنشاء أو حذف المسودة.
-   النماذج أحادية الخطوة لا تتظاهر بأنها wizard. النماذج متعددة الخطوات تستخدم مرجع الإسنادات/العقارات، وتختم بمراجعة حقيقية من review-card components المشتركة. Edit لا يرث draft create تلقائيًا.
-   حفظ الملفات واستبدالها وحذفها explicit ومختبر؛ Blade لا يصنع مسارات storage بنفسه.

### Show / Archive / Actions

-   مرجع Show هو الإسنادات: بطاقات وأقسام موحدة، ومع زري «العودة للرئيسية» و«تعديل البيانات» في موضعهما المعتمد وفق الصلاحيات.
-   Soft delete يقتضي archive controller/DataTable/view، وسياسات restore/force-delete، وcleanup للملفات والعلاقات. لا تسجل إدارة بلا هذه الدورة إن كانت تستخدم الأرشفة.
-   الحذف الجماعي يمر عبر Request وPolicy ويعيد تحقق كل سجل مختار.

## 4. القرار المعلن لتغييرات الأعمال

### أ. Admin: مصمم قوالب المزادات

هذه هي الإدارة الوحيدة التي يتغير منطقها من رفع PDF/صورة إلى **مصمم قوالب مرئي**. صاحب صلاحية إدارة القوالب يرسم القالب داخل النظام؛ لا يرفع قالبًا جاهزًا.

-   القالب يحمل: الاسم، النوع، الحالة، النسخة، والمواصفات التقنية.
-   يدعم canvas لكل صفحة: المقاس والاتجاه، خلفية مصمتة أو صورة، وعناصر موضوعة بإحداثياتها وأبعادها وترتيبها.
-   العنصر يكون نصًا ثابتًا أو متغيرًا من عقد المزاد، أو خانة صورة، مع نوع الخط وحجمه ووزنه ولونه ومحاذاته والتفافه وأي style معتمد.
-   الـBrochure يدعم صفحات متعددة. الأنواع الأخرى تتبع عدد صفحات يقرره تعريف النوع، ولا يفترض النظام نموذجًا واحدًا للجميع.
-   القالب يمر بحالة draft ثم publish/active. لا يستخدم Tenant إلا نسخة منشورة ونشطة.
-   كل تعديل منشور ينشئ version جديدة. المزاد يحتفظ بـtemplate version snapshot كي لا يتغير مخرج مزاد قديم عند تعديل القالب لاحقًا.
-   فحوصات الإدارة تشمل: منع العناصر خارج الـcanvas، منع key غير معتمد، منع تكرار slot فريد، التحقق من الخلفية والخطوط والملفات، preview حقيقي، وpermission منفصل للرسم والنشر والتفعيل.

### ب. Tenant: المزادات ومخرجاتها

لا يتغير business الأساسي للمزاد، لكنه يصبح مرتبطًا بعقد القوالب الجديد.

-   يحفظ المزاد بياناته الأساسية كما هي، ويختار فقط قالبًا منشورًا متوافقًا مع نوع المخرج.
-   توجد **قاموس بيانات مزاد موحد** هو المصدر الوحيد للـkeys المتاحة للقوالب: بيانات المزاد والإسناد والعقارات والجهات والتواريخ والنصوص والصور. لا يسمح القالب بطلب key غير موجود فيه.
-   لكل image slot عقد واضح: الاسم، هل هو مطلوب، عدد الصور، الأبعاد/النسبة المقبولة، والـfallback إن وجد. FormRequest في الخلفية يتحقق من توافق الصور والبيانات قبل الحفظ، والواجهة تعرض الأخطاء بجانب الموضع المرتبط.
-   قبل النشر أو التصدير، Renderer يبني preview من template version snapshot + بيانات المزاد، ويتحقق أن كل key/صورة مطلوبة متاحة. لا يعتمد على JavaScript وحده.
-   Tenant يرى القوالب المنشورة المصرح بها فقط ولا يحرر تصميمها. Admin/صاحب صلاحية القالب يتحكم بالتصميم والنشر.
-   تنفذ اختبارات توافق ثنائية إلزامية: إنشاء/نشر قالب متعدد الصفحات، اختيار template مناسب لمزاد، نجاح preview عند اكتمال البيانات، رفضه عند key أو صورة ناقصة، وثبات مخرج المزاد بعد إصدار نسخة جديدة من القالب.

### عقد التوافق المشترك

ينشأ module/domain مشترك صغير يعرّف schema للـtemplate والـkeys والـimage slots وrenderer input. لا تكرر Admin وTenant تعريفات مستقلة. أي تغيير في schema أو key أو renderer يحدّث العقد والاختبارات في النطاقين داخل نفس التغيير.

## 5. ترتيب التنفيذ

### المرحلة 0: الحراسة والتوثيق

1. إنشاء manifest لكل إدارة من [جرد الإدارات](dashboard-legacy-inventory.md)، ووسمها: `restore` أو `business-change` أو `not_applicable`.
2. إضافة contract tests تحمي: المسارات الفعالة، FormRequests، Controllers الرشيقة، صلاحيات إنجليزية، DataTables، المسودات، boundaries، ومراجع resources.
3. إعداد tenant test seeder واحد يعيد سيناريو اختبار قابلًا للتكرار: tenant، مستخدمو بأدوار متفاوتة، صلاحيات، باقة/اشتراك، إسناد، عقارات، مزاد، ملفات، حالات، أرشيف وإشعارات.

### المرحلة 1: أساس الـDashboard المشترك

1. `layouts.dashboard` وpartials المنفصلة للـAdmin/Tenant.
2. مكونات Stat Cards وDataTable filters/status toggle/actions/mass delete/copyable code.
3. shared form controls، uploader، draft lifecycle، review cards، وإشعارات الواجهة.
4. نقل assets وفق boundaries وتشغيل build واختبارات views/references.

### المرحلة 2: العقد المشترك للقوالب والمزادات

1. تصميم schema وmigration وFactories/Seeders للقوالب والنسخ والصفحات والعناصر والـassets.
2. بناء Admin template designer وpreview/publish/versioning والـPolicies والاختبارات.
3. بناء tenant template gateway/selection/validation/renderer output على العقد نفسه.
4. تثبيت اختبارات compatibility وsnapshot rendering قبل التوسع في إدارات أخرى.

### المرحلة 3: استرجاع إدارات Tenant بلا تغيير أعمال

بالترتيب: المستخدمون والأدوار والصلاحيات، الإسنادات، العقارات، الشركات، الفواتير، التذاكر، الإشعارات، الإعدادات/رمز API، سجلات النشاط، الأرشيفات، ثم الدومينات والتراخيص والاشتراكات وفق تكاملاتها الموجودة.

### المرحلة 4: استرجاع إدارات Admin بلا تغيير أعمال

بالترتيب: الباقات، العملاء، الاشتراكات ومتابعتها، الكوبونات، مستخدمو الإدارة والأدوار، الفواتير، التذاكر، الطلبات العامة، البنوك ودومينات التحكم، الإعدادات، سجلات النشاط، والأرشيفات.

### المرحلة 5: استكمال دورة المنصة والموقع

1. استكمال دورة إنشاء Tenant: العميل/الشركة، المستخدم الإداري، تهيئة قاعدة Tenant، migrations، seed/reference data، الاشتراك والرخص والصلاحيات، وحالات الفشل/rollback والإشعارات.
2. استكمال ربط بوابة الدفع: checkout، coupon، الفاتورة، الدفع الإلكتروني والتحويل البنكي، callback signature، idempotency، إعادة المحاولة، reconciliation، وعدم تكرار الفاتورة/الاشتراك.
3. ربط صفحات الموقع العام كلها ببيانات حقيقية من قاعدة البيانات: الباقات، الطلبات، checkout، المحتوى والإعدادات، مع fallback واضح للبيانات المفقودة.
4. نقل وتجهيز mailables وnotifications وjobs والقوالب. تبقى جاهزة وتختبر بسواقات محلية/وهمية؛ مفاتيح SMTP والبث ومزودات SMS والدفع والدومينات تضاف في `.env` عند النشر فقط، ولا تقرأ مباشرة من Controller.

## 6. بوابة الإغلاق Production

لا يعلن المشروع جاهزًا حتى يمر بهذه البوابة بالكامل:

### اكتمال الوظائف

-   كل route نشط يحل إلى Controller/handler موجود، وكل mutation يحوي typed FormRequest وPolicy وAction/Service عند الكتابة.
-   لا توجد صفحة بلا مصدر بيانات، ولا endpoint بلا واجهة/مستهلك مقصود، ولا sidebar/menu link مكسور، ولا permission غير seeded أو غير مترجم.
-   كل إدارة مصنفة `compliant` في manifest أو `not_applicable` بسبب موثق؛ لا تبقى `gap` أو `partial` بلا owner وخطة.
-   فحص references يؤكد غياب الملفات/المسارات/الـclasses dead بعد حذفها، مع قرار صريح لأي compatibility alias مؤقت.

### سلامة البيانات والحدود

-   migrations fresh للمركزي ولكل Tenant، ثم tenant test seeder، ثم اختبارات إنشاء Tenant وعزل Tenant A عن Tenant B.
-   قيود uniqueness والفهارس وsoft deletes والـrestore/force-delete والملفات والمسودات tested.
-   لا crossing مباشر بين Tenant وCentral، ولا سر في الكود أو في الواجهة؛ الإعدادات السرية من `config/services.php` و`.env` فقط.

### التكامل والأمان

-   الصلاحيات تطبق في navigation، الصفحة، DataTable actions، route، Request، Policy، job والـcallback حسب الحاجة.
-   بوابة الدفع تختبر النجاح والفشل والإلغاء والـcallback المكرر والتوقيع غير الصالح.
-   البريد والإشعارات والـqueue/scheduler جاهزة ومختبرة بالسواقات غير الإنتاجية، وتتحول للإنتاج بمجرد إدخال keys البيئة.
-   كل ملفات الرفع محكومة بالنوع والحجم والملكية والتنظيف، ولا تظهر ملفات Tenant لآخر.

### التحقق النهائي

1. اختبارات Feature/Unit/Policy/Isolation/Action/Files/Drafts/Template compatibility كاملة.
2. اختبار مسارات Admin وTenant وWebsite يدويًا من سيناريو حقيقي: تسجيل الدخول، الصلاحيات، CRUD، الأرشيف، الدفع، إنشاء Tenant، إنشاء مزاد ومخرجاته.
3. `php artisan test`، و`npm run build`، و`php artisan view:cache`، وفحص routes/config/cache بحسب بيئة النشر.
4. فحص بصري desktop/mobile للـwebsite والـdashboards، وفحص DataTable والفلاتر والـRTL ورفع الملفات.
5. فحص dead code/references، ومراجعة migrations/seeders، وتجربة build وتشغيل من نسخة نظيفة ببيانات اختبار.
6. تسليم runbook نشر يحوي متغيرات البيئة المطلوبة دون قيم سرية، وأمر migrations/queue/scheduler/cache، وخطة rollback.

## 7. تعريف الإنجاز

لا يكفي أن تعمل الصفحة يدويًا. تعتبر الإدارة منتهية فقط حين يثبت manifest أن contracts الخلفية والواجهة والصلاحيات والملفات والمسودات والاختبارات اكتملت، ثم تمر بوابة الإغلاق العامة. ويعتبر المشروع منتهياً فقط بعد اجتياز بوابة Production كاملة، وتسليم نسخة قابلة للنشر بلا أجزاء غير مربوطة أو غير مستخدمة.
