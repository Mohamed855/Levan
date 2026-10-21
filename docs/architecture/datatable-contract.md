# عقد DataTable ونقل قواعد الأعمال

## الغرض

النقل إلى Levan الجديد هو نقل **سلوك الأعمال وعقد البيانات** من `old/` إلى البنية الحالية، وليس نسخ ملفات أو واجهات. تعاد صياغة الطبقات والواجهة وفق Golden Pattern، لكن لا يتغير workflow أو الصلاحية أو الحالة أو lifecycle أو tenant boundary إلا بقرار أعمال مكتوب.

## المرجع وترتيب الأولوية

1. [جرد الإدارات القديمة](../dashboard-legacy-inventory.md) هو مرجع الوظائف والحقول والفلاتر والإجراءات الموجودة.
2. [خطة النقل](../dashboard-migration-plan.md) و`AGENTS.md` هما مرجع البنية، الحدود، والواجهة الجديدة.
3. عند التعارض، يحافظ المنفذ على الأعمال أولاً ثم يوثق أي تغيير مقصود في خطة النقل قبل كتابة الكود.

## عقد صفحات الفهرس

كل إدارة قائمة على سجلات متعددة تستخدم DataTable server-side عبر `yajra/laravel-datatables-oracle` وحزمة DataTables الموجودة في الواجهة. لا تستخدم Blade pagination كبديل مرحلي ولا تحمل مجموعة السجلات كاملة إلى المتصفح.

- تبدأ الصفحة بخمس بطاقات إحصائية حقيقية مناسبة لدورة حياة المجال، بلا banner أو عنوان أو زر إضافة فوقها.
- تليها filters مشتقة من business contract في الجرد؛ لا تضف فلترًا لا تدعمه query ولا تحذف فلترًا قائمًا بلا قرار أعمال.
- يقدّم endpoint مستقل JSON من query محددة الأعمدة، scoped بالـboundary، مع eager loading للعلاقات المعروضة فقط.
- العمود الأول checkbox عند وجود عمليات جماعية؛ زر الإضافة والإجراءات والتصدير داخل DataTable toolbar فقط وبحسب الصلاحيات.
- عمود `is_active` قبل الإجراءات ويستعمل `js-table-status-toggle`، لا badge أو زر نصي.
- HTML الصادر من DataTable يمر عبر escaping و`rawColumns()` المتعمدة فقط؛ actions والحالة مشروطة بالقدرة نفسها في Policy/Request/Controller.

## تعريف الاكتمال

لا تعد صفحة Index منقولة أو إدارة مغلقة إذا كانت تستعمل `paginate()` أو جدول Blade عاديًا بدل DataTable، ما لم توثق بطاقة الإدارة أنها read-only أو not_applicable ولا تحتاج جدولًا تشغيليًا. الإصلاح يشمل route، authorization، endpoint، DataTable query، stat cards، filters، toolbar، scripts، والفحص المرحلي.

## سلامة الأعمال والحدود

- Admin يستعلم ويكتب في البيانات المركزية فقط تحت `auth:admin`.
- Tenant يستعلم ويكتب في قاعدة tenant المهيأة فقط تحت `auth:tenant`.
- لا يغير DataTable أو filter معنى الحالة أو الانتقالات؛ هذه تبقى داخل Action/Service.
- لا يفترض المنفذ أسماء أعمدة أو transitions؛ يراجع migration/model ومرجع `old/` قبل كل slice.
