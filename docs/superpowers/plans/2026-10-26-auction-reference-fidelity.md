# Auction Reference Fidelity Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox syntax for tracking.

**Goal:** إنتاج مخرجات وتقارير تطابق التصميم المرجعي مع بيانات وصور متغيرة، وإثبات المطابقة صفحة بصفحة.

**Architecture:** إعادة استخدام هندسة الحزمة الأصلية، مع عقد بيانات ومخطط صفحات موثقين ومصدر إخراج واحد. الاحتفاظ بمسار التوليد التلقائي وإصداراته وعزل المستأجرين، وحصر إعداد الطباعة الجديد في ملفات التقارير.

**Tech Stack:** Laravel/Blade، SVG/CSS، Chromium/Browsershot الحالي، اختبارات PHP وJavaScript الحالية، Poppler ومقارنة صور محلية.

**Spec:** `docs/superpowers/specs/2026-10-26-auction-reference-fidelity-design.md`

## Global Constraints

- لا fresh ولا seed ولا حذف أو استبدال بيانات المزادين 5 و6 أو مرفقاتهما.
- لا إعادة Samples ولا تحويل أنواع brochure/billboard/social إلى outputs/report.
- لا بدائل خطوط صامتة، لا صور مرجع متسربة لمزاد جديد، لا أرقام أو نتائج مختلقة.
- ألوان وأحجام وإحداثيات من المصدر المقاس؛ لا قيم تقريبية مختارة بصريًا.
- أي تعديل JS/CSS/SCSS يستلزم `npm run build`؛ لا commit تلقائيًا لعمل المستخدم.
- `docs/architecture/golden-pattern.md` مرجع أي تغيير حقول؛ الوصول للمركزي عبر Gateway فقط.

## Review Focus

1. حروف عربية غير موجودة في subset: اكتشاف النقص ومنع اعتماد تطابق بخط بديل، مهمة 2.
2. بيانات أصلية مخبأة في SVG/خلفية/صورة عقد: صفر تسرب في حالة مزاد جديد، مهمتا 3 و4.
3. حدود سعة الصفوف والصور والنصوص الطويلة: متابعة دون قص أو اختفاء أو تصغير صامت، مهمة 5.
4. تعارض المعاينة مع PDF/PNG بسبب التأخر أو اختلاف القص: مصدر موحد وإشارة جاهزية، مهمة 6.
5. مهمة قديمة أو فشل الملف الثاني/سياق مستأجر مختلف: لا نشر ملفات مختلطة ولا وصول عابر للمستأجر، مهمة 7.

## ترتيب العمل وبوابات المراجعة

تسلسل إلزامي: تجميد المرجع ← جرد ومعايرة ← خطوط ← بيانات ← صفحات ← متابعة ← PDF/PNG ← دورة تلقائية ومقارنة نهائية. تُراجع أولًا أربعة نماذج: غلاف الحرم، جدول الحرم، غلاف عسير، جدول عسير؛ اعتمادها لا يعني اعتماد بقية الصفحات.

### Task 1: تثبيت المرجع ومعايرة الحزمة قبل البيانات

**Files:**
- Create: `resources/auction-references/manifest.json` (بصمات ومسارات محركات وإصدارات؛ لا بيانات مستأجرين).
- Create: `docs/auction-references/page-specification.md` و`docs/auction-references/field-mapping.md`.
- Create: `scripts/auction-references/compare.mjs` و`tests/fixtures/auction-references/expected-manifest.json`.
- Inspect: `resources/views/tenant/auctions/reports/engines/haram.html` و`asir.html` والحزمة في Downloads.

**Interfaces:** ينتج manifest بإصدار، ملف مصدر وSHA256، profile، صفحات بمقاس pt ومعرف template، موارد بأدوارها، حقول وخطوط وجداول ومعايير مقارنة. `compare.mjs --reference <pdf> --actual <pdf> --out <dir>` ينتج JSON لكل الصفحات وصور overlay/difference واتصال الأخطاء برقم الصفحة.

- [ ] تأكيد المصدر الأعلى: PDF الأصلي إن توفر؛ وإلا اعتماد print-PDF مؤقتًا مع بيان حدوده. مقارنة نسختي Downloads والمحركات الموجودة بالبصمة، لا استبدال عمياني.
- [ ] حفظ بصمات ونسخة مدخلات وصور المزادين الحالية دون تعديلهما؛ النسخ الكبيرة/البيانات الخاصة خارج Git.
- [ ] إعادة التحقق من سياق tenant/اسم القاعدة واسم المزاد وID قبل أي كتابة؛ IDs المذكورة من الجولة السابقة وليست تحققًا مباشرًا ناجحًا في جلسة التخطيط الحالية. الاحتفاظ بـfixture المرجع مستقلة عن snapshots المزادين؛ لا تعديلهما لتكميل بيانات الأصل.
- [ ] استخراج خصائص SVG/CSS وأسماء الخطوط والأوزان والقص والجداول لكل صفحة إلى مواصفة قابلة للمراجعة. تعيين المحتوى/القناع/الشعار لكل صورة بالمعاينة.
- [ ] اختبار أداة المقارنة على ملفين متطابقين: صفر اختلاف؛ ملف به صفحة ناقصة: إخفاق واضح؛ ملف به مستطيل مزاح: تحديد الصفحة والمنطقة. لا تحسين تلقائي للصورة لإخفاء الفروق.
- [ ] طباعة الحزمة unchanged دون قيم Laravel ثم مقارنة 7 و21 صفحة؛ تصنيف فرق المقاس والتنعيم والظلال. لا الانتقال لربط البيانات قبل تفسير هذه الفروق.

**Deliverable:** مرجع مجمّد ومواصفة قياس وخريطة موارد، وتقرير baseline دون Laravel.

### Task 2: تثبيت خطوط الهوية وإزالة الاستبدال الشامل

**Files:**
- Modify: `app/Services/Tenants/Auctions/Reports/AuctionTemplateDataBuilder.php::getCustomFonts()`.
- Create: `resources/auction-references/fonts.json`؛ ملفات الخطوط المصرح بها تحت `public/static/fonts/auction-references/` وفق حق التوزيع.
- Create: `tests/Unit/Auctions/AuctionReferenceFontsTest.php`.

**Interfaces:** font manifest يربط family/weight بملف وبصمة وlicense وfull/subset؛ payload لا يغير family الأصلية. حالة preflight تحتوي قائمة حقول ذات خط مفقود/تغطية ناقصة، لا fallback مجهول.

- [ ] جرد الخطوط المستخدمة فعلًا في حقول متغيرة، وتسليم المستخدم قائمة الملفات المطلوبة بدقة قبل شرائها/طلبها.
- [ ] RED: نص عربي جديد خارج حروف الأصل لا يُعتمد بخط subset؛ Noto وIBM الكاملان يحتفظان بعائلتهما؛ regular/bold لا يستبدلان بعضهما.
- [ ] إزالة تحويل كل العائلات إلى Almarai؛ ربط كل عائلة بملفها الصحيح وتوثيق أي نقص. لا تحميل خطوط غير موثوقة من عنوان يدخله المستخدم.
- [ ] GREEN: تشغيل الاختبار؛ إخراج عينة «مزاد» باسم جديد ونص مشكول وأرقام مختلطة؛ فحص بصري وتضمين الخط/رسوماته. أي نقص يظل بندًا مانعًا للاعتماد النهائي.

### Task 3: استكمال عقد البيانات والـfixtures

**Files:**
- Modify: `app/Services/Tenants/Auctions/Reports/AuctionReportDataBuilder.php::build(Auction): array`.
- Modify: `app/Services/Tenants/Auctions/Reports/AuctionTemplateDataBuilder.php::buildOutputsPayload(array): array` و`buildReportPayload(array): array`.
- Extend: `tests/Unit/Auctions/AuctionReportDataTest.php`.
- Create: `tests/Unit/Auctions/AuctionTemplateDataBuilderTest.php` و`tests/fixtures/auction-references/{haram,asir}-bindings.json`.
- Conditional: tenant migration/FormRequests/Actions/auction step partials فقط للحقول المثبت نقصها في field-mapping؛ تعيين مساراتها بعد الجرد وقبل تعديلها.

مسارات التوسعة المحتملة الموجودة بالفعل: `app/Http/Requests/Tenants/Auctions/{StoreAuctionRequest,UpdateAuctionRequest}.php`، `app/Actions/Tenants/Auctions/{CreateAuctionAction,UpdateAuctionAction}.php`، `resources/views/tenant/auctions/partials/steps/{step1-basic-info,step3-properties,step4-settings,step5-review}.blade.php`. يحدد الجرد الحاجة الفعلية لكل منها؛ لا تعدل جميعها افتراضيًا. أي migration جديدة تكون additive داخل `database/migrations/tenant/`، ولا تعيد تشغيل migration إنشاء الجدول.

**Interfaces:** snapshot يصير versioned ويضيف بيانات مثبتة المصدر فقط. كل binding يربط profile/template/field-id أو table/column أو image-id بمصدر snapshot وتنسيق وقاعدة empty. لا استنتاج من اسم المزاد ولا position في مصفوفة مرفقات غير مصنفة.

- [ ] كتابة mapping لكل حقل ولكل ظهور داخل الجمل والجداول؛ حسم source-of-truth والتنسيق الميلادي/الهجري مع المستخدم عند الحاجة.
- [ ] RED: لا فقد صك/أصل متعدد، فصل board_before/after، فصل القناع عن الصورة، ترتيب موثق، null مختلف عن 0، إجماليات صحيحة، تصفير كل بقايا بيانات المصدر عند استخدام اسم جديد.
- [ ] إعداد fixture نقل مطابق يدويًا للمرجع بعد مراجعة كل الصفوف والصور، مع سجل يقين/نقص. لا نقل بيانات مشوهة من استخراج نص آلي بوصفها مؤكدة.
- [ ] استكمال الباني بالحقول الموجودة أولًا، ثم تحديد أقل حقول ناقصة. أي توسعة قاعدة/واجهة ترفق بقائمة ملفات دقيقة واختبارات validation/authorization/isolation وبنمط النماذج الحالي.
- [ ] تشغيل `php artisan test tests/Unit/Auctions/AuctionReportDataTest.php tests/Unit/Auctions/AuctionTemplateDataBuilderTest.php`؛ تتطابق 11/13 عقارًا ونتائجهما وإجمالياتهما مع fixture.

### Task 4: ربط الهندسة الأصلية وبناء جميع الأقسام

**Files:**
- Modify: `app/Services/Tenants/Auctions/Reports/AuctionReportRenderer.php::html(array $snapshot, string $type): string`.
- Modify after diff: `resources/views/tenant/auctions/reports/engines/{haram,asir}.html`.
- Modify or replace internal delegation: `resources/views/tenant/auctions/reports/{outputs,report}.blade.php`؛ لا استعمال styles العامة الجديدة لإعادة رسم الأصل تقريبًا.
- Create: `app/Services/Tenants/Auctions/Reports/AuctionReferencePageCompiler.php` واختباره `tests/Unit/Auctions/AuctionReferencePageCompilerTest.php`؛ قوالب الصفحات المستخرجة تحت `resources/views/tenant/auctions/reports/reference-pages/`.
- Modify: `tests/Unit/Auctions/AuctionReportRendererTest.php` ليختبر التصميم المعتمد بدل فرض chunk=8 وصفحات العقارات الحالية.

**Interfaces:** `AuctionReferencePageCompiler::compile(array $snapshot, string $type): array` ينتج قائمة صفحات مرتبة تحتوي `template_id`, `html`, `width_pt`, `height_pt` بعد استبدال كل binding على الخادم. Blade يضم الصفحات النهائية قبل استدعاء المتصفح. `window.AuctionTemplate.setData/preparePrint` خاصان بمعايرة الحزمة في مهمة 1 فقط. لا بناء دلالي للبيانات على JavaScript وقت التصدير؛ فحص جاهزية الخطوط والصور في المتصفح لا يغير المحتوى. escape للنصوص وتقييد الموارد يمنعان تنفيذ مدخلات المستخدم.

- [ ] RED: كل الأقسام الـ7 والـ21 موجودة مع fixture الكامل؛ لا صفحات عقار عامة إضافية؛ لا بيانات أصل متبقية مع بيانات جديدة؛ صورة محتوى جديدة لا تغير mask أو logo.
- [ ] ربط renderer بصفحات Blade/SVG المستخرجة للنوع، مع compile كامل على الخادم لكل صفحة، لا subset من صفحات عسير. محركا HTML مصدر استخراج واختبار لا مسار إنتاج جديد. تحرير نصوص المصدر المتجهة عند تغييرها حتى لا تظهر نسخة قديمة تحت الجديدة.
- [ ] استعادة الشعارات والزخارف الأصلية والألوان والتدرجات والإحداثيات والقص دون global CSS يؤثر عليها؛ إزالة الإضافات غير الموجودة بالمرجع.
- [ ] إنهاء الحرم صفحة صفحة وفق خريطة spec، ثم عسير: 1–2، 3–7، 8–18، 19–21. مراجعة كل مجموعة قبل التالية بالصور جنبًا لجنب.
- [ ] GREEN: لا clipping أو تسرب أسماء/صكوك/روابط قديمة. لا نعتمد نفي substring داخل الملف كله لأن الحزمة قد تحتوي بيانات غير مستخدمة؛ نفحص DOM النهائي وPDF والصور المركبة فعليًا.

### Task 5: متابعة الصفحات والحالات المتغيرة

**Files:** `AuctionReferencePageCompiler.php`، باني payload السابق، اختبارات `AuctionReferencePageCompilerTest.php` و`AuctionTemplateDataBuilderTest.php`.

**Interfaces:** خطة صفحات بأرقام section/sequence/template/source-rows وصور ذات IDs ثابتة؛ capacities وbounding boxes من manifest؛ قائمة overflow قابلة للعرض قبل اعتماد الإخراج.

- [ ] RED: جدول عند السعة وما بعدها بصف واحد، صف صكين/سبب طويل، صفر مبيعات، جميع العقارات مباعة، أدلة 0/1/السعة+1، اسم طويل وصور بأبعاد مختلفة.
- [ ] متابعة بنفس رأس الجدول والتذييل وأبعاد أعمدته، مع ترقيم متصل وربط كل صورة بعقارها؛ لا تكرار صور لسد الفراغ أو فقد الصف الأخير.
- [ ] تطبيق قاعدة المحتوى الناقص التي يوافق عليها المستخدم؛ لا حذف فصل حكومي لمجرد غياب صورة ولا استنساخ أرقام/عقود المرجع.
- [ ] GREEN: العدد والترتيب والمحتوى كامل عبر جميع الصفحات، وغياب أي overflow غير معلن. لا تخفيض font-size صامتًا.

### Task 6: معايرة PDF وPNG من المصدر نفسه

**Files:**
- Modify: `app/Services/Tenants/Auctions/BrowsershotDocumentRenderer.php`.
- Modify: `config/auction-renderer.php` فقط لإعداد profile الخاص بالتقارير عند الحاجة.
- Extend: `tests/Unit/Auctions/AuctionReportRendererTest.php`؛ create `tests/Feature/Tenants/Auctions/AuctionReferenceRenderingTest.php`.
- Preserve: `app/Contracts/Auctions/BrowserDocumentRenderer.php` signatures؛ أي توسيع لا يكسر exporters الحاليين.

- [ ] RED: لا تصدير قبل ready، خطأ تحميل خط/صورة يبلغ بفشل واضح، حجم كل profile يطابق المرجع المقاس، PNG يتضمن كل صفحات النوع وبنفس القص.
- [ ] ضبط طباعة backgrounds والمقاس من CSS والنقاط الأصلية، مقياس 100%، صفر margins/headers/footers؛ تثبيت إصدار Chromium وإعدادات اللون والدقة في manifest. `network idle` وحده لا يثبت الجاهزية.
- [ ] استخدام نفس DOM النهائي للـPDF وصور صفحات PNG. عزل إعدادات التقارير كي لا تتغير أبعاد PNG للبروشور أو اللوحات أو السوشيال.
- [ ] `npm run build` بعد تعديل الأصول؛ اختبار تصدير مرتين بنفس البيانات للتحقق من استقرار التصميم، مع استبعاد metadata الزمنية من مقارنة المحتوى فقط.
- [ ] معايرة أول أربع صفحات نموذجية ثم كل صفحات المرجعين؛ تسجيل أي فرق من Chromium لا يمكن إزالته، وعدم قبوله نيابة عن المستخدم.

### Task 7: إثبات التشغيل التلقائي والتطابق النهائي

**Files:**
- Extend: `tests/Feature/Tenants/Auctions/{AuctionReportWorkflowTest,AuctionReportGenerationTest,AuctionReportIsolationTest}.php`.
- Modify if necessary only: `app/Jobs/Tenants/Auctions/GenerateAuctionReportsJob.php` لحفظ هوية القالب/preflight ضمن عقد الإصدارات دون تغيير عزل الطابور.
- Output: `output/pdf/reference-fidelity/` (نسخة لكل مقارنة دون الكتابة فوق baseline السابق).
- Update: `docs/auction-report-worker.md` بتفاصيل الخطوط والمحرك عند تغير متطلبات التشغيل.

- [ ] RED/GREEN: فشل الملف الثاني لا يعلن ready؛ إصدار قديم لا ينشر نفسه؛ مستأجر آخر لا يرى snapshot أو المرفقات؛ خطأ preflight يمكن معالجته وإعادة التوليد بعد التصحيح.
- [ ] تشغيل `php artisan test tests/Unit/Auctions tests/Feature/Tenants/Auctions`، و`npm run test:auction-designer` إذا تأثر المصمم، و`composer validate --no-check-publish` و`git diff --check`، و`npm run build` عند تغيير الأصول.
- [ ] اختبار حفظ مزاد تجريبي عبر الواجهة ثم تعديل قيمة وصورة، انتظار العامل، تنزيل الملفين من واجهة المستخدم؛ لا الاكتفاء باستدعاء renderer يدويًا.
- [ ] مقارنة fixture الأصلي كاملة دون mask لمناطق النصوص، ثم مقارنة الحالات المتغيرة هندسيًا دون توقع نفس عدد الصفحات أو بكسلات الصور الجديدة.
- [ ] إصدار PDF وPNG لكل صفحة، صور مقارنة وoverlay/difference، فحص كل الصفحات والأغلفة والجداول عند 300/600DPI، وتقرير موقع عليه بالملاحظات المتبقية بدل نسبة عامة مضللة.
- [ ] مراجعة المستخدم للغلافين وجدولي البيانات ثم التقرير الكامل. لا إعلان تطابق نهائي قبل حسم الخطوط والمحتوى وكل فروق التصميم.

## نقاط تحتاج قرار المستخدم

1. تعميم مرجع الحرم للمخرجات وعسير للتقرير، أو وجود عائلات متعددة.
2. مصدر PDF الأصلي قبل استخراج HTML، إن كان متاحًا؛ استخدام ملفات الحزمة مؤقتًا فقط عند عدم توفره.
3. إتاحة الخطوط الأصلية؛ لا وعد بمطابقة أسماء جديدة بنفس الخط دون ملفاتها الكاملة.
4. السماح بتعديل التصميم الرسمي وصلاحياته، وقاعدة القسم غير المنطبق. لا تنفيذ صلاحيات أو إدارة جديدة قبل تثبيت هذا القرار.

## ما يسلمه التنفيذ

خريطة موثقة للصفحات والحقول والخطوط، مصدر إخراج موحد مرتبط بالبيانات، الملفان المولدان تلقائيًا، PNG لكل صفحة، مقارنة شاملة بكل الفروق ومعاييرها، واختبارات تثبت عدم كسر التوليد والعزل. لا reset للبيانات ولا إصلاحات جانبية خارج هذا الهدف.
