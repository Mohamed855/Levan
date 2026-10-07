# خطة تنفيذ توحيد النظام وإصلاحاته

> مرتبطة بتقرير: [`system-audit-report-ar.md`](system-audit-report-ar.md)  
> هذه الخطة لا تغيّر سلوكاً business ولا تحذف كوداً أو بيانات قبل إثبات الاستخدام والموافقة.  
> قاعدة العمل: لا تبدأ مرحلة قبل اجتياز بوابة التحقق للمرحلة السابقة.

## 1. الهدف وحدود التنفيذ

### الهدف

الوصول إلى نظام موحّد في Website وSuper Admin وTenant من حيث:

1. الـUI، حالات hover/focus، والمسافات والـcomponents.
2. الـforms والتحقق الأمامي المرتبط حصراً بـLaravel FormRequests.
3. أزرار الـwizard والـdrafts ورسائل الخطأ والنجاح.
4. DataTables، الفلاتر، حالات النشاط، وعمليات AJAX.
5. route lifecycle: active، compatibility، deprecated، removed.
6. JSON/API contracts والأمان والحدود بين central وtenant.

### خارج النطاق حتى يصدر قرار مستقل

- تغيير قواعد التسعير أو احتساب الضريبة.
- حذف tables أو بيانات production.
- حذف legacy files قبل مرحلة deprecation والموافقة الصريحة.
- تغيير مزود الدفع أو التكامل الخارجي.
- إعادة تصميم بصري مختلفة عن الهوية الحالية.

## 2. قواعد إلزامية طوال التنفيذ

1. `FormRequest::rules/messages/attributes` هو مصدر الحقيقة الوحيد للتحقق.
2. كل control قابل لإرسال البيانات يستخدم العقد التالي:
   - wrapper: `data-form-field` و`data-field-name`.
   - control: `data-form-control`.
   - error output قابل للوصول عبر `data-form-error` و`aria-describedby`.
3. لا يضاف rule business مكرر في JavaScript؛ JavaScript يعرض حالة التحقق من endpoint المشترك فقط.
4. كل wizard يبدأ وزر Next معطّل، ولا يُفعّل إلا بعد صلاحية حقول الخطوة الحالية.
5. لا placeholder يتحرك عند focus، ولا `translateX`/`translateY` كأثر hover في المكوّنات العامة إلا باستثناء موثق ومقبول.
6. `x-filter.select` يظل للفلاتر فقط ولا يستخدم كحقل form مرسل.
7. كل جدول يغيّر حالة نشاط يستخدم `js-table-status-toggle`، ويكون عمود `الحالة` قبل `الإجراءات` مباشرة.
8. كل قيمة identifier أو permission أو status مخزنة في قاعدة البيانات تبقى إنجليزية حسب `AGENTS.md`.
9. لا يتم حذف route أو class أو view إلا بعد فحص الاستدعاءات في Blade وJS وPHP وJobs وCommands وAPI consumers.
10. بعد أي تعديل في `.js` أو `.scss` أو `.css` يُشغّل `npm run build`.

## 3. تعريفات الحالة للـroutes والملفات

| الحالة | التعريف | التصرف |
|---|---|---|
| Active | route مسجل وله UI أو consumer موثق | يبقى ويُختبر |
| Compatibility | route قديم ما زال يستهلكه client معروف | يبقى مؤقتاً مع توثيق موعد الإزالة |
| Deprecated | route/file لا يُفترض أن يبدأ استخدامه الجديد | لا نضيف له وظائف؛ نضع خطة استبدال |
| Inactive | route معلّق أو غير مسجل | لا يُحذف قبل فحص المراجع والموافقة |
| Orphan candidate | class/view/file بلا route أو مرجع ثابت | يدخل قائمة deprecation فقط |

## 4. بوابة البداية المشتركة — المرحلة 0

> **الحالة الحالية (5 أكتوبر 2026):** اكتمل الـbaseline الثابت وقائمة الاختبار اليدوية في
> [`baselines/phase-0-baseline-2026-10-05.md`](baselines/phase-0-baseline-2026-10-05.md) و
> [`baselines/manual-smoke-checklist.md`](baselines/manual-smoke-checklist.md).
> التحقق التشغيلي مؤجل لحين توفير MySQL المحلي؛ تفاصيل العائق موثقة في الـbaseline.

### التنفيذ

1. إنشاء inventory مولد من الكود لكل routes في Website وAdmin وTenant وAPI، متضمناً method، middleware، controller/action، name، ونطاق قاعدة البيانات.
2. إنشاء inventory للـviews وDataTables وFormRequests وJS/CSS المرتبط بكل صفحة.
3. تسجيل baseline قبل التعديل:
   - عدد الـroutes لكل نطاق.
   - عدد forms التي تحمل `data-form-validate`.
   - عدد ملفات validation المنفصلة.
   - عدد controls التي لا تطابق عقد form المشترك.
   - كل routes التي تقبل GET وPOST للـmutation.
4. تشغيل `php artisan route:list` وtest suite عند توفر قاعدة التطوير، وتسجيل أي عائق بيئي منفصلاً عن أخطاء التطبيق.
5. إنشاء قائمة اختبار يدوية لكل flow حساس: checkout، إنشاء tenant، login/2FA، wizard draft، import/export، التحصيل، domain payment.

### بوابة القبول

- لا توجد route أو صفحة ضمن خطة التنفيذ بلا owner واضح.
- يوجد ملف baseline قابل للمقارنة بعد كل مرحلة.
- تم تمييز ما هو مؤكد من الكود وما يحتاج اختبار runtime.

## 5. مرحلة الأمان والعزل — المرحلة 1

> **الحالة الحالية (6 أكتوبر 2026):** **مكتملة**. تم تأمين API الترقية بـHMAC وحماية replay مع اختبارات آلية ناجحة، تحويل كافة mutations المثبتة إلى POST-only، وعزل قوالب الأخطاء؛ التفاصيل وسجل بوابة القبول في
> [`baselines/phase-1-security-register.md`](baselines/phase-1-security-register.md).

### 1.1 API upgrade

1. حصر كل المستهلكين الفعليين لـ`POST /api/tenants/{tenantId}/upgrade`.
2. استبدال secret header البسيط بآلية مصادق عليها ومحددة القرار: token مقيّد الصلاحية أو HMAC موقّع.
3. إضافة rate limit، audit log، منع تشغيلين متوازيين لنفس Tenant، وعدم إعادة exception داخلي للعميل.
4. تشغيل migration عبر Job موثق الحالة إذا كان زمن العملية غير قصير.

### 1.2 عمليات تغير الحالة

1. جرد كل `Route::match(['get', 'post'])` أو GET قد يغير بيانات، خصوصاً domain/payment/transfer.
2. فصل صفحة العرض GET عن endpoint mutation POST/PUT/DELETE.
3. تطبيق CSRF، authorization، idempotency في callbacks، ورسائل JSON موحدة.

### 1.3 أخطاء HTTP

1. جعل Error Renderer يختار `site.errors` أو `admin.errors` أو `tenants.errors` حسب route/domain.
2. الحفاظ على JSON contract للـAJAX/API وعدم كشف exception في production.

### بوابة القبول

- لا توجد mutation موثقة تقبل GET.
- endpoint الترقية له authentication/audit/rate-limit واختبار رفض للطلب غير المصرح.
- أخطاء Website وAdmin لا تعرض قالب Tenant.

## 6. مرحلة عقود الـUI الأساسية — المرحلة 2

> **الحالة الحالية (6 أكتوبر 2026):** **مكتملة**. تم تثبيت design tokens الموحدة للألوان والحلقات والانحناءات وحالات المكونات في `_variables.scss`، إلغاء حركات placeholder/button translations في `_forms.scss` و`_buttons.scss`، توحيد حالات `.btn.is-loading` و`.spinner` و`.empty-state` و`.card-footer` و`.page-header`، إنشاء المكون المشترك `<x-ui.empty-state>`، واجتياز بناء الأصول `npm run build`؛ التفاصيل في
> [`baselines/phase-2-ui-contracts-register.md`](baselines/phase-2-ui-contracts-register.md).

### التنفيذ

1. تدقيق SCSS/JS العام لإزالة placeholder movement وhover translations من النظام العام.
2. تثبيت design tokens للون والخطوط والحواف وfocus ring وحالات valid/invalid/disabled.
3. تثبيت سلوك واحد لـ:
   - button loading/disabled.
   - Toast/alert.
   - modal confirmation.
   - empty state.
   - page title/breadcrumb/card footer.
4. توحيد layout primitives المشتركة بين Admin وTenant بدون دمج هوية القائمة أو الصلاحيات.
5. إبقاء Website layout مستقلاً بصرياً، لكن استخراج العناصر المتكررة الآمنة مثل alerts وform feedback.

### بوابة القبول

- لا تظهر حركة placeholder أو button content في صفحات العينة المحددة.
- جميع الحالات الأساسية تمر عبر tokens/mixins/mكونات مشتركة لا CSS مكرر.
- لا يتغير DOM أو سلوك الصفحات غير المستهدفة بلا اختبار.

## 7. مرحلة محرك form-validation والمكوّنات — المرحلة 3

> **الحالة الحالية (6 أكتوبر 2026):** **مكتملة**. تم اعتماد `shared/form-validation.js` كمحرك موحد وإزالة القواعد البرمجية المكررة منه، مطابقة كافة مكوّنات الحقول لعقد الـ DOM، ربط نقطة التحقق `validate-step` بالـ FormRequest الحقيقي، وتوثيق دورة حياة الـ Wizard والمسودات؛ التفاصيل في
> [`baselines/phase-3-forms-validation-register.md`](baselines/phase-3-forms-validation-register.md).

### التنفيذ

1. اعتماد `shared/form-validation.js` كالمحرك الوحيد للـforms التي لها FormRequest صالح.
2. مراجعة مكونات `input/select/password/textarea/checkbox/file/image-upload` للتأكد من العقد الكامل، بما في ذلك Select2 وinput groups وarrays/files.
3. جعل endpoint التحقق يرفض بوضوح أي route ليس `store` أو`update` أو لا يحتوي FormRequest؛ لا fallback إلى rules داخل المتصفح.
4. توحيد response التحقق:
   - نجاح: `{ success: true }`.
   - فشل: `{ success: false, errors: { field: [message] } }` مع 422.
5. تثبيت سياسات wizard:
   - Next disabled on first render.
   - يعاد احتساب الحالة بعد restore draft وSelect2 وdynamic repeater.
   - draft لا يحفظ password/file binary؛ يحفظ metadata للملف عند الحاجة.
   - discard وrestore يظهران modal واضحاً ويمكن تجاهلهما.
6. كتابة اختبارات feature للـvalidate-step ولدورة draft: إنشاء، استعادة، تجاهل، إزالة، وصلاحيات المستخدم.

### بوابة القبول

- كل Control مشترك يضيف الحالة الصحيحة `is-invalid` و`is-valid` على control/wrapper.
- لا تتكرر رسالة الخطأ لنفس الحقل.
- لا يُفعّل Next قبل صحة الخطوة.
- كل wizard مستهدف يستعيد dynamic rows دون فقد بيانات.

## 8. مرحلة Website — المرحلة 4

> **الحالة الحالية (6 أكتوبر 2026):** **مكتملة**. تمت مزامنة قواعد التحقق في التجربة المجانية وإتمام الدفع مع الـ FormRequests الرسمية، حصر وإلغاء الاعتماد على سكربتات التحقق المنفصلة، توثيق قرارات الضرائب وإتاحة الباقات، واعتماد بطاقة الباقات الموحدة `<x-ui.package-card>` واجتياز بناء الأصول بنجاح؛ التفاصيل في
> [`baselines/phase-4-website-register.md`](baselines/phase-4-website-register.md).

### الترتيب

1. Landing/contact/trial.
2. Checkout وcoupon والدفع.
3. Header/footer والأقسام المتكررة.

### التنفيذ

1. تحويل contact وtrial وcheckout إلى controls مشتركة و`data-form-validate` إن كان لكل form FormRequest قابل للتحقق.
2. إزالة scripts validation المكررة بعد مقارنة جميع الحالات والتحقق server-side.
3. مراجعة `Plan::isAvailable()` أو القاعدة business المعتمدة عند عرض الباقات؛ لا يتغير السلوك حتى اعتماد قرار availability.
4. توثيق قرار الضريبة قبل تعديل checkout calculations.
5. الإبقاء على `x-ui.package-card` كالمصدر الوحيد لشكل بطاقة الباقة على الموقع وداخل إنشاء الباقات.

### بوابة القبول

- التواصل والتجربة والدفع يعرضون أخطاء server-side وfront-end في نفس المكان.
- لا يظهر plan غير متاح وفق القرار المعتمد.
- checkout يعالج card/bank/coupon/error/callback دون كسر redirect أو session.

## 9. مرحلة Super Admin — المرحلة 5

> **الحالة الحالية (6 أكتوبر 2026):** **مكتملة**. تم فصل `admin.mass-delete` في متحكم مركزي مستقل `AdminMassDeleteController` مع تقييد القائمة البيضاء والصلاحيات، توحيد نموذج وسكربت إنشاء الاشتراكات مع عقد الـ Wizard والـ Controls ومحرك التحقق المشترك، توحيد أزرار تبديل الحالة `js-table-status-toggle` ومطابقة ترتيب عمود `الحالة` قبل `الإجراءات` في كافة الـ DataTables، حماية مسارات الفواتير، واجتياز اختبارات العقد وبناء الواجهة بنجاح؛ التفاصيل في
> [`baselines/phase-5-admin-register.md`](baselines/phase-5-admin-register.md).

### ترتيب الإدارات

1. الباقات، الشركات، الاشتراكات، الفواتير، الكوبونات.
2. المستخدمون، الأدوار، الإصدارات، settings.
3. المحتوى، الدعم، الأرشيف، dashboard.

### لكل إدارة

1. ربط route/controller/FormRequest/view/DataTable/service والجداول المتأثرة في manifest.
2. استبدال controls اليدوية والـvalidation المحلي بالمكونات والمحرك المشتركين.
3. تحويل status controls إلى المكوّن المشترك حيث تنطبق قاعدة النشاط.
4. توحيد JSON responses وflash messages وdelete confirmations.
5. التحقق من permissions وroute-model binding وsoft-delete boundaries.
6. إنشاء/تحديث feature tests للـCRUD والحالات غير المصرح بها.

### معالجة خاصة

- توثيق `plans.reorder` كـcompatibility route أو إزالته بعد فحص كل consumers.
- فصل `admin.mass-delete` عن Tenant controller قبل أي توسع.
- نقل Transfer Requests إلى قائمة Inactive رسمية؛ لا يعاد تفعيله إلا بخطة مستقلة.

### بوابة القبول

- كل form نشط في الإدارة المستهدفة يمر عبر FormRequest والمكوّنات المشتركة.
- كل جدول حالة يطابق ترتيب الأعمدة ومكون toggle المشترك.
- كل action AJAX يملك success/error contract معروفاً واختبار authorization.

## 10. مرحلة Tenant — المرحلة 6

### تقسيم التنفيذ الإلزامي

لا تنفذ Tenant دفعة واحدة. تعمل الوحدات التالية بالترتيب، وكل وحدة تمر بالبوابة قبل التالية:

1. Authentication **(مكتمل)**، profile، users، teams، roles. **Branches ملغاة من المنتج لكل Tenant وتُزال مراجعها قبل استكمال هذه الوحدة.**
2. Settings وnotifications وactivity logs وarchive.
3. Companies، contracts، portfolios، tasks.
3.1. Assignments، properties، auctions، وأرشيفاتها **(تدخل من Stage 0R إلى 5R قبل اعتماد وحدة Tenant؛ ثم تُغلق في 6R باختبارات العزل ودورة حياة الملفات والعلاقات)**.
4. Customers: الأساس، الهواتف، fields، notes، comments، attachments، follows، payments، settlements، payment plans، SMS، update requests.
5. Customer import، bulk update، distribution، exports.
6. Financial collections، targets، collection process، reconciliation.
7. Communication، company letters، templates، logs.
8. Subscriptions، bills، licenses، tickets، versions.
9. Domains وCPanel وpayments/transfers، بعد إتمام مرحلة الأمان.
10. Reports وdashboards.

### لكل وحدة

1. مراجعة الـmiddleware والصلاحية والـtenant connection قبل تعديل الواجهة.
2. حصر كل FormRequest؛ أي mutation يستخدم `Request` عام يدخل backlog لتحويله إلى FormRequest.
3. توحيد controls، validation، wizard buttons، modal/JSON/status actions.
4. فحص uploads، downloads، queues، polling، cancellation، وsoft deletes.
5. اختبار isolation: Tenant A لا يستطيع رؤية أو تعديل بيانات Tenant B.
6. تحديث navigation فقط بعد إثبات routes والصلاحيات، مع إظهار العناصر حسب `@can`/الاشتراك/الترخيص.

### بوابة القبول لكل وحدة

- لا توجد form نشطة خارج العقد الموحد إلا باستثناء موثق.
- لا توجد rules business مكررة في JS.
- requests غير المصرح بها تعيد 403 ولا تعبر للبيانات.
- AJAX/queue/download flows تعمل في النجاح والفشل والإلغاء.

## 11. مرحلة DataTables والـresponses — المرحلة 7

### التنفيذ

1. اعتماد `x-data-table.page` و`x-data-table.filters` للحالات المتشابهة.
2. فصل form selects عن `x-filter.select` دائماً.
3. توحيد payloads:

| الحالة | الحد الأدنى للـresponse |
|---|---|
| Mutation AJAX | `success`, `message`, `data?` |
| Validation | `success`, `errors`، HTTP 422 |
| Toggle | `success`, `message`, `is_active/status` |
| Async import/export | `success`, `job/session_id`, `status`, `progress?` |
| API resource | `data`, `meta?`, `errors?` |

4. توحيد status toggle وتأكيد bulk-delete، مع مراجعة authorization والـtenant scope.
5. تدقيق ترتيب الأعمدة: `الحالة` قبل `الإجراءات`.

### بوابة القبول

- لا يوجد consumer يعتمد response غير موثق.
- DataTables تعالج empty/error/loading states بنفس النمط.
- عمليات bulk/delete لا تتجاوز authorization أو tenancy.

## 12. مرحلة routes والـlegacy — المرحلة 8

### التنفيذ

1. وسم كل route/file وفق تعريفات المرحلة 3.
2. لكل Inactive/Orphan candidate:
   - فحص `rg` في PHP/Blade/JS/tests/commands/jobs.
   - فحص route cache والـexternal integrations عند توفر بيئة التشغيل.
   - تسجيل المالك وسبب الإبقاء أو تاريخ الإزالة.
3. إزالة imports غير المستخدمة والمسارات المعلقة فقط في pull request منفصل.
4. حذف `.orig` وملفات legacy فقط بعد approval صريح ونسخة احتياطية source-control مؤكدة.
5. عدم حذف migrations أو جداول historical دون خطة data-retention مستقلة.

### بوابة القبول

- كل إزالة مدعومة بقائمة مراجع فارغة واختبارات routes/pages المتأثرة.
- لا توجد routes compatibility بلا owner أو موعد إزالة.
- لا توجد imports معلقة لcontrollers غير مستخدمة في ملفات routes.

## 13. مرحلة الاختبارات والتشغيل — المرحلة 9

### الاختبارات المطلوبة

1. Feature tests: authentication، 2FA، authorization، subscriptions، checkout، plans، wizard validation/drafts، API upgrade، tenant isolation.
2. Browser/manual smoke tests: forms، validation states، Select2، repeaters، drafts، next buttons، tables، uploads، imports، exports، payments callbacks.
3. Route tests: methods، middleware، response type، redirects، JSON 422/403/404.
4. Data tests: central connection مقابل tenant connection، soft deletes، restore، concurrency في الدفع/الترقية.
5. Build and static checks: `npm run build` بعد تعديل JS/CSS، وPint/test suite حسب البيئة.

### معايير الانتهاء

- كل route Active له owner واختبار أو smoke checklist.
- كل form Active له FormRequest وواجهة تحقق متسقة أو استثناء موثق.
- كل mutation محمية بالطريقة المناسبة ولا تعمل عبر GET.
- لا توجد ملفات legacy محذوفة دون سجل قرار.
- بناء الواجهة ناجح، واختبارات التطبيق ناجحة في بيئة MySQL صالحة.

## 14. قرارات مطلوبة قبل تنفيذ مراحل معينة

| القرار | مطلوب قبل | الخيارات التي يجب تثبيتها |
|---|---|---|
| تعريف السعر والضريبة | Website checkout | السعر شامل/غير شامل، قواعد الخصم والضريبة |
| تعريف availability للباقات | Website plans | تطبيق `available_from` أو عدم تطبيقه |
| مستهلك API upgrade | المرحلة 1 | HMAC أو token مقيّد، policy للتشغيل |
| مصير Transfer Requests والتقارير المعطلة | المرحلة 8 | إحياء بخطة منفصلة أو deprecate ثم حذف |
| ترتيب navigation | Tenant المرحلة 6 | الأدوار، الاشتراك، الترخيص، الأولوية التجارية |
| سياسة الاحتفاظ بالأرشيف | المرحلة 8 | soft delete، مدة الاحتفاظ، من يملك force delete |

## 15. ترتيب التنفيذ المقترح فعلياً

1. المرحلة 0: Baseline/inventory.
2. المرحلة 1: الأمان والعزل.
3. المرحلة 2: UI contracts.
4. المرحلة 3: form engine/components.
5. المرحلة 4: Website.
6. المرحلة 5: Super Admin.
7. المرحلة 6: Tenant، وحدة بوحدة.
8. المرحلة 7: DataTables/responses أثناء كل وحدة ثم تدقيق نهائي.
9. المرحلة 8: legacy/routes cleanup.
10. المرحلة 9: regression/final acceptance.

لا يتم دمج مرحلتين في pull request واحد عندما تمسّان الأمان أو قواعد البيانات أو الـtenancy. كل مرحلة لها changelog، قائمة ملفات، نتائج build/test، وخطة rollback قابلة للتنفيذ.
