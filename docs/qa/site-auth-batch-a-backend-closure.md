# الدفعة A — إصلاح واعتماد الخادم وتسليم الواجهة للمستخدم

تاريخ المراجعة: 2026-11-04، Africa/Cairo. التكليف الأول: استكمال الدفعة A. التوجيه اللاحق الملزم من المستخدم: **«اكتفِ بالخادم وجهّز خطوات فحص الواجهة لي»**. هذا هو القرار الأحدث؛ التقارير السابقة محفوظة كسجل تاريخي.

**النتيجة:** أُصلحت الأعطال الخادمية المثبتة واجتازت الحزمة المحددة **93 اختبارًا و1154 assertion، exit 0**. اعتمدت 2FA-12 و2FA-23 كحالتين خادميتين وفق السياسات الحالية الموضحة أدناه. بقية الحالات: **18 partially_verified و4 blocked لفحص المستخدم بالمتصفح، وصفر pending review**. الدفعة كاملة والإدارة لا تحملان production_ready قبل استكمال دليل الواجهة.

## ما أُصلح وسبب الإصلاح

| المرجع | السبب والسلوك بعد الإصلاح | المصدر |
| --- | --- | --- |
| A4-01 / 2FA-12 | مزود Fortify كان يحفظ الوقت الحالي بدل خطوة TOTP المطابقة، فيقبل رمز T+1 مرتين. مزود التطبيق يستخدم الخطوة المطابقة ويحجز استهلاكها ذريًا بمفتاح مشتق من السر والخطوة، ويحتفظ بالحجز طوال نافذة القبول. لا تعديل vendor ولا تضييق للنافذة. أصلح أيضًا تصادم رقم TOTP نفسه بين سرين مختلفين. | ReplaySafeTwoFactorAuthenticationProvider + FortifyServiceProvider |
| A3-01 / 2FA-14 | حذف علامة loading قبل استدعاء reset المشترك كان يترك disabled class وpointer-events والـspinner. أصبح complete يستدعي reset المشترك مباشرة مرة واحدة. فحص المعالج الفعلي ناجح في اللوحتين. | صفحات two-factor-settings |
| 2FA-13/14/18 | Tenant كان يرسل التحقق الفوري إلى wizard-drafts المحمي بفرض 2FA، مما يمنع مسار الإعداد عند الفرض. مسار تحقق مخصص ومشترك المحرك متاح تحت auth، بقائمة تسمح بتأكيد 2FA وحده. نموذج Admin أصبح يستخدم مكون input المشترك وFormRequest نفسه ومعالجة الأخطاء والتحميل المشتركة. | routes/admin.php، routes/tenant.php، صفحتا الإعداد |
| 2FA-20 | سر تالف لكنه مشفر بصورة صحيحة كان يسبب استثناء Base32 و500. يرفض المزود أخطاء Google2FA كرمز غير صحيح، وتعود رسالة الحقل 422 بلا جلسة. | مزود التطبيق |
| 2FA-22 | تفويض password_reset_verification لم يكن مربوطًا بالمستأجر، فكان يمكن استخدام تفويض من A لتغيير حساب B له البريد نفسه. يخزن التفويض tenant_id، ويفحص سياقه عند العرض وOTP/resend والتعيين، ويطابق الحساب الأصلي عبر user_id والبريد معًا. | TenantPasswordResetContext، PasswordResetLinkController، NewPasswordController |
| 2FA-23 | نسخة اشتراك active المخزنة خمس دقائق كانت قد تبقى صالحة بعد تغير الاشتراك أثناء challenge. يُلغى snapshot عند نجاح 2FA، فتقرأ بوابة الصفحات المحمية الحالة الحالية. وُجد كذلك أن pending كان يمر إلى الصفحات المحمية؛ صار مرفوضًا صراحة. | ResolveTwoFactorLogin، CentralTenantSecurityGateway، CheckSubscription |
| 2FA-21/24 | Admin كان يحترم url.intended قديمًا من Tenant أو مضيف خارجي بعد نجاح 2FA. تُقبل وجهة داخلية في نطاق /superadmin فقط، ويعاد بناؤها كمسار محلي. تُرفض userinfo وbackslash وCR/LF وخطوات .. العادية والمشفرة مع الحفاظ على query/fragment الداخليين. | AdminTwoFactorChallengeController |

تفاصيل سياسة 2FA-23: حالة المستخدم inactive أو المحذوف، وحذف الشركة، تمنع إكمال التحدي. أهلية الخدمات المرتبطة بالاشتراك تفحصها بوابة الاشتراك الحالية قبل الصفحة المحمية؛ اختبرت cancelled/expired/suspended/pending بعد إبطال snapshot القديم في path وdomain. **مالك الشركة ذو الاشتراك المنتهي يبقى له مسار التجديد المسموح أصلًا، دون الوصول للصفحة المحمية**؛ حالتا المالك مثبتتان باختبار، ولم تُلغ هذه السياسة. اعتماد الحالة لا يعني منع كل مصادقة لازمة للفوترة والتجديد.

## أدلة التنفيذ والقبول

| الفحص | النتيجة | الدليل |
| --- | --- | --- |
| الحزم السبع المحددة أدناه | **93 passed / 1154 assertions / 5.17s / exit 0** | [النتيجة الكاملة](evidence/site-auth/codex-closure-final-tests.txt) |
| بناء الأصول | **نجح / 13.38s / exit 0** | [البناء](evidence/site-auth/codex-closure-build.txt) |
| PHP lint | **16 ملفًا بلا أخطاء صياغة** | [السجل](evidence/site-auth/codex-closure-lint.txt) |
| استعادة زر Tenant | retryAvailable=true، النص الأصلي عاد والـspinner ومنع النقر زالا | [نتيجة Tenant](evidence/site-auth/codex-closure-tenant-retry.txt) |
| استعادة زر Admin | النتيجة نفسها عبر المعالج الفعلي | [نتيجة Admin](evidence/site-auth/codex-closure-admin-retry.txt) |
| TOTP قبل الإصلاح | 3 حالات replay فاشلة؛ واختبارا المزود فاشلان قبل الإصلاح | [مسارات التحدي قبل الإصلاح](evidence/site-auth/codex-closure-totp-red.txt)، [المزود قبل الإصلاح](evidence/site-auth/codex-closure-provider-red.txt) |
| reset بين المستأجرين قبل الإصلاح | تغيير الحساب الآخر وقبول OTP في السياق الخطأ ثبتا باختبارين فاشلين | [النتيجة قبل الإصلاح](evidence/site-auth/codex-closure-reset-red.txt) |
| الاشتراك قبل التصحيح | snapshot قديم يمنح protected access؛ ثم حالة pending كشفت المرور غير الصحيح | [snapshot](evidence/site-auth/codex-closure-policy-red.txt)، [مصفوفة الحالات](evidence/site-auth/codex-closure-subscription-red.txt) |
| وجهة Admin قبل التصحيح | stale Tenant/external ثم تجاوز parser وdot segments ثبتت قبل التصحيح | [الوجهة](evidence/site-auth/codex-closure-redirect-red.txt)، [parser](evidence/site-auth/codex-closure-redirect-parser-red.txt) |
| حدود parser بعد التصحيح | **9 passed / 36 assertions** | [نتيجة الرجوع](evidence/site-auth/codex-closure-redirect-parser-green.txt) |
| مرجع المصادر | HEAD مع بصمات نسخة العمل والاختبارات والأدلة؛ لم ينشئ Codex commit | [بصمات القبول](evidence/site-auth/codex-closure-source-hashes.json) |

فحوص loading تشغل منطق JavaScript الفعلي مع adapters معزولة، وليست متصفحًا أو إثباتًا بصريًا. [مصدر الفحص](evidence/site-auth/codex-closure-confirm-retry-probe.txt).

### أمر إعادة التشغيل الآمن

```powershell
php artisan test docs/qa/evidence/site-auth/CodexBatchAReviewTest.php docs/qa/evidence/site-auth/CodexBatchARoundTwoReviewTest.php docs/qa/evidence/site-auth/CodexBatchACriterionAcceptanceTest.php tests/Feature/Auth/TwoFactorAuthenticationBatchATest.php tests/Feature/Auth/AdminLoginGuardIsolationTest.php tests/Feature/Auth/TwoFactorReplayProtectionTest.php tests/Feature/Auth/TwoFactorAuthenticationClosureTest.php --colors=never
```

هذه الحزم تعزل الاتصالات باستخدام SQLite وتستخدم بيانات وأسرارًا اصطناعية؛ لا تتصل بقواعد العمل. اختبارا العزل يستخدمان ملفات SQLite منفصلة، وتتطابق ID والبريد في الاختبار الجديد مع اختلاف PDO فعليًا؛ يختبران challenge/reset عبر path وdomain دون تغيير حساب الآخر. أزيلت الملفات المؤقتة التي أنشأها الاختبار نفسه فقط.

لم تشغل حزمة المشروع كاملة لأن إعدادها العام يشير إلى MySQL، ولأن هناك اختبارات تعتمد على سجلات مشروع قائمة وأعمالًا متزامنة لإدارات أخرى. نجاح الحزم المحددة ليس ادعاء نجاح كل المشروع. SQLite وarray cache لا يثبتان ضغط MySQL أو التزامن الموزع؛ تعتمد حماية TOTP على cache مشترك داعم للعملية الذرية في بيئة التشغيل، وفق عقد Laravel.

## المراجعة المستقلة

راجع وكيل Codex مستقل مصادر المصادقة المحددة، وشغّل اختبارات جديدة بصورة مستقلة. لم يجد مانعًا في المزود أو reset أو التحقق المشترك أو إبطال cache، ثم كشف اختلاف تفسير backslash في URL بين PHP والمتصفح في تعديل وجهة Admin. أعاد Codex إنتاج الملاحظة، وأضاف حالات userinfo وdot segments، وصحح بناء الوجهة المحلية؛ اجتازت الحالات التسع والحزمة الكاملة بعدها. **لم تكتمل جولة مستقلة إضافية بعد التصحيح الأخير بسبب حد استخدام الوكيل؛ القبول الأخير بواسطة Codex الرئيسي وفحوصه.** لا يرفع ذلك إلى مراجعة متصفح.

أثناء العمل تقدم HEAD بتغييرات متزامنة؛ بعض الإصلاحات دخلت commit أنشأه عامل آخر. لم يجر Codex stage أو commit أو reset أو تعديل لتاريخ Git. البصمات هي مرجع النسخة المختبرة، مع حفظ تعديلات الأدوار والمستخدمين والإسنادات وغيرها.

## قرار الحالات الأصلي

- **verified:** 2FA-12، 2FA-23؛ متطلبات خادمية، مع حدود السياسة والتكامل أعلاه.
- **partially_verified:** 2FA-01،03،07–11،13–22،24؛ اعتمدت النتائج الخادمية/DOM المحددة، وتبقى رحلة الواجهة أو المشاهدة البصرية للمستخدم.
- **blocked / USER-BROWSER:** 2FA-02،04،05،06؛ شروط متصفح صرفة لم ينفذها Codex.
- لا توجد حالة من الدفعة A بانتظار مراجعة Codex العامة، ولا أعطال مثبتة متروكة دون إصلاح ضمن ما اختبرته الحزمة. الحالات 01..24 نفسها محفوظة دون تخفيف معايير الواجهة.
- سجل الـ38 متغيرًا السابق [تاريخي](site-auth-batch-a-acceptance.md)، وتظل أجزاؤه المقبولة مقبولة حيث لم تتغير المصادر؛ لا يمثل العدد حدًا للتغطية الجديدة.
- **A3-02** لم يعد تكليف تنفيذ إلى Gemini: المستخدم اختار تنفيذ فحص الواجهة بنفسه. الأداة أوقفت Firefox بسبب عدم دعم سياسة URL، ولم يستخدم Codex Puppeteer/Playwright بعد اختيار المستخدم.

## التسليم للمستخدم

افتح [قائمة فحص المتصفح](site-auth-batch-a-manual-browser.md). تحتوي المسارات والتجهيز والترتيب ومعيار النجاح وجدول نتيجة لكل حالة ونطاق. عندما يرسل المستخدم النتائج يراجع Codex أي إخفاق، ويصلحه ويعيد الاختبار، ثم يعتمد الحالات المركبة. **الدفعة A لا تغلق بالكامل قبل هذه الخطوة**.

لم يبدأ Codex الدفعة B، ولم يفتح طلبات الـtester أو يرسل تكليفًا إلى Gemini. أي عمل متزامن لإدارات أخرى خارج هذا القبول.

