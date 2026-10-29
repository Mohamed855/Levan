# Auction PDF Output Remediation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans for native execution, or superpowers:subagent-driven-development only if the user chooses delegated execution. Track each step with checkboxes. This document is a plan, not authorization to implement it.

**Goal:** إغلاق عيوب المستندات الأربعة الفعلية، مع الحفاظ على تصميم المرجعين وصحة بيانات كل مزاد، والتحقق من الاستخراج اليدوي والتلقائي لكل تصميم.

**Architecture:** الإبقاء على المسار الحالي Snapshot → Pagination → Data Binding → SVG → PDF. إصلاح عقد البيانات والتخطيط داخل الخدمات الحالية بدل إعادة بناء مصمم القوالب أو إضافة طبقة Repository. فصل القرارات الدلالية للقنوات والأدلة عن هندسة الصفحة، وتوحيد قياس النص مع الخط الذي يُرسم فعليًا.

**Tech Stack:** Laravel 11 / PHP 8.2+، Blade، Bootstrap 5، jQuery، Browsershot/Chromium، SVG، Poppler، PHPUnit، Python runtime المعزول للفحص فقط.

**Spec:** [مراجعة الملفات الستة وسجل F01–F18](../../auction-reports/2026-10-28-actual-output-review.md).

## Global Constraints

- لا `fresh` أو `refresh` أو `truncate` أو إعادة seed عامة؛ لا تغيير لبيانات tenant `test` أو صوره دون موافقة محددة ونسخة استرجاع.
- لا تشغيل لاختبارات Feature الحالية على قواعد `lot_legacy` / `levan_test` المشتركة. تفعيل حارس عزل قبل أي اختبار يتصل بقاعدة بيانات.
- الأصليان المعتمدان هما O-ref وR-ref في مجلد Downloads/outputs، بالبصمتين المسجلتين في التقرير، وليس `haram-print.pdf` / `asir-print.pdf` القديمين.
- المخرجات: كلا التصميمين لكلا المزادين. صحة بيانات «الحرم» لا تعني صحة تصميم تقريره، وصحة تقرير «عسير» لا تعني صحة مخرجاته.
- لا نقل قيم أو صور مزاد المرجع إلى مزاد آخر. لا حسابات مشاهدات/أرقام/أعمال منفذة افتراضية لملء الفراغات.
- تحديث نطاق مأذون به: ثُبتت الخطوط الكاملة المطابقة المقدمة لاحقًا؛ المؤجل فقط Ruaq Arabic Medium وAptos Narrow Bold. لا بدائل متشابهة الاسم.
- الشعار البديل يبقى للمرفق المتوقع المفقود، وليس لكل خانة غير مستخدمة في صفحة امتداد. الخلفيات cover؛ أدلة المستندات ولقطات الشاشة contain.
- العناوين حجمها ديناميكي حسب طلب المستخدم: اختيار أكبر حجم لا يتجاوز الحجم الأصلي ويُظهر العنوان كاملًا في سطرين كحد أقصى داخل صندوقه. لا حذف كلمات أوellipsis أو صفحة جديدة بسبب العنوان. بقية النصوص لا تُصغّر تلقائيًا بهذه السياسة.
- حد التصغير المقترح لحماية القراءة هو70% من حجم العنوان الأصلي، قابل للضبط لكل عنوان؛ هذا قرار تصميم مقترح لا قيمة طلبها المستخدم. إذا لم يسع النص سطرين عند الحد الأدنى، يظهر تنبيه واضح بدل إخراج عنوان مقصوص أو غير مقروء.
- أسماء المفاتيح والقنوات والأدوار في DB إنجليزية. التسميات عربية عبر localization فقط.
- تدفق التعديل: Controller → FormRequest → Policy → Action/Service → UI → Tests. الحفاظ على حدود Tenant وملفات المخرجات الخاصة.
- أي تعديل JS/CSS/SCSS يستلزم `npm run build`. لا تغيير لتصميم إدارات أخرى أو إعادة ميزة Samples المحذوفة.
- تعديل بيانات القبول لاحقًا يكون محدودًا بالإسنادين والعقارات المرتبطة والمزادين ومرفقاتهما فقط، بعد مراجعة dry-run؛ ليس جزءًا من تنفيذ كود الإصلاح افتراضيًا.

## Review Focus

1. عنوان عربي طويل: يصغر عند الحاجة فقط لأكبر حجم يسع سطرين، دون تغيير الخط أو حذف كلمات. شهر «أغسطس» ورقم العقار10 يبقيان كاملين داخل وحدتيهما؛ لا صفحات وهمية — Task3.
2. عقار بصكين ووصف طويل بين عقارين عاديين: لا يضيع ولا يتكرر ولا يستنزف صفحة كاملة بلا ضرورة — Task4.
3. اختلاف ترتيب الحملات ووجود أكثر من3قنوات: كل رقم وصورة ورابط تحت القناة الصحيحة في التصميمين — Tasks5/6/7.
4. أدلة صفر/سعة/سعة+1، صورة رأسية، PDF متعدد الصفحات، وملف مفقود: لا قص ولا فقد ولا صفحات placeholders إضافية — Tasks6/8.
5. تعديل المزاد أثناء معالجة إصدار أقدم وفشل ملف واحد: أحدث إصدار فقط هو القابل للتنزيل، ولا نجاح جزئي أو تسريب tenant آخر — Task12.

---

## حدود الملفات ومسؤولية كل جزء

المسارات أدناه نسبية لجذر `D:/Levan`. الإضافات المذكورة **مقترحة للتنفيذ**، وليست ملفات موجودة الآن.

| الملفات | المسؤولية |
|---|---|
| `tests/Support/TenantAuctionReportTestCase.php` + `tests/Feature/Tenants/Auctions/AuctionReportIsolationTest.php` | عزل الاختبارات وحماية قواعد المستخدم |
| `tests/Fixtures/AuctionReports/{haram,asir}.php` + `acceptance-manifest.json` الجديد | بيانات قبول ثابتة، مع مصادر وأدوار الوسائط وبصماتها |
| `resources/auction-reports/fonts/{catalog.php,numeric-metrics.php}` + ملف Arimo الكامل وترخيصه | أرقام كاملة قابلة للقياس والرسم |
| `AuctionReportTextLayout.php` + `AuctionReportArtwork.php` تحت `app/Services/Tenants/Auctions/Reports/` | القياس، الحاويات، تعبئة الصفوف، الرسم، الروابط، Unicode |
| `resources/auction-reports/{haram,asir}/{bindings.php,pages.php}` | ربط الحقول وحاوياتها والهندسة، دون بيانات مزاد ثابتة |
| `AuctionReportPaginator.php` + `HaramReportData.php` + `AsirReportData.php` | خطة الصفحات وربط المعاني، لا التخمين من ترتيب القوائم |
| `config/auction-report-inputs.php` الجديد | القنوات والأقسام والأدوار المسموح بها، بلا قواعد validation مكررة |
| `StoreAuctionRequest.php` و`UpdateAuctionRequest.php` + `AuctionReportInputService.php` | الإدخال والتحقق والحفظ والاستعادة |
| `app/Models/Tenants/Auctions/AuctionAttachment.php` + migration tenant جديدة | metadata اختيارية للمرفق، مع ترحيل إضافي غير هدّام |
| `AuctionReportDataBuilder.php` + `AuctionReportMedia.php` | Snapshot مستقل يحفظ تصنيف الملف وملكيته وكل صفحات PDF |
| `resources/views/tenant/auctions/partials/steps/{step4-settings,step5-review}.blade.php` + `resources/assets/js/tenant/auctions/create.js` | إدخال بيانات التقرير وتحريرها وعرض مراجعتها بالمكونات المشتركة |
| `AuctionReportCharts.php` الجديد | SVG إحصائي من القيم الفعلية فقط |
| `resources/views/tenant/auctions/reports/layouts/document.blade.php` + `config/auction-report-templates.php` | مقاس الطباعة وطبقة الروابط وهندسة الصفحة |
| `scripts/auction-reports/{compare-pages.py,assert-pdf-contract.ps1,verify-queued-reports.php}` | مقارنة حقيقية وقبول الأربعة ملفات لا حالتين فقط |

لا تعديلات على auth أو واجهات خارج المزادات. أي ملف إضافي يثبت احتياجه أثناء التنفيذ يُذكر أولًا مع سبب، دون توسع تلقائي.

## ترتيب الاعتماد

Task1 → Task2 → Task3 → Task4. ثم Task5 → Task6 → Task7 → Task8 → Task9 → Task10 → Task11 → Task12. لا تُنفذ إعادة تجهيز بيانات القبول إلا في الخطوة المأذون بها من Task11.

### Task 1: تجميد المراجع وعزل الاختبارات

**Files:**

- Modify: `tests/Support/TenantAuctionReportTestCase.php`.
- Modify/Test: `tests/Feature/Tenants/Auctions/AuctionReportIsolationTest.php`.
- Create: `tests/Fixtures/AuctionReports/acceptance-manifest.json`.
- Modify: `docs/auction-reports/reference-manifest.md`.

**Interfaces:** manifest يحتوي `references[design]={path,sha256,media_box}` و`auctions[key]={properties,sold,unsold,sales_total,media[]}`. كل media يسجل `source_hash,section,channel,display_order,property_key,available`، لا credentials ولا مسارات تخزين تُعرض للمستخدم.

- [x] اكتب unit guard يرفض `tenant=test` أو أسماء قواعد المستخدم، ويشترط `AUCTION_REPORT_TEST_TENANT_ID=auction_report_ci` واسم قاعدتي الاختبار المنفصلتين `levan_report_ci_central` / `levan_report_ci_tenant` قبل أول اتصال أو transaction.
- [x] تحقق من فشل الحارس عند إعدادات الاختبار الحالية، دون إنشاء اتصال DB. لا تشغّل suite Feature لاختبار أن الحارس يعمل.
- [x] عدّل helper ليستخدم بيئة CI المعزولة صراحة، ويحافظ على transaction rollback. تأكد من صلاحية ووجود المخططين المخصصين؛ لا تنشئهما أو تمسحهما ضمنيًا. إذا لم يكونا مجهزين، تتوقف اختبارات DB وتُطلب موافقة تجهيز معزول.
- [x] أثبت بصمتي المرجع من التقرير وسجل حدود المصدر: الحرم11عقارًا، 4مباع/7غيرمباع، 5,670,000؛ عسير13عقارًا، 3مباع/10غيرمباع، 4,026,000. الوسائط تُجرد؛ لا تفترض أن كل ما استُعيد دليل أعمال صالح.
- [x] تحقق: unit guard يمر، fingerprint mismatch يفشل بوضوح، وبيانات test لا تتغير. حدّث manifest ووثيقة المراجع؛ مراجعة ثم commit محدود لهذه المهمة.

### Task 2: سلامة الأرقام وطبقة النص في PDF

**Files:** `AuctionReportArtwork.php`، `AuctionReportTextLayout.php`، `resources/auction-reports/fonts/{catalog.php,numeric-metrics.php}`، `scripts/auction-reports/measure-numeric-fonts.php`؛ إضافة `arimo-bold.ttf` وترخيصه من توزيعة كاملة مرخصة، لا من subset PDF. Tests: `AuctionReportArtworkTest.php` و`AuctionReportTextLayoutTest.php`؛ إضافة `tests/Unit/Auctions/AuctionReportPdfTextTest.php`.

**Interfaces:** يبقى `wrap(string $value, float $width, float $size, string $font): array`. الخط الذي يقيسه `width` يجب أن يساوي المستخدم في `Artwork::text`، مع تحويل scale نفسه. لا مصدر مقاييس صفري لحرف ظاهر.

- [x] اكتب فشلًا لكل `0123456789٠١٢٣٤٥٦٧٨٩,./:-%` بعرض موجب عندما يكون المحرف مرئيًا، ولاختبار `573,000`, `554,000`, `220,000`, `1,547`, `1,000,006`, `78,000` بصريًا ونصيًا، لا نصيًا فقط.
- [x] ولّد PDF مصغرًا في scratch بلا DB، وأثبت تراكب الأرقام الحالي وخرائط `<0000>`. احفظ baseline لاختبار الانحدار.
- [x] استبدل subset Arimo بمصدر كامل، وأعد قياس الأرقام من الخط الكامل. وحّد اختيار الخط قبل القياس والرسم؛ لا تعالج التراكب بتغيير القيم أو تباعد مصطنع لكل رقم.
- [x] جرّب تضمين Noto/IBM كـstatic TTF instances كاملة عند الوزن المستخدم بدل مسار يولّد Type3 مع خرائط معيبة. يُعتمد المسار الذي يجتاز بحث/نسخ «مزاد جوار الحرم» و«واجهة عسير» والجمل العربية والأرقام، دون تحويل النص صورة. إن استمر العيب، يعزل مصغّر SVG/Chromium قبل تغيير backend؛ لا يُعلن الحل من قياس خطوط وحده.
- [x] تحقق: اختبارات Unit المحددة تمر، ثم render150dpi للمصغّر: كل رقم ظاهر بوضوح، الفصل والفواصل صحيحة، النص العربي المتوقع قابل للاسترجاع بعدNFKC، ولا NUL أوreplacement characters في الحقول الديناميكية. راجع وcommit.

### Task 3: عناوين ديناميكية بحد أقصى سطرين وحقول ذرية

**Files:** `AuctionReportTextLayout.php`، `AuctionReportArtwork.php`، `AuctionReportRenderer.php`، `resources/views/tenant/auctions/reports/layouts/document.blade.php`، `resources/auction-reports/{haram,asir}/bindings.php`. Tests: `AuctionReportTextLayoutTest.php` و`AuctionReportRendererTest.php` و`AuctionReportArtworkTest.php`.

**Interfaces:** إضافة metadata لكل role في `layouts`: `box=[left,top,right,bottom]`, `max_lines`, `overflow=error|continue_section`. box هو المساحة المرئية الفعلية، لا حدود حبر حروف المصدر. العنوان وحده يأخذ `fit=shrink`, `max_lines=2`, `max_font_size=original_size`, `min_font_size=original_size*0.70`، مع دقة اختيار0.25pt. يحتفظ `flow(array $content,array $definition): array` بعقده ويضيف `field_layouts[role]={font_size,lines}` للعنوان. تضيف `Artwork::render` معاملًا اختياريًا أخيرًا `array $fieldLayouts=[]` يمرره document view؛ الرسم يستهلك الحجم والأسطر المحسوبة نفسها بدل إعادة لف العنوان. `continue_section` للنص السردي فقط، وليس نسخ غلاف أو منصة أو نتائج.

- [x] اكتب اختبارات عنوان قصير يبقى بحجمه الأصلي، وعنوان يسع سطرين بالحجم الأصلي لا يُصغّر، وعنوان أطول يحتاج تصغيرًا لأكبر حجم يسع سطرين داخل العرض والارتفاع. النص كامل بلا حذف، واسم الخط والوزن واللون لا تتغير. عنوان لا يسع عند الحد الأدنى ينتج تنبيهًا، لا سطرًا ثالثًا أو صفحة جديدة.
- [x] اكتب حالات «مزاد جوار الحرم» غلافًا واحدًا؛ المدير «أ/ أسامه الحملي» والمشروع «عسير154» لا يتوزعان على غلافين؛ «أغسطس» لا ينقسم؛ أرقام1–13 كاملة؛ نطاق13–16أغسطس2026 داخل بطاقة المنصة. أكّد أن تصغير العناوين لا يمتد للأرقام والفقرات والجداول.
- [x] شغّل الاختبارات على الوضع الحالي وأثبت الفشل في عدد الصفحات/قيمة الحقل كاملة.
- [x] قِس صناديق العناوين وبطاقات التاريخ والمدير والمشروع وسجلها في bindings. جرّب حجم العنوان الأصلي أولًا، ثم ابحث تنازليًا عن أكبر حجم يسع النص كاملًا في سطرين ضمن العرض والارتفاع. افحص القياس بالخط النهائي بعد تحميله وبنفس تحويلات الرسم، لا بتقدير طول النص أوعدد حروفه. عند بلوغ الحد الأدنى دون ملاءمة، أظهر تنبيه preflight بدل قص الكلمات أوتجاوز الصندوق.
- [x] ألغِ نسخ الصفحة بسبب scalar عام. الحقول العددية لا تُقسم لمحارف؛ تواريخ/شهور لا تُفصل. احسب baseline والارتفاع من المنطقة والخط الفعلي، لا من عرض كلمة «مايو» الأصلية.
- [x] تحقق: الاختبارات تمر وغلافا المزادين في التصميمين صفحة واحدة لكل مستند؛ كل عنوان سطر أو سطران بأكبر حجم مناسب، دون امتداد فهرس فارغ بسبب الاسم أوالتاريخ أو منصة مكررة بسبب السنة. ولّد PDF وPNG من نفس التخطيط وتحقق بصريًا من تطابق حجم العنوان والأسطر وعدم القص والتداخل؛ راجع ثم commit.

### Task 4: تعبئة الجداول حسب ارتفاع الصفوف

**Files:** `AuctionReportTextLayout.php`، `AuctionReportArtwork.php`، تعريفات الجداول في `resources/auction-reports/{haram,asir}/pages.php`. Tests: `AuctionReportTextLayoutTest.php` و`AuctionReportArtworkTest.php`.

**Interfaces:** `table_layouts[$tableId]['rows']` قائمة `{top:float,bottom:float}` لكل صف فعلي؛ جسم الجدول حدوده من آخر صف أصلي، دون التمدد حتى `page.height-60`. كل سجل عقار يحتفظ بهويته وترتيبه والصكوك كاملة.

- [x] اكتب حالات: صف عادي/صف بصكين/صف بوصف عربي طويل/صف عادي؛ الأعداد11و13و14؛ سبب عدم بيع متعدد الأسطر. أكد تطابق قائمة IDs قبل وبعد التخطيط، وترتيبها وعدم تكرارها.
- [x] أكد فشل قاعدة «لا صفحة جدول بلا سجل، ولا صف يملأ صفحة كاملة بينما الارتفاع المطلوب أقل» على العينة الحالية للحرم.
- [x] احسب ارتفاع كل خلية بالنص والخط النهائي، وارتفاع الصف=maxالخلايا+padding الأصلي. املأ جسم الصفحة بالصفوف المتتالية، وكرر العناوين عند امتلائه فقط؛ لا تقسيم صكّي عقار واحد بين صفحتين. السجل الذي يتجاوز الجسم كله ينتج خطأ واضحًا، لا كتابة فوق التذييل.
- [x] عدّل رسم خطوط وخلفيات الصفوف فيArtwork ليطابق `table_layouts` مع ألوان المرجع، ولا يترك شبكة قديمة متداخلة تحت صف ممدود.
- [x] تحقق: اختبارات Unit المحددة تمر، ومراجعة R-1 لا تنتج3–5 فارغة ولا صفحة كاملة لصف عقار قصير. عدد الصفحات النهائي مشتق من قياس الصفوف، لا ثابت7/21. راجع وcommit.

### Task 5: عقد بيانات القنوات والمرفقات والحفظ

**Files:** إضافة `config/auction-report-inputs.php` و`database/migrations/tenant/2026_10_28_000003_add_report_metadata_to_auction_attachments.php`؛ تعديل `AuctionAttachment.php`، `AuctionReportInputService.php`، `AuctionReportDataBuilder.php`، وFormRequests الحالية. Test جديد: `tests/Feature/Tenants/Auctions/AuctionReportInputContractTest.php`.

**Interfaces:**

- القنوات: `x,tiktok,snapchat,youtube,whatsapp,google,other`. الأقسام الحالية تبقى كما هي: `brochure,platform,video,x,whatsapp,social,board_before,board_after,brokers,marketing_evidence,design_asset`.
- إضافة JSON nullable باسم `report_metadata` للمرفق: `{channel,kind,display_name,link_role,fit,focal_x,focal_y}`. النوع `document|screenshot|photo|artwork`، والـfit هو `cover|contain`، والتركيز من0 إلى100. تبقى أعمدة section/property_id/display_order الحالية دون نقلها إلى JSON.
- بجانب `marketing_evidence[]` يأتي `marketing_evidence_meta[index]`؛ تعديل مرفق موجود عبر `report_attachments[id]` بعد إثبات تبعيته للمزاد والـtenant. يبقى file_name اسم الملف، وdisplay_name اسم الوسيط أو وصف الدليل.
- Snapshot المرفق: `id,section,channel,kind,display_name,link_role,fit,focal_x,focal_y,property_id,display_order,mime,uri`. ترتيب الإدخال لا يحدد معنى القناة.

- [x] اكتب اختبارات حفظ/استعادة metadata، التعديل دون إعادة رفع، ملكية المرفق، قناة أو قسم غير مسموح، focal خارج المدى، وبيانات قديمة metadata=null. بعد اجتياز حارس Task1، شغّل الاختبار المحدد وتأكد من فشله للأسباب المقصودة.
- [x] أضف migration إضافية غير هدّامة وcast array. ضع التحقق فيFormRequests والسياسة الحالية؛ لا صلاحيات جديدة خارج الطلب.
- [x] وسّع الحفظ والاستعادة والـsnapshot. طبّع أسماء القنوات القديمة المعروفة عند حد الإدخال فقط؛ الملف الغامض يبقى دليلًا عامًا يحتاج تصنيفًا، ولا يُرسل تلقائيًا لـX. لا تخمين من filename أوhash داخل الإنتاج.
- [x] تحقق عبر `php artisan test tests/Feature/Tenants/Auctions/AuctionReportInputContractTest.php` على البيئة المعزولة: حفظ واستعادة صحيحان، رفض مرفق أجنبي، وتوافق محافظ مع البيانات القديمة. راجع ثم commit محدود؛ لا تطبيق migration على test المشترك ضمنيًا.

### Task 6: ربط الحملات واستمرارات الأدلة

**Files:** `AuctionReportPaginator.php`، `HaramReportData.php`، `AsirReportData.php`، bindings للتصميمين، وصفحاتSVG للامتدادات اللازمة داخلهما. Tests الحالية: Paginator/Data/HaramReportData/Renderer.

**Interfaces:** يبقى `paginate(array $snapshot,string $type): array`، والصفحة `page_type,payload,page_number`. الأنواع الجديدة المقترحة: `haram-marketing-evidence-continuation` و`haram-board-after-continuation` و`haram-unsold-continuation`. هندستها مشتقة من القسم الأصلي، لا هوية جديدة. payload يحدد القناة والمرفقات الفعلية والخانات المشغولة.

- [x] اكتب اختبارًا يعيد ترتيب حملات الحرم مع ثبات Snapchat=220,000 وTikTok=554,000 وX=573,000 في التصميمين. لعسير: X=1,547 وTikTok=1,000,006 وSnapchat=78,000. يجب أن يفشل ربط البطاقات الموضعي الحالي.
- [x] اكتب حالات أدلة0/السعة/السعة+1، وأربع قنوات، وbefore14/after13، و3مباع/10غير مباع. أكد ظهور كل attachmentID/document_page مرة واحدة داخل قسمه، والحفاظ على الصورة14 دون ستة شعارات تركيب على صفحتها.
- [x] أوقف تجميع كل أقسام التسويق في قائمة واحدة بالمخرجات. اربط العنوان والرقم والدليل والرابط كوحدة؛ الصفحة الأولى تحافظ على المرجع عند تطابق البيانات، والامتدادات تعرض أدلة القناة فقط دون إحصاءات منتهية أو فارغة.
- [x] افصل امتدادات before/after؛ امتداد after لا يرسم before. امتداد غير المباع لا يعيد جدول مباع فارغ. slot غير مخصص لا يرسم شعارًا؛ القسم الأساسي الخالي يحتفظ بعنوانه ورسالة «لا توجد مرفقات مضافة» مرة واحدة.
- [x] تحقق: اختبارات Unit المحددة تمر، ومراجعة O-2 لا تضع TikTok/YouTube تحت X ولا تعيد أقسامًا انتهت بياناتها. IDs وترتيب الأدلة محفوظان؛ راجع ثم commit.

### Task 7: سد فجوة بيانات التقرير في الـwizard

**Files:** FormRequests الحالية، `AuctionReportInputService.php`، `AuctionReportDataBuilder.php`، binders، `step4-settings.blade.php`، `step5-review.blade.php`، `resources/assets/js/tenant/auctions/create.js`، وملف الترجمة العربي الحالي. Tests: InputContract وWorkflow.

**Interfaces:**

- `property_results[id].board_count`: integer≥0؛ `board_dimensions`: نص≤100. يحفظان في board_data.count/dimensions مع location/notes، دون مسح حقول قديمة عند تعديل جزئي.
- `marketing.introduction` و`marketing.results_narrative`: نص≤10000. الأول للمقدمة، والثاني لنتائج المخرجات؛ description القديم fallback فقط.
- `marketing.contacts.{phone,unified_phone,social}`: نصوص متحقق منها، دون هواتف مرجعية افتراضية. الهاتف string للحفاظ على الصفر الأول.
- `marketing.links[].role=brochure|video|marketing_evidence|boards|platform|other` بجانب label/url؛ لا استنتاج للدور من التسمية العربية.
- `campaigns[].platform` قناة canonical؛ المقاييس integer≥0 nullable: `reach,impressions,engagements,clicks,views,posts,subscribers,messages,delivered,failed,pending,total,profile_views`. `watch_hours` numeric≥0؛ نسب التغير numeric تقبل السالب. لا تحويل views إلى impressions ضمنيًا. `marketing.whatsapp_date` تاريخ.
- حالة المنصة من حالة المزاد الفعلية، ومدتها من وقت البداية والنهاية والـtimezone المعتمد. لا حالة مستقلة ثانية تتعارض مع الأصل، ولا تحويل المجهول إلى «منتهي».

- [x] اكتب فشلًا لمسار create→reload→edit→save: كل الحقول محفوظة،0 مختلف عنnull، الهاتف يحتفظ بصفره، وboard_data القديم لا يُمحى. أضف تعديلًا دون رفع صورة جديدة، واستعادة draft، وقيمًا غير صالحة.
- [x] أضف الحقول بالمكونات المشتركة داخل خطوة الإعدادات الحالية؛ create/edit بنفس البنية، draft فيcreate فقط، وFormRequest هو مصدر القواعد الوحيد. JavaScript يضيف صفوفUI بعقد التحقق المشترك لا validator موازٍ.
- [x] وسّع الحفظ والاستعادة والربط. اسم الوسيط منdisplay_name وليسfilename. لا نص يؤكد إرسال رسائل أو توزيع كتيبات بلا بيانات تنفيذ؛ النص العام يبقى معرفًا كمنهجية، وليس إثبات إنجاز.
- [x] تحقق: اختبارات InputContract وWorkflow تمر علىCI المعزول، ثم `npm run build` ينجح. اختبر create/edit/draft فعليًا وتتبع كل حقل إلى snapshot ثم PDF، لا نجاح الحفظ فقط. راجع ثم commit.

### Task 8: احتواء الأدلة وقص الصور والخلفية

**Files:** `AuctionReportArtwork.php`، `AuctionReportMedia.php`، frames/bindings وSVG التصميمين. Tests: Artwork وMedia.

**Interfaces:** يبقى `prepare(array $snapshot): array` مع حفظ metadata لكل صفحةPDF. defaults: document/screenshot→contain؛ photo/artwork→cover في إطار الصورة؛ report_image→cover، مع focal_x/y. document_page يبدأ من1.

- [x] اكتب صورًا اختبارية بعلامات أركان للقطتين346×1107 و1920×1040، وPDF من3صفحات. أثبت اختفاء أركان الصور عند قص cover الحالي. استخدم صورًا اصطناعية غير حساسة، لا بيانات مستخدم داخل tests.
- [x] طبق fit حسب نوع المرفق. احتفظ بقناع إطار التصميم، لا silhouette خاص بمشهد قديم. أصلح تركيب غلافR-2 لإزالة الشريط/الحافة المزدوجة؛ الصورة الجديدة تتبع نفس الإطار ونقطة التركيز دون إعادة رسم skyline قديم فوقها.
- [x] ميّز المرفق المتوقع غير المقروء→شعار بديل، والخانة غير المشغولة→لاimage node. لا حذف أوdedup تلقائي للمرفقات بناءً علىhash؛ التكرار المحتمل يُذكر للمراجعة فقط.
- [x] تحقق: اختبارات Artwork وMedia تمر؛ كل الأركان مرئية فيcontain، صفحاتPDF الثلاث كاملة ومرتبة، ونسبة أبعاد اللوحات محفوظة. راجع هندسةPDF والصور المعروضة بصريًا، ثم commit.

### Task 9: إحصاءات ورسوم صادقة

**Files:** إضافة `app/Services/Tenants/Auctions/Reports/AuctionReportCharts.php`؛ تعديل AsirReportData وArtwork و`resources/auction-reports/asir/page-14.svg` وbindings المرتبطة بالفيديو والواتساب. إضافة `tests/Unit/Auctions/AuctionReportChartsTest.php`.

**Interfaces:** `AuctionReportCharts::whatsapp(array $metrics): ?string` يعيدSVG آمنًا داخل أبعاد وألوان إطار المرجع، أوnull عند نقص البيانات. لا تُستنتج watch_hours منviews، ولا سلسلة زمنية من رقم إجمالي واحد.

- [x] اكتب فشلًا: delivered=70/failed=20/pending=10 ينتج نسب70%/20%/10%؛ null لا يرسمpie الأصلي؛ مجموع صفر ينتج حالة فارغة لاNaN؛ اختلاف مجموع الحالات عنtotal يرفض بوضوح ولا يرسم معلومة متعارضة.
- [x] أزل شكل الإحصاءات الثابت الذي يوحي بنتيجة حقيقية، مع إبقاء الإطار والعنوان. صورة تحليلات حقيقية تعرضcontain مع مصدرها؛ وإلا تظهر «لا تتوفر إحصاءات»، لا رسم مزاد المرجع.
- [x] تحقق: Charts/Data tests تمر، والجدول والرسم متفقان. لا تُكتب أرقام جديدة فوق دليل صورة ذي أرقام مختلفة. راجع ثم commit.

### Task 10: الروابط وهندسة الطباعة

**Files:** Artwork، `resources/views/tenant/auctions/reports/layouts/document.blade.php`، `config/auction-report-templates.php`، bindings. Tests: Artwork وPdfText.

**Interfaces:** content يضيف `links[role]={url,box}` من أدوارTask7. يمكن استخدام HTML anchors فوقSVG إن لم يحفظ Chromium روابطSVG؛ hitbox بالنقاط قبلscale، دون تغيير النص أو موقعه.

- [x] اكتب فشلًا لتحويل رابطX وTikTok والكتيب والمنصة والهاتف وWhatsApp إلىPDFLinkAnnotation بالوجهة الصحيحة. null لا يولد رابطًا، وjavascript/data/file مرفوضة. الهاتف يتحولtel لاURL بلاscheme.
- [x] طبق الروابط فوق عناصرها فقط، معIDs فريدة لكل صفحة؛ لا روابط متبقية من المصدر ولاhitbox يغطي صفحة كاملة.
- [x] اضبط هندسة الصفحة على الأصل: outputs595.5×842.2499787pt، report595.276×841.89pt، وإحداثياتSVG تبقىviewBox معscale واحد. تحقق بانحرافMediaBox≤0.5pt لقيود تقريبChromium، وسجل الفرق بدل الادعاء بأنه صفر.
- [x] تحقق: الروابط المطلوبة موجودة بوجهاتها وحدودها الصحيحة في كل مستند قبول، ولا قص للحواف أو انزياح متراكم في الجدول والتذييل. راجع ثم commit.

### Task 11: حزمة القبول والمقارنة الحقيقية

**Files:** fixtures وmanifest منTask1؛ `scripts/auction-reports/{compare-pages.py,assert-pdf-contract.ps1,render-reference-comparison.ps1}`؛ إضافة `docs/auction-reports/acceptance-results.md`.

**Interfaces:** نتيجةQA لكل مستند: `source_hash,snapshot_hash,page_families,property_ids,media_ids,expected_values,link_destinations,unicode_errors,geometry_checks,visual_regions`. الأجزاء الثابتة والمحتوى المتغير لهما نتائج مستقلة؛ لا اختزال فيstatic score.

- [x] أنجز جرد وسائط المزادين: المصدر/النوع/hash/دليل حقيقي أم ديكور/ناقص أم موجود. راجع أنواع العقارات والنصوص ومقاسات اللوحات مقابل المرجع، دون تعديلenum عام فقط لإرضاء عينة.
- [x] اكتب dry-run محدودًا لأي تعديل بيانات مطلوب فيtest، مع القيم القديمة والجديدة وخطوات الرجوع. التطبيق يحتاج إذنًا محددًا؛ يمكن إكمالrender بـfixtures ملفية دون DB. لا تسمِّ المقارنة «نفس البيانات» إذا كانت الكتيبات أو العقود غير متاحة.
- [x] وسّع المقارنة إلى التركيبات الأربع وبصمتي الأصلين الحاليين. في نفس البيانات، النص والصور والقيم لا تُحجب بالmasks؛ في بيانات مختلفة، يقارن الهيكل والقيم ضدsnapshot، ويُذكر سبب استبعاد كل منطقة متغيرة.
- [x] اكتب فشلًا يرفض: تراكب الأرقام،NUL، قناة خاطئة، نقصًا غير معلن، غلافًا مكررًا، صفحة جدول فارغة، رقم10 مقسمًا، عقارًا مفقودًا/مكررًا. لا ثابت7/21 عند وجود محتوى إضافي؛ expected families مشتقة منmanifest وسعات الأقسام.
- [ ] Render150dpi لكل الصفحات و300dpi للأرقام والجداول والحواف؛ احتفظ بالoverlay/diff ومراجعة بشرية. الهندسة: انحراف مواضع الإطارات والخطوط≤1pt؛ الألوان المسطحة≤3RGB بعد تثبيتprofile والرندر. لا تمرير لتراكب أوقص تحت بند الخطوط المؤجلة.
- [x] تحقق: الحرم4مباع/7غير مباع/5,670,000؛ عسير3/10/4,026,000؛ القنوات متفقة بين الوثيقتين؛ كل مرفق في قسمه. سجل المناطق غير المقاسة ونواقص المصدر صراحة، ولاall-pass معknown failures. راجع ثم commit.

### Task 12: قبول الاستخراج الفعلي والتلقائي

**Files:** `scripts/auction-reports/verify-queued-reports.php`؛ Feature tests الحالية Workflow/Generation/Isolation. تعديل Job أوService فقط إن كشف اختبار محدد خللًا، دون إعادة هيكلة استباقية.

**Interfaces:** receipt لكل مزاد: `generation_id,revision_token,snapshot_hash,outputs_path,report_path`. collect يجمع الملفين لكل مزاد من نفس الإصدار، لاoutputs للحرم وreport لعسير فقط.

- [x] اكتب فشلًا يثبت أنcollect الحالي لا يجمع4ملفات. أضف اختبارات create/update يولدان زوجPDF بنفسsnapshot؛ إصدار أقدم لا يحل محل الأحدث؛ فشل ملف واحد لا ينشرready جزئية.
- [x] اجعل سكريبت القبول يقبل tenantID مخصصًا ومحروسًا، دونhard-code لـtest. تحقق من حارسTask1؛ worker خاص ببيئة القبول فقط. تشغيله علىtest المشترك يحتاج إذنًا محددًا بعد ضبط بيانات القبول.
- [x] نفذ طلب توليد يدوي، ثم حفظ تعديل فعلي داخلCI المعزول لتفعيلauto export. اجمع4ملفات لكل مسار، وافحصready والخصوصية والتنزيل والمحتوى والروابط؛ وجودheader يبدأ بـ%PDF- غير كافٍ.
- [x] اختبرtenant آخر، مستخدمًا بلاصلاحية،failed/retry،stale generation، وصورة تالفة. rollback لبيئة الاختبار المعزولة فقط، دون حذف ملفات المستخدم أو جميع إصداراته.
- [x] بعد الحارس فقط: شغّل `php artisan test tests/Unit/Auctions --filter=AuctionReport` وHaramReportDataTest، ثم Feature tests المحددة. شغّل `npm run build` عند تعديلJS/CSS، وراجعdiff واقتصر على ملفات الخطة.
- [ ] افتح كلPDF نهائي وحدّثacceptance-results بالبصمات ونتائج اليدوي/التلقائي، والنواقص والخطوط المؤجلة. لا إعلان إغلاق قبل اجتياز البوابة أدناه.

## بوابة الإغلاق النهائية

| المطلوب | دليل القبول |
|---|---|
| الأربع تركيبات سليمة | الحرمoutputs+report وعسيرoutputs+report، من نفس بيانات كل مزاد |
| لا تراكب أو تقسيم معنى | أرقام573,000 و1,000,006 و10 كاملة؛ غلاف واحد؛ تاريخ وشهر ومدير كاملون؛ العناوين ديناميكية بأكبر حجم مناسب ولا تتجاوز سطرين فيPDF وPNG |
| لا فقد أو ازدواج | تطابق مجموعاتIDs للعقارات والمرفقات وكل صفحاتPDF المضمنة |
| امتدادات مبررة | سبب واضح لكل صفحة إضافية، ولا scalar/جدول فارغ أوشبكةfallback وهمية |
| مصدر القيم واضح | القنوات والإجماليات والرسوم متفقة معsnapshot لا بيانات مرجع قديم |
| الأدلة مكتملة أو النقص معلن | لا اعتبارfallback دليلًا مستعادًا، ولا نقل وسائط من مزاد آخر |
| الروابط والنص صالحان | Annotations صحيحة، ونص عربي قابل للبحث والنسخ دونNUL |
| التصميم محافظ على المرجع | ألوان وهندسة وقص وخلفية وفق المعايير، والخطوط الثانوية المؤجلة موضحة |
| اليدوي والتلقائي يعملان | receipt ونتائجQA لزوج الملفات بنفسrevision لكل مزاد، ونجاحstale/retry/isolation |
| بيانات المستخدم آمنة | لا تغيير لـtest المشترك إلا بعملية محددة مأذون بها وقابلة للرجوع |

## مصفوفة تغطية سجل المراجعة

F01→2؛ F02→3؛ F03→4؛ F04→6؛ F05→5/6؛ F06→6؛ F07→8/11؛ F08→8؛ F09→7/11؛ F10→7؛ F11→7؛ F12→7؛ F13→9؛ F14→5/7؛ F15→10؛ F16→2؛ F17→8/10؛ F18→1/11/12.

نقص أصل كتيب أوعقد ليس عيبrenderer محلولًا. إذا جُهز الكود وبقي المصدر غير متاح، يُسلم كمانع قبول للمطابقة الكاملة مع طلب الملف المحدد، لا كنجاح كامل ولا بإحصاءات مصطنعة.

## حالة التنفيذ — 29 أكتوبر 2026

نُفذت المهام الوظيفية واختباراتها، مع105 اختبارات/1215 تحققًا، و4 تنزيلات يدوية و4 تلقائية داخل CI. تفاصيل الأدلة والقرارات والمخاطر في [نتائج القبول](../../auction-reports/acceptance-results.md). مراجعة مستقلة واحدة كشفت6 بنود Important وأُصلحت باختبارات RED→GREEN؛ لا إعادة مراجعة دورية ولا commit جماعي للعمل المتزامن.

المربعات المؤشرة تعني تنفيذ الخطوة والتحقق الوظيفي؛ لا تعني تنفيذ تعليمات commit القديمة، أو تطبيق migration على قاعدة المستخدم، أو اعتماد المطابقة البصرية الكاملة. أُنتجت مقارنة150dpi وتكبيرات300dpi، لكن بوابة1pt/3RGB الشاملة ونواقص المصدر والخطان المؤجلان لا تزال مفتوحة. لذلك لا يُعلن إغلاق بوابة المطابقة النهائية.
