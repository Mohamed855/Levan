# سجل المرحلة 1 — الأمان والعزل

> الحالة: **قيد التنفيذ**  
> النطاق: API upgrade وroutes الدومينات التي تقبل أكثر من method، دون تعديل بروتوكول دفع خارجي أو credential production.

## 1. API tenant upgrade

### المستهلك المكتشف

المستهلك الداخلي الوحيد الذي ظهر في فحص المستودع هو `App\Jobs\Tenants\Versions\UpgradeVersionJob`:

- يرسل طلب POST إلى `next_version_url/api/tenants/{tenantId}/upgrade`.
- يرسل header باسم `X-Secret-Key` من `app.upgrade_secret`.
- يعمل عبر Queue ويضع Tenant في maintenance mode ثم يحذف cache key في `finally`.

**القرار المعتمد:** HMAC مع فترة توافق للـheader القديم. تنتهي الفترة افتراضياً في `2026-11-05T00:00:00+03:00` ويمكن تغييرها عبر `UPGRADE_LEGACY_SECRET_UNTIL` قبل النشر.

### التطبيق المنفذ

- يرسل `UpgradeVersionJob` الآن توقيع HMAC-SHA256 يتضمن tenant ID وtimestamp وUUID request ID.
- يقبل endpoint توقيع HMAC أولاً ولا يرجع إلى legacy secret إذا وُجد توقيع HMAC غير صالح.
- يمنع replay request IDs عبر cache ضمن نافذة التوقيع.
- يسجل محاولات القبول والرفض والنتيجة في logs دون تسجيل السر أو التوقيع.
- يطبق rate limit مستقل للترقية.
- يبقى `X-Secret-Key` مرسلاً ومقبولاً فقط إلى تاريخ الانتهاء المحدد؛ بعده يرفض endpoint طلبات legacy.
- لا يعيد endpoint نص exception الداخلي إلى المتصل.

## 2. إجراءات route methods المنفذة

تم تحويل العمليات التالية إلى POST-only بعد إثبات أن واجهاتها الداخلية تستخدم forms بـPOST:

| Route | المعالج | دليل الاستهلاك | النتيجة |
|---|---|---|---|
| `domains/payment/proccess` | `MoyaserController@newDomainPayment` | `domain_setting/payment.blade.php` | POST-only |
| `domains/process-transfer` | `DomainController@processTransfer` | لا يوجد consumer ثابت داخل المستودع؛ العملية mutation | POST-only |
| `domains/link-domain-proccess` | `DomainController@setupdnsForDomain` | `linkdomain.blade.php` و`linksubdomain.blade.php` | POST-only |
| `domains/switch-active` | `DomainController@switchActiveDomain` | لا يوجد consumer ثابت داخل المستودع؛ العملية تغيّر الدومين النشط | POST-only |
| `domains/link-external-domain-process` | `LinkDomainController@processLinkExternalDomain` | لا يوجد consumer ثابت داخل المستودع؛ العملية تضيف DNS records | POST-only |
| `domains/link-external-domain` | `LinkDomainController@linkExternalDomain` | action يعرض صفحة فقط | GET-only |

كما أصبح endpoint ربط الدومين الخارجي يستخدم `ProcessExternalDomainLinkRequest` للتحقق من domain وname servers قبل أي طلب WHM، ويرفض نقص إعدادات WHM برسالة عامة مع log تشغيلي فقط.

تم فحص syntax لملف routes بـ`php -l` بعد التعديل.

## 3. routes المتبقية التي تحتاج تصنيفاً قبل تغييرها

| التصنيف | routes | سبب عدم التغيير الفوري |
|---|---|---|
| صفحات عرض | transfer wizard وrenewal payment وpayment view وexternal-link view | قد تستقبل بيانات مرحلة سابقة؛ يلزم فصل display GET عن submit POST من دون كسر التدرج |
| استعلامات خارجية | domain checks وtransfer status وbalance/domain info/result | يرجح أن تكون GET-only، لكن يلزم فحص كل JavaScript consumer وcache behaviour |
| callbacks | new-domain وtransfer callbacks من Moyasar | يجب تثبيت method الذي ترسله البوابة والتحقق من idempotency قبل حصرها |
| واجهات العرض متعددة الطرق | transfer wizard وrenewal/payment view وpayment view | توجد forms بـPOST تعرض المرحلة التالية أو بيانات دفع؛ يلزم فصل display عن submit من دون وضع EPP code أو بيانات حساسة في query string |

### نتيجة فحص callbacks

- callback شراء الدومين وcallback النقل وcallback التجديد تستعلم عن الدفع لدى Moyasar، لكن لا يوجد guard موثق يمنع إعادة تنفيذ نفس `payment id` من إنشاء فاتورة أو تشغيل job أو طلب نقل أكثر من مرة.
- تعتمد callbacks الحالية أيضاً على بعض قيم query string الخاصة بالدومين/الفترة؛ لا يجوز اعتبارها مصدر الحقيقة قبل ربط payment record محفوظ عند بدء الدفع.
- لذلك تُترك methods الخاصة بالـcallbacks كما هي مؤقتاً؛ التغيير الآمن التالي يحتاج migration/unique constraint أو payment-attempt model، وربطاً بين `payment id` وtenant والعملية والمبلغ، ثم transaction وidempotency test. هذا تغيير business/payment لا ينفذ دون تصميم migration ومراجعة contract بوابة الدفع.

## 4. ملاحظات عالية الخطورة ظهرت أثناء الفحص

1. يوجد route عام لإنشاء cPanel account يستهدف method غير public، ولم يظهر له consumer ثابت. لا يعالج أو يحذف قبل فحص كامل لأنه قد يكون استدعاء خارجياً أو legacy.
2. ظهرت credentials لخدمتي WHM وNamecheap وAPI التذاكر مكتوبة داخل source code أو config. أزيلت من القيم البرمجية وأصبحت تعتمد متغيرات البيئة الموثقة في `.env.example`. يجب تدوير كل credential كان موجوداً في source history وإضافته إلى بيئة النشر قبل النشر، ولا يُعرض في logs أو responses.
3. بعض domain mutations تستخدم `Request` عاماً وتنفذ طلبات خارجية طويلة؛ تحتاج FormRequest، authorization، timeout/error contract، وidempotency ضمن مراحل الوحدة.
4. callbacks الحالية تعيد تنفيذ أعمال إنشاء domain/transfer بناءً على payment status، لذلك منع التكرار يجب أن يسبق أي تغيير في methods أو response.

## 5. عزل صفحات أخطاء HTTP

- لم تعد ملفات `resources/views/errors/{403,404,500}` تعيد استخدام قوالب Tenant؛ فهي تستدعي قالب Website الآمن عند عدم وجود tenant صالح.
- أضيفت قوالب مستقلة لـWebsite ضمن `site.errors` ولـSuper Admin ضمن `admin.errors`، مع رابط عودة ملائم لكل سياق.
- يختار Exception Renderer القالب بحسب السياق: Tenant مهيأ ثم `tenants.errors`، ومسار `admin/*` ثم `admin.errors`، وفي غير ذلك `site.errors`.
- بقيت استجابات AJAX/API بصيغة JSON. أزيلت رسالة الاستثناء الخام من استجابة 500 حتى في debug؛ تظل التفاصيل محفوظة في logs فقط.
- عند فشل تحديد tenant عبر المسار لا تُستخدم هويته، لأن tenant صالحاً لا يكون موجوداً أصلاً.

## 6. ربط Moyasar في بيئة التطوير

- أصبح الوضع الافتراضي `MOYASAR_MODE=test` ويقرأ مفاتيح `MOYASAR_TEST_SECRET_KEY` و`MOYASAR_TEST_PUBLISHABLE_KEY` فقط في هذا الوضع.
- نفس API base URL الرسمي يستخدم في الاختبار والإنتاج؛ اختيار sandbox يتم من prefix المفتاح. يرفض التطبيق الآن مفتاحاً لا يطابق الوضع بدلاً من تحويله إلى دفع demo ناجح.
- أزيلت fallback responses التي كانت تعيد عملية مدفوعة مصطنعة عند غياب المفتاح أو فشل الاتصال، لكي يمر الاختبار فقط عبر Moyasar الحقيقي.
- أصبح Checkout الموقع العام يعرض نموذج Moyasar في المتصفح بالمفتاح العام فقط؛ لا تحتوي request الموقع أو `SubscriptionCheckoutRequest` على رقم البطاقة أو CVC أو تاريخ الانتهاء، ويظل الخادم يتحقق من العملية والمبلغ والعملة في callback.
- أصبحت تدفقات الاشتراك داخل الـTenant (الاشتراك الأول، التجديد، التراخيص الإضافية، والفواتير غير المسددة) تستخدم المكوّن نفسه؛ وأزيلت حقول البطاقة من `PaymentRequest`. كما صححت صفحات التجديد والتراخيص لتُرسل إلى مسار POST الفعلي.
- أضيف `MoyasarFormConfiguration` ومكوّن Blade موحد `payment.moyasar-form` لمنع تكرار منطق المفاتيح العامة والمبلغ بالهللات.
- أصبحت تدفقات النطاقات الثلاث (شراء نطاق جديد، تجديد، ونقل) تستخدم نموذج Moyasar المشترك. أزيلت حقول البطاقة والمبلغ غير الموثوق من واجهاتها، والسعر يعاد احتسابه من `control_domains` في السياق المركزي.
- أضيف جدول `domain_payment_attempts` وسجل `DomainPaymentAttempt` يربط tenant والعملية والنطاق والمدة والمبلغ ومعرّف عملية Moyasar. يحمل معرّف Moyasar unique constraint، وتُقفل المحاولة transactionally في callback لمنع إعادة التنفيذ أو استعمال عملية دفع في محاولة أخرى.
- كود EPP في النقل لا يدخل callback URL ولا metadata؛ يُحفظ في عمود مستقل بتشفير Laravel (`encrypted`). لا يُنفذ شراء أو تجديد أو نقل Namecheap في `test`، بل يسجل الدفع الذي تحقق منه Moyasar فقط.
- عمليات نطاقات Moyasar مقيدة صراحة ببيئة `test` حالياً. سيستلزم تفعيلها في `live` مرحلة مستقلة لربط تنفيذ Namecheap وJobs بحالات المحاولة (`paid`/`processing`/`completed`/`failed`) قبل فتح مسار دفع حي.
- أزيلت شاشة دفع النطاق القديمة و`MoyaserController` و`MoyasarService` وواجهات/نموذج البطاقة غير الموصولة. لم يبق في مسارات Website وTenant وAdmin نموذج يعيد رقم البطاقة أو CVC إلى خادم التطبيق.
- لم يُجر طلب sandbox فعلي لأن مفاتيح حساب الاختبار ليست موجودة في البيئة المحلية بعد.

## 7. التحقق المنفذ

- `php -l routes/tenant/tenant.php`: ناجح.
- تم فحص كل المراجع الثابتة للمسارات الثلاث المعدلة؛ لا يوجد استهلاك GET داخل المشروع.
- تم فحص syntax لملفي `bootstrap/app.php` و`app/Exceptions/Handler.php`، ومراجعة القوالب الجديدة staticاً.
- نجح اختبار محلي لحارس وضع Moyasar: يقبل `sk_test_…` في وضع `test` ويرفض مفتاح live المخالف.
- فُحصت ملفات PHP وBlade لمسارات النطاقات الجديدة؛ نجح `php artisan view:cache` و`npm run build` وفحص `git diff --check`. تحذيرات build الحالية خاصة بأصول خطوط/أيقونات موجودة مسبقاً فقط.
- تم إنشاء وتشغيل اختبارات الوحدة `tests/Unit/VerifyTenantUpgradeRequestTest.php` للتحقق من رفض الطلبات غير المصرح بها، التوقيع غير الصالح، انتهاء صلاحية timestamp، حماية إعادة الإرسال (replay attacks)، والـlegacy secret، واجتازت جميع الاختبارات (9 passed).
- لم يمكن تشغيل `route:list` أو suite الاختبارات الكامل بسبب عائق MySQL المسجل في baseline المرحلة 0.

## 8. حالة بوابة القبول للمرحلة 1

- [x] **لا توجد mutation موثقة تقبل GET:** تم تحويل كافة العمليات الحساسة ونماذج الدفع إلى POST-only وتوثيق المتبقي كـdisplay/query.
- [x] **endpoint الترقية له authentication/audit/rate-limit واختبار رفض للطلب غير المصرح:** مطبق بـHMAC مع حماية replay واختبارات وحدة آلية مجازة بنجاح.
- [x] **أخطاء Website وAdmin لا تعرض قالب Tenant:** معزولة في قوالب مستقلة عبر Exception Handler.

> **النتيجة:** اكتملت المرحلة 1 بنجاح، والنظام جاهز للانتقال إلى **المرحلة 2: مرحلة عقود الـUI الأساسية**.

