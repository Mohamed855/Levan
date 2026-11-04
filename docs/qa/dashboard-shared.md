# مراجعة الإدارات المشتركة — SuperAdmin وTenant

**الحالة: prepared.** إعداد Codex: 2026-11-04، Africa/Cairo. مرجع الحصر: `a86bbe93c32b99d9537183647861db18cc97de3b`، وكانت مساحة العمل نظيفة عند البداية. هذه ملفات إعداد واختبار، وليست نتائج نجاح أو إصلاحات.

## قرار العمل المعتمد

- **طلب المستخدم الحالي هو المصدر:** أُوقفت مراجعة رابط الـtester وبلاغاته، بما فيها تقرير الصورة السابق، حتى انتهاء مراجعتنا الخاصة. تحفظ الملفات السابقة كتاريخ دون استيراد حالاتها أو أولوياتها إلى هذه الدفعات.
- المشكلات العشر التي أبلغ بها المستخدم أدناه لها **أولوية تنفيذ قصوى P0**، بما فيها مشاكل العرض؛ هذا ترتيب تنفيذ طلبه المستخدم ولا يعني أن كل مشكلة ثغرة أمنية.
- كل إدارة تُراجع بكل صفحاتها ومساراتها الفعلية في النطاقين، Backend وFrontend والمكونات المشتركة. اختلاف المزايا بين اللوحتين لا يعالج باختراع صفحات غير موجودة.
- Codex يعد الحالات ويراجع النتائج؛ Gemini ينفذ ويصلح؛ المستخدم يسلم الملفات بينهما. لا تفويض آلي ولا إغلاق بمجرد تقرير المنفذ.
- الإدارات الخاصة بكل لوحة مؤجلة. الأرشيف يشمل واجهات المحذوفات الحالية فقط، دون توسيع العمل إلى CRUD الإدارات الخاصة الأصلية.
- تظل مراجعة إصلاحات 2FA الجارية في `site-auth.md` مستقلة. طلب أيقونة نسخ كل recovery code إضافة جديدة مسجلة هنا؛ لا يضاف ادعاء إنجازها بأثر رجعي إلى الجولة السابقة.

## الملفات والترتيب

| الترتيب | الملف | النطاق | بوابة الإغلاق |
| --- | --- | --- | --- |
| 0 | هذا الملف — المشترك وبلاغات المستخدم | إصلاح العقود المشتركة المتأثرة داخل الدفعة المختارة أولًا | دليل نجاح كل بلاغ P0 وآثاره، دون إصلاحات عشوائية في كل المشروع |
| 1 | [الأدوار والصلاحيات](dashboard-roles-permissions.md) — QA-003 | كل صفحات الأدوار وتعيينها في اللوحتين | يمنع الدور الفارغ وفقد صلاحية overview وحذف الدور المرتبط |
| 2 | [المستخدمون وprofiles](dashboard-users-profiles.md) — QA-002 | CRUD المستخدمين وملفي الشخص الحالي والغير | الأدوار والدخول والحذف والملفات والمسودات تعمل |
| 3 | [overview وكلمة المرور و2FA](dashboard-overview-security.md) — QA-004 | صفحتا overview، تغيير كلمة المرور، إعدادات 2FA | توزيع عقارات مفهوم، ونسخ مستقل لكل كود، وأمن الحساب |
| 4 | [سجل النشاطات](dashboard-activity-logs.md) — QA-006 | index/show وعمليات Tenant الإضافية | شكل show الموحد والعزل والعرض والتصدير |
| 5 | [المحذوفات](dashboard-archives.md) — QA-005 | كل واجهات المحذوفات المتاحة بالنطاقين | layout والاستعادة والارتباطات وحماية الحذف النهائي |
| 6 | [الدعم الفني](dashboard-support.md) — QA-007 | صفحات التذاكر وعمليات كل لوحة | دورة تذكرة كاملة وعزل ومرفقات وفشل التكامل |

قُدمت الأدوار لأنها تمنع إغلاق المستخدمين والدخول للـoverview، ثم الأجزاء الأقل اعتمادًا على التكاملات. لا يبدأ Gemini الملفات الستة معًا. أول تسليم بعد إنهاء التعديلات الجارية: **QA-003 + الحالات المشتركة المنطبقة عليه**. بوسع المستخدم تغيير ترتيب الدفعات دون تغيير أولوية بلاغاته.

## مصفوفة التنفيذ والبيانات

لكل حالة ينطبق نطاقها، سجل نتائج مستقلة: `ADMIN`، `TENANT-A/PATH`، `TENANT-A/DOMAIN`، ثم `TENANT-B` لاختبار العزل. لا يعني نجاح ADMIN نجاح Tenant. إذا كان domain غير متاح في المتصفح، اختبر HTTP kernel بـHost اصطناعي وسجل مانع المتصفح منفصلًا؛ لا يحتاج Feature request إلى DNS خارجي.

استخدم قاعدة Central تجريبية وقاعدتي Tenant منفصلتين مع إثبات اختلاف الاتصالات/البيانات وتطابق IDs المطلوبة. أدوار: مدير اختبار كامل الصلاحيات، قارئ، محرر دون حذف، مستخدم عادي يملك overview فقط، وحساب معطل ومحذوف. البيانات اصطناعية والملفات تجريبية. لا أسرار في الأدلة. راجع `phpunit.xml` وsetup/teardown قبل أي suite؛ بعض الاختبارات الموجودة تكتب في بيانات مستأجر قائم. لا تشغلها عميانيًا.

الحد الأدنى للمتصفح: 1440×900، 1024×768، 768×1024، 390×844، 360×800، 320×740. أعد الحالات المشتركة بعد البحث والفلترة وredraw وفتح dropdown/modal، وفي الحساب محدود الصلاحيات. وجود كلمات في Blade أو لقطة HTML محفوظ لا يثبت POST أو clipboard أو toast أو confirmation.

## بلاغات المستخدم — كلها P0 وتنفيذها not_run

| ID | البلاغ ومعيار القبول | نقطة التحقيق الفعلية | الحالات المالكة |
| --- | --- | --- | --- |
| U-01 | Chart توزيع العقارات: العقار له شريحة واحدة فقط، لا يدخل الإجمالي كشريحة، مجموع الأعداد = عدد العقارات المشمولة والنسب ≈100% بعد التقريب؛ الإجمالي يظهر منفصلًا | `app/Services/Tenants/Dashboard/DashboardService.php::getPropertyStatusDistribution`، `app/Services/Tenants/Properties/PropertyService.php::getStatistics`، `resources/assets/js/tenant/dashboard/index.js`، `resources/views/tenant/dashboard/index.blade.php` | OVR-05..08 |
| U-02 | badge طويل: يظهر … داخل حد العرض، لا يكتب فوق الجوار؛ الأيقونة والعدد يظلان ظاهرين، والنص الكامل متاح بطريقة وصول مناسبة | `resources/assets/scss/shared/_badges.scss`، `resources/assets/css/shared/dataTable.css`، `resources/views/components/shared/ui/badge.blade.php`. توجد قاعدة صريحة تجعل overflow للـbadge داخل DataTable مرئيًا؛ تحقق أثرها قبل التعديل | SHD-03، USR-21، ROL-19 |
| U-03 | أيقونة copy مستقلة لكل recovery code في اللوحتين، بنفس أسلوب النسخ المشترك الموجود، تنسخ هذا الكود وحده وتظهر نتيجة مفهومة | `renderRecoveryCodes` بقالبي `resources/views/{admin,tenant}/auth/two-factor-settings.blade.php`، نمط `.copyable-code-wrapper` في `resources/assets/js/shared/datatable-page-defaults.js`. افحص تحميل السلوك في auth دون الاعتماد على تحميل DataTable | SEC-04..06 |
| U-04 | لا إنشاء دور بلا صلاحيات ولا تعديل دور لينتهي بلا صلاحيات، من الواجهة أو POST/PUT مباشر أو select_all أو مسار تعيين بديل | طلبات الدور الأربعة وخدمتا RoleService. توجد required_if بالفعل؛ بلاغ المستخدم يتطلب إعادة إنتاج المسار الذي يتجاوزها، لا افتراض عدم وجود قواعد | ROL-01..04 |
| U-05 | صلاحية overview مختارة دائمًا ولا يمكن نزعها، وتثبت في الخادم أيضًا؛ الدخول لمستخدم عادي صالح لا ينتهي بـ403 بسبب غيابها | المفتاح **Admin: `dashboard.main` / Tenant: `dashboard.view`** من seeders والمتحكمين. طلبات الدور والخدمات ونماذج الصلاحيات وpermission cache | ROL-05..09، USR-09، OVR-02 |
| U-06 | منع حذف role مرتبط بمستخدم، فردي وجماعي وأي مدخل حذف؛ نفس منع الجهات المسندة مع رسالة مفهومة وبقاء العلاقة | `app/Services/Admin/AdminMangment/Role/RoleService.php::deleteRole` و`app/Services/Tenants/UserManagement/Role/RoleService.php::canDeleteRole`، `app/Policies/Shared/RolePolicy.php`، ومسار mass-delete. المرجع: `IssuingEntityService::delete` يمنع الحذف عند `assignments()->exists()` | ROL-10..13 |
| U-07 | النقر اليدوي لحفظ المسودة يرجع success toast بعد ثبوت الحفظ، أو error toast عند الفشل، دون صمت أو نجاح زائف أو مضاعفة | `resources/assets/js/shared/form-validation.js`، `resources/assets/js/shared/wizard-draft.js`، `WizardDraftController`، `WizardDraftService`. يوجد مساران للـdraft وتنبيهات بالكود؛ افحص المحمل فعليًا وتوقيت تهيئة Toastr | SHD-05..08، USR-23، ROL-20، SUP-08 |
| U-08 | تفاصيل النشاط تتبع show المعتمد في المزادات/العقارات، دون فقد حقول النشاط وفروق قبل/بعد والصلاحيات | `resources/views/admin/activity-logs/show.blade.php` و`resources/views/tenant/activity-logs/show.blade.php`؛ المرجع `tenant/auctions/show.blade.php` و`tenant/properties/show.blade.php` | LOG-01..05 |
| U-09 | جميع th وtd في tables وDataTables تتمركز، بما فيها العناوين والأزرار والتحديد وempty/child rows؛ يبقى ترتيب الأرقام LTR سليمًا | `app/DataTables/Abstract/AbstractDataTable.php`، `resources/assets/css/shared/dataTable.css`، components وBlade tables. الحصر العام في ملف evidence؛ النطاقات المؤجلة تبقى معلّمة غير مراجعة وظيفيًا | SHD-01..02 وجداول كل ملف |
| U-10 | كل زر حذف فردي في index يعرض confirmation؛ الإلغاء لا يرسل، والتأكيد يرسل DELETE واحدًا للسجل الصحيح وينجح؛ mass-delete ليس بديلًا | `components/shared/actions/delete.blade.php`، `resources/assets/js/shared/confirm-delete.js`، action partial وscript loader لكل layout. افحص ضياع listener أو تصادم `window.confirmDelete` أو data-id/form-id | SHD-09..12، USR-16، ROL-10، ARC-05 |

**قراءة المصدر ليست اختبار تنفيذ:** القراءة تؤيد تداخل مجموعات chart وغياب copy من renderer الحالي، وتوضح احتمالات cascade وloading للسلوك المشترك. لا حالة من العشرة مغلقة في مرحلة الإعداد، ولا سبب جذري معلن لبقية البلاغات دون إعادة إنتاج.

### تفسير مطلبَي الأدوار والرسم

«الدور الفارغ» لا يشمل المسودة الناقصة التي لم تنشئ Role نهائيًا. overview صلاحية حقيقية، ولذلك الدور الذي يحمل overview وحدها يحقق عدم الفراغ ما لم يطلب المستخدم صلاحية أخرى. منع الفراغ وتثبيت overview شرطان مستقلان: افحص الطلب الفارغ قبل التطبيع، وأثبت أن الناتج النهائي لا يمكن أن يكون بلا overview. لا تختزل العلاج إلى required checkbox في HTML.

بالنسبة للرسم، الأبعاد الحالية مستقلة: النشاط، اكتمال البيانات، والارتباط بالإسناد. لا تغير حقيقة حالة العقار في قاعدة البيانات لإجباره على شريحة. قبل الإصلاح وثق مجموعة الحالات الحصرية وقاعدة تحديد الحالة من الدومين بحيث تغطي كل عقار مرة واحدة؛ إذا احتجت أولوية بين أبعاد مختلفة فاعرضها للمستخدم قبل اعتمادها. معيار عدم التداخل وثبات الإجمالي محسوم، وأسماء/معاني الحالات لا تُخترع ضمنيًا.

## الحالات المشتركة — تطبق على كل إدارة وصفحة منطبقة

| ID | الخطوات | المتوقع | الأولوية | الحالة |
| --- | --- | --- | --- | --- |
| SHD-01 | افتح كل جدول بيانات أصلي وDataTable وقارن computed text-align لكل th/td؛ كرر بعد البحث/فرز/redraw | center لجميع الخلايا؛ الأزرار/الأيقونات في مركز الخلية؛ لا تعالج ذلك داخل كل صفحة بpatch متكرر | P0 | not_run |
| SHD-02 | اختبر empty/loading/error/pagination/child rows، القيم null والنص الطويل والأرقام والأكواد و320px | العناوين والخلايا المولدة centered، لا overlap ولا كسر controls أو تسرب LTR إلى اتجاه الصفحة | P0 | not_run |
| SHD-03 | نص badge عربي طويل جدًا ثم إنجليزي بلا مسافات ومع icon/dot وعدة badges؛ داخل/خارج DataTable | … فعلي داخل العرض، النص لا يغطي الجوار، الأيقونة لا تختفي، النص الكامل متاح بـtitle/tooltip أو accessible name؛ اختبر cascade وhover | P0 | not_run |
| SHD-04 | راجع create/edit/show: input/password/select/file/validation/stepper/actions/attachment | shared controls وعقد data-form-*، create/edit partial واحد وis_edit، header مخفي لخطوة واحدة، file-card للمعاينات؛ لا page validation موازٍ لـFormRequest | P1 | not_run |
| SHD-05 | من create جديد احفظ بيانات ناقصة يدويًا كمسودة مرة ثم كررها | حفظ فعلي داخل DB الصحيح + success toast واحد لكل عملية مكتملة + زر يعود؛ لا كيان نهائي ولا فرض full required | P0 | not_run |
| SHD-06 | فشل 422 و403 و419 و500 وانقطاع الشبكة وJSON غير سليم/بلا draft_id أثناء الحفظ | error toast واضح، لا نجاح زائف ولا loading دائم ولا فقد المدخلات؛ لا تتسرب stacktrace | P0 | not_run |
| SHD-07 | أعد تحميل create، استعد/ارفض المسودة، غيّر ملفًا ثم احفظ؛ قارن مستخدمًا آخر ومستأجرًا آخر | قيم/ملفات سليمة، draft معزول بالمستخدم/resource/tenant، الإشعار مطابق للنتيجة؛ لا إظهار مسودة على edit | P0 | not_run |
| SHD-08 | أكمل إنشاء الكيان من المسودة، ثم افتح create؛ كرر النقر السريع | إزالة مسودته المؤقتة بعد النجاح فقط، لا تكرار سجل/ملفات/toasts؛ أي auto-save لا يزاحم toast اليدوي | P0 | not_run |
| SHD-09 | لكل delete فردي: انقر الأيقونة ثم مساحة الزر، من صف أول ومن صف بعد filter/redraw | confirmation المشترك يفتح للسجل الصحيح، دون خطأ Console؛ لا يلزم وجود handler خاص بالصف | P0 | not_run |
| SHD-10 | Cancel ثم Confirm؛ راقب Network وDB وإعادة التوجيه | الإلغاء لا DELETE؛ التأكيد DELETE واحد؛ HTML redirect + toast، وAJAX JSON منضبط؛ لا raw JSON في المتصفح | P0 | not_run |
| SHD-11 | كرر حذفًا ممنوعًا لارتباط/صلاحية، وسجل محذوفًا من تبويب آخر | منع من الخادم برسالة واضحة وبقاء البيانات والعلاقات؛ لا نجاح زائف ولا 500؛ التصفية والصفحات لا تضيع بلا سبب | P0 | not_run |
| SHD-12 | قارن single/mass delete، selectors/hidden forms، confirmDelete قبل وبعد modals/JS إضافي | العقد نفسه بلا overwrite أو form-id مكرر؛ bulk لا يتجاوز قيد single؛ تحميل confirm/toastr مرة واحدة في كل layout | P0 | not_run |
| SHD-13 | قارئ/محرر/مدير/ضيف؛ عدل IDs وguard وHost؛ A/B لهما نفس ID في قاعدتين | UI وصلاحيات endpoint متسقة، لا IDOR أو cross-tenant؛ مسارات admin بـroute وTenant بـtenant_route_name، الملفات بـtasset | P0 | not_run |
| SHD-14 | GET وPOST/PUT مباشر بقيم خاطئة، CSRF ناقص، فشل حفظ في منتصف العملية | FormRequest/Policy/Service موثقة، rollback للكتابات المرتبطة، errors عربية accessible، المدخلات غير الحساسة محفوظة | P1 | not_run |
| SHD-15 | stats→filters→DataTable toolbar، تبديل الحالة، select-all بعد redraw، export | لا heading/Add فوق stats؛ Add/actions في toolbar؛ الحالة قبل الإجراءات بسويتش مشترك؛ تحديد وتصدير يحترمان filters والصلاحيات والعزل | P1 | not_run |
| SHD-16 | شغّل routes/lint/الاختبارات الآمنة، وبعد أي CSS/SCSS/JS شغّل build ثم رحلات الواجهة | أوامر وexit codes ومرجع/hashes محفوظة؛ لا استدلال على UI من نجاح build، ولا بيانات حقيقية أو أسرار في الأدلة | P1 | not_run |

## حصر عام للمشترك

[المسارات الفعلية](evidence/dashboard-shared/route-inventory.json) حُصرت عبر `route:list`: 60 مسارًا canonical لـAdmin و65 لكل من Tenant path/domain ضمن المجموعات المحددة. لا يمثل العدد عدد الحالات أو نجاح المسارات. [حصر مصادر الجداول وأزرار الحذف](evidence/dashboard-shared/table-delete-inventory.json) يشمل المصادر المطابقة على مستوى الواجهات لتتبع U-09 وU-10؛ المطابقة النصية قد تشمل مكونات/مراجع، فيحدد المنفذ الصفحة المستهلكة الفعلية ويضيفها لسجل التحقق.

[حصر الصفحات](evidence/dashboard-shared/module-view-inventory.json) يغطي 70 ملف view ضمن المجموعات الست، و[بصمات المراجع](evidence/dashboard-shared/source-manifest.json) تثبت وجود 175 مرجعًا أساسيًا بالمسارات المحددة. حصر الجداول يضم 74 مصدرًا مطابقًا، والحذف الفردي 19 مصدرًا؛ هذه أعداد ملفات وليست عدد الصفحات العاملة أو نتائج قبول. [المسارات المساندة](evidence/dashboard-shared/shared-route-inventory.json) تحفظ wizard drafts وmass-delete وتفضيلات ظهور charts.

الحصر العام يمنع نسيان plain tables أو صفحات مؤجلة عند تعديل CSS/JS مشترك. لا يغلق إدارة مؤجلة بمجرد تحسن تنسيق جدولها. لا تعديل للتصاميم أو الأيقونات أو الترجمة خارج المطلوب.

## سجل التنفيذ وبوابة القبول

في ملف كل إدارة أضف صفًا **لكل حالة/variant/محاولة**: الوقت، المرجع، البيئة، ID الحالة وU/SHD المرتبط، بيانات اصطناعية، النتيجة الفعلية، السبب، الملفات، دليل HTTP/DB/browser/clipboard، الحالة، قرار Codex. افصل القراءة عن التنفيذ. الحالات الأولية كلها `not_run`؛ `fixed_pending_review`/`passed_pending_review` من Gemini؛ `verified` حصريًا لـCodex.

لا تغلق الإدارة حتى تنجح كل صفحاتها وعملياتها ومتغيراتها المنطبقة، وتغلق بلاغاتها P0، ويكتمل shared-component audit وGolden Pattern manifest، وتراجع الأدلة مستقلاً. blocked يبقيها مفتوحة. فرق قائم أو property جدول محسنة ليس قبولًا كافيًا. فحص حذف نهائي يكون فقط في fixture معزول محدد disposable؛ لا تمس بيانات العمل.
