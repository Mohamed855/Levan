# قوالب الهيئة العامة للعقار: طلب الإقامة والإقفال

## النطاق

قالبان مستقلان يستقبلان البيانات دون الاعتماد على قاعدة البيانات أو أي إدارة في النظام:

- `establishment`: صفحات المنشأة، المزاد، العقار والإفصاحات، بيان العقارات الأفقي، الوكلاء، والإقرار. النسخة الفارغة ٦ صفحات.
- `closing`: بيان إقفال أفقي من صفحة واحدة، يتكرر عند الحاجة.

الكتابة والجداول والخانات عناصر HTML/Blade فعلية. الأصول المعاد استخدامها هي شعار الهيئة وحده، والمسارات الثلاثة للزخرفة الباهتة. لا تُستخدم صفحات PDF الأصلية كخلفيات، ولا تُستورد جداولها أو عناوينها كصور أو SVG، ولا تُعبأ حقول AcroForm.

لم تُضف شاشات أو مسارات أو جداول قواعد بيانات، ولم يُربط القالبان بإدارة الإقفال أو إدارة طلب الإقامة. مربعات التوقيع والختم والمصادقة تظل فارغة لتستكمل عبر إجراء الاعتماد المناسب.

## الاستخدام

```php
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryForms;

$forms = app(AuctionRegulatoryForms::class);
$html = $forms->html($data, 'establishment');
$forms->pdf($data, 'closing', $absoluteOutputPath);
```

يجب أن يكون مجلد الإخراج موجودًا. فشل تحميل الأصول أو تجاوز المساحة المتاحة يمنع تصدير PDF. البيانات تمر إلى Blade كنصوص مهربة؛ لا يقبل العقد HTML أو صورًا أو روابط أصول خارجية. رابط البث يُطبع نصًا ولا يُحمّل داخل محرك المستند.

لإنشاء نسخ فارغة وتجريبية وصفحات استكمال:

```console
php scripts/auction-regulatory-forms/render.php --proofs
```

لتوليد مستند ببياناتك:

```console
php scripts/auction-regulatory-forms/render.php --type=establishment --data=resources/auction-regulatory-forms/example.json
php scripts/auction-regulatory-forms/render.php --type=closing --data=resources/auction-regulatory-forms/example.json
```

خيارات إضافية: `--html-only` و`--output=مسار`. المجلد الافتراضي `output/pdf/auction-regulatory-forms`. ملفات HTML مستقلة وتحتوي على الخطوط والزخرفة ومحرك تقسيم الصفحات. قيمة `initial_pages` في سجل الأمر عدد الصفحات قبل قياس الصفوف؛ قد يزداد العدد بعد قياس النصوص الطويلة.

## عقد البيانات

المرجع الكامل ذو القيم التجريبية هو `resources/auction-regulatory-forms/example.json`. جميع بياناته أمثلة وليست بيانات مزاد حقيقي. القيم النصية اختيارية لتمكين إخراج النموذج الفارغ. يتحقق القالب من أنواع القيم والاختيارات؛ تتحمل الإدارات لاحقًا التحقق من اكتمال البيانات وصحتها التنظيمية.

| المجموعة | المفاتيح |
|---|---|
| رأس الطلب | `submission_date` |
| `organization` | `name`, `commercial_registration`, `unified_number`, `fal_license` |
| `manager` و`auctioneer` | `name`, `identity_number`, `mobile`, `email` |
| `auction` | `name`, `properties_count`, `units_count`, `method`, `platform`, `city`, `venue`, `broadcast_url`, `start_date`, `end_date`, `start_time`, `award_period`, `disposition` |
| `funds` | `receipt_method`, `receipt_other`, `safekeeping`, `receipt_days`, `refund_method`, `refund_other`, `refund_days`, `entry_type` |
| `property` لصفحة تفاصيل العقار | `owner_name`, `deed_number`, `agent_name`, `agency_number`, `area`, `age`, `type`, `usage`, `usage_other`, `status`, `status_other`, `opening_value`, `entry_amount`, `ownership_share` |
| `disclosures` | `disposition_rights` قائمة، `disposition_right_other`، والحقول أدناه |
| `declaration` | `representative_name` |
| `closing` | `date`, `approval_number` |

عند غياب `property` مع وجود عقار واحد فقط، تُستخدم بياناته في صفحة التفاصيل. مع تعدد العقارات لا يُختار أول عقار تلقائيًا لصفحة التفاصيل. لا يستنتج القالب إفصاح «لا يوجد» من غياب البيانات.

الإفصاحات الخمسة: `real_rights`, `dispute`, `value_information`, `off_plan`, `contribution`. لكل منها قيمة `yes` أو `no` أو فارغة، وحقل تفسير باسمه متبوعًا بـ`_details`.

`properties` قائمة صفوف؛ مفاتيح كل صف:

```text
owner_name, deed_number, contract_number, area, type, usage, age,
city_district, disclosure, opening_value, entry_amount, estimated_value,
result, awardee, award_value, unawarded_reason
```

`agents` قائمة صفوف؛ مفاتيح كل صف: `owner_name`, `deed_number`, `name`, `agency_number`.

مفاتيح الاختيارات:

| المفتاح | القيم |
|---|---|
| `auction.method` | `electronic`, `in_person`, `hybrid` |
| `auction.disposition` | `sale`, `lease` |
| استلام واسترجاع الأموال | `transfer`, `cheque`, `other` |
| `funds.entry_type` | `earnest`, `advance`, `refundable`, `none` |
| `property.status` | `clear`, `seized`, `mortgaged`, `endowed`, `other` |
| `disclosures.disposition_rights` | قائمة من `none`, `preemption`, `session_option`, `condition_option`, `earnest_sale`, `other`؛ لا تُجمع `none` مع حق آخر |
| `properties[].result` | `awarded`, `not_awarded` أو فارغة |

عند الترسية يُطبع الاسم والقيمة ويُخفى سبب عدم الترسية، وعند عدمها يحدث العكس. تُمرّر القيمة المالية كسلسلة عشرية غير سالبة من منزلتين بحد أقصى، مثل `"1350000.25"`. لا يستخدم التنسيق الحساب بالفاصلة العائمة. تُمرّر أرقام الوثائق والرخص والهويات كنصوص للحفاظ على الأصفار البادئة. التواريخ نصوص عرض جاهزة؛ لا يُجرى تحويل ضمني بين الهجري والميلادي.

السعات الأصلية للصفوف القصيرة: ١٤ عقارًا في صفحة بيان الإقامة، ٤ وكلاء، و١٧ نتيجة في الإقفال. يقيس المتصفح الصفوف بالخطوط الفعلية ويضيف صفحات عند زيادة الارتفاع، مع الاحتفاظ بالرؤوس والتوقيعات. القيمة التي لا يمكن احتواؤها في خانة ثابتة أو صف في صفحة كاملة تُرفض بدل قصها.

## التحقق وحدود المطابقة

```console
php vendor/phpunit/phpunit/phpunit tests/Unit/Auctions/AuctionRegulatoryFormsTest.php
node --test tests/Browser/AuctionRegulatoryForms.test.mjs
```

يمكن ضبط متغير `PHP_BINARY` لاختبارات المتصفح إذا لم يكن PHP على PATH. اختبارات المتصفح تشغّل Chromium الحقيقي وتختبر الاستكمال، وتجاوز خانات المنشأة والعقار، ورفض تصدير PDF غير صالح.

المطابقة البصرية لا تزال تحتاج اعتمادًا: الخطوط الأصلية داخل المراجع جزئية. استُعيدت أشكال REGA المتاحة من الملفات وأضيفت قواعد وصل الحروف، ويُستخدم TheSans الكامل للبيانات والنصوص الصغيرة، وIBM Plex للعناوين المناسبة. هذا لا يثبت مطابقة الخطوط الأصلية الكاملة أو كل المسافات بنسبة ١٠٠٪. أُزيلت خرائط المحارف غير العربية الفارغة لتفعيل الخط الاحتياطي للأرقام وعلامات الترقيم. مصدر خط IBM وترخيصه محفوظان مع الأصول، وبصمات المرجعين وتغطية الخطوط في `provenance.json`.

ملف `verification.json` وصور `comparison/` الناتجة من أداة الفحص يسجلان الفروق الفعلية. معيار الاستلام النهائي للمطابقة الحرفية هو اعتماد المقارنة، وتوفير الخطوط الأصلية الكاملة إذا بقيت فروق طباعية. الملف الحالي يُسلّم كقالب فعلي جاهز للبيانات، ولا تُوصف المطابقة البصرية بأنها ١٠٠٪ قبل اجتياز ذلك الاعتماد.
