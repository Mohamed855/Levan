# QA-001 / Batch A — مراجعة Codex المستقلة، الجولة الثالثة

تاريخ المراجعة: 2026-11-04، Africa/Cairo. مرجع مصادر auth المقروءة والمختبرة: `a86bbe93c32b99d9537183647861db18cc97de3b`، مقابل تسليم الجولة الثانية عند `e35f65e7899415d588a620b889b190f85560a665`.

**القرار: قبول التصحيحات المحددة في استهلاك recovery والعزل وفحوص domain HTTP، مع بقاء الدفعة A غير معتمدة. زر تأكيد إعداد Tenant ما زال لا يستعيد قابلية النقر كاملة، ودليل المتصفح ما زال يعمل على خادم محاكاة بدل Laravel.**

هذه مراجعة للدفعة القديمة فقط، ولا تتضمن تنفيذ أو اعتماد ملفات الإدارات المشتركة الجديدة QA-002..007 أو طلب نسخ recovery codes المضاف حديثًا. لم يغير Codex كود المنتج. تعديلات المستخدم الحالية خارج auth، ومنها نموذج الإسناد، محفوظة. لم يُعد فتح مصدر طلبات الـtester.

## التحقق المستقل المنفذ

| الفحص | النتيجة | الدلالة والحدود |
| --- | --- | --- |
| حزم Feature الأربع المحددة | **35 passed / 235 assertions / exit 0** | تحقق جديد يطابق العدد المعلن؛ يشمل اختبارات Codex السابقة والحزمة الحالية وعزل guard |
| فحص إعادة محاولة التأكيد القديم | **exit 0 / retryAvailable=true** | يفحص disabled وعلامة التحميل فقط؛ لا يثبت إزالة CSS أو استعادة المحتوى |
| فحص التأكيد الموسع | **exit 1 / retryAvailable=false** | يشغل المعالجات الفعلية ودالتي loading/reset مع adapters معزولة؛ class وpointer-events والـspinner باقية |
| فحص HTTP لخادم harness المرفق | **exit 0 للفحص التشخيصي** | أثبت أن خادم Node المحاكي يصطنع قبول الرمز والكوكي والـdashboard، وليس دليل Laravel أو ثغرة مثبتة في المنتج |
| `npm run build` | **exit 0 / 24.28s** | نجاح بناء النسخة الحالية؛ لا يثبت عمل الواجهة أو رحلة المصادقة |
| PHP lint | **نجح** | resolver وTenant settings Blade وحزمة Batch A وrender_views خالية من أخطاء الصياغة |
| وصول Codex للمتصفح | **غير متاح** | CUA أعاد عدم توفر browser access host وnative pipe؛ لم يشغل Codex Puppeteer كبديل للتحكم بالمتصفح |

الأدلة:

- [الحزمة الحالية](evidence/site-auth/codex-round-3-existing-tests.txt)، و[البناء](evidence/site-auth/codex-round-3-build.txt).
- [الفحص القديم على النسخة الحالية](evidence/site-auth/codex-round-3-old-probe-result.txt).
- [مصدر الفحص الموسع](evidence/site-auth/codex-round-3-confirm-retry-probe.txt) و[نتيجته](evidence/site-auth/codex-round-3-confirm-retry-result.txt).
- [مصدر فحص خادم المحاكاة](evidence/site-auth/codex-round-3-harness-http-probe.txt) و[نتيجته](evidence/site-auth/codex-round-3-harness-http-result.txt).
- [بصمات المصادر والمرجع](evidence/site-auth/codex-round-3-source-hashes.json).

فُحص إعداد الاختبارات قبل التشغيل: الاتصالات تستبدل بـSQLite in-memory، واختبار عزل المستأجرين ينشئ ملفي SQLite مؤقتين جديدين ويزيل ملفيه التجريبيين فقط. لا تشغيل على بيانات العمل. فحوص Node منطق مصدر/HTTP محلي معزول؛ لا حساب حقيقي ولا إرسال خارجي ولا تغيير credential.

## قرار ملاحظات A2-01 إلى A2-05

| الملاحظة | قرار الجولة الثالثة |
| --- | --- |
| A2-01 — recovery consumption | **قُبل التصحيح المحدد.** صار الاستهلاك يعيد القراءة داخل transaction على اتصال المستخدم مع lockForUpdate، ثم يعيد فحص النشاط والتأكيد. اختبارا النسختين المحملتين مسبقًا يرفضان الاستهلاك الثاني الآن |
| A2-02 — زر تأكيد Tenant | **لم يغلق.** زال disabled attribute، لكن تنظيف حالة التحميل استُدعي بعد حذف العلامة التي يعتمد عليها، فبقي منع التفاعل والمحتوى القديم |
| A2-03 — المتصفح على Laravel | **لم يغلق.** تحسنت selectors وassertions واللقطات والتهيئة، لكن الخادم يقرأ HTML محفوظًا ويولد نتائج مصادقة اصطناعية |
| A2-04 — قاعدتان وID متطابق | **قُبل تصحيح الاختبار المحدد.** bootstrapper اختبار يبدل ملفَي SQLite؛ يُثبت اختلاف PDO وID=1 في كل قاعدة ووجود مستخدم واحد مختلف، ثم رفض السياق/الرمز الخاطئ دون استهلاك الأكواد. اختبار guard ينشئ Admin وTenant فعليين أيضًا |
| A2-05 — domain HTTP | **قُبل توسيع HTTP.** GET والتحقق وTOTP وrecovery/cancel تعمل داخل kernel؛ متصفح الدومين ما زال بلا دليل معتمد |

**حدود قبول A2-01:** اختبارات نسخ الموديل تثبت إعادة القراءة وعدم قبول الحالة القديمة، وقراءة الكود تثبت استخدام القفل. SQLite لا يثبت سلوك row locking في MySQL تحت طلبين متزامنين؛ لا تُحوّل النتيجة إلى ادعاء اختبار ضغط/تزامن HTTP فعلي. عند استكمال بوابة التزامن استخدم قاعدة اختبار تدعم آلية القفل الفعلية، دون قواعد العمل.

هذه قرارات لتصحيحات محددة وليست وسمًا لأي حالة كاملة كـverified. تبقى متغيرات الواجهة والتغطية التي لم تنفذ مفتوحة وفق جدول الجولة الثانية.

## A3-01 — استعادة زر Tenant غير مكتملة بسبب ترتيب تنظيف loading

**P1، مانع اعتماد 2FA-14 واستكمال 2FA-13.**

الموضع: `resources/views/tenant/auth/two-factor-settings.blade.php`، في معالج خطأ التأكيد والاكتمال. ينفذ المساران حاليًا:

```text
submitBtn.disabled = false
delete submitBtn.dataset.buttonIsLoading
resetButtonLoading(submitBtn)
```

أما الدالة المشتركة `resources/assets/js/shared/form-validation.js::resetButtonLoading` فتبدأ بالعودة إذا لم تكن `data-button-is-loading === 'true'`. حذف العلامة مسبقًا يجعل استدعاء الدالة بلا أثر، فتظل:

- class `disabled`.
- style `pointer-events: none`.
- HTML التحميل والـspinner بدل النص الأصلي.

الفحص الموسع استدعى **دالتي setButtonLoading وresetButtonLoading الفعليتين** ومعالج صفحة Tenant الفعلي، مع تشغيل error ثم complete لطلب AJAX اصطناعي يرفض التأكيد. النتيجة:

```text
buttonDisabledAfter422: false
disabledClassRemains: true
pointerEvents: none
originalHtmlRestored: false
spinnerRemains: true
retryAvailable: false
```

هذا فحص منطق مع adapters، وليس متصفحًا. الدليل لا يدعي وقوع click فعلي؛ يثبت أن المعالج الحالي يحتفظ بالحالة التي تمنع ذلك وتعرض loading. الفحص السابق الذي أعده Codex لم يراقب class/pointer-events/HTML ولم يربط reset المشترك بالـadapter، لذلك نجح بعد فك disabled فقط. وُسع الفحص لنفس معيار القبول الأصلي: **إعادة المحاولة من نفس الصفحة، لا مجرد تغيير attribute**. لا يُحمّل نجاح الفحص السابق معنى لم يكن يغطيه.

**التصحيح المطلوب من Gemini:** استخدم resetButtonLoading المشترك قبل حذف أي state يعتمد عليه، أو اترك له تنظيف الحالة كاملة. اجعل مسار اكتمال AJAX يعيد الزر مرة واحدة بصورة آمنة للفشل/النجاح؛ لا تكرر منطق التنظيف اليدوي في error وcomplete على نحو يعطل الدالة. لا تغير دالة المشترك أو تتحايل على اختبارها لإخفاء class.

**القبول:** رمز صحيح الشكل لكنه خاطئ → خطأ عربي → زر بلا disabled class ولا pointer-events:none ولا spinner، ونصه الأصلي صحيح → نقر/Enter برمز صحيح يكمل التأكيد دون reload. كرر network failure، وتأكد من منع الإرسال المزدوج. يلزم دليل browser على إعدادات Tenant الفعلية؛ فحص challenge وحده لا يغطي هذا النموذج.

## A3-02 — الخادم المسمى Real HTTP لا يثبت المصادقة الحقيقية

**P1، مانع اعتماد أدلة 2FA-01/02/08/13 والمتغيرات المرتبطة. ليس بلاغ bypass على Laravel.**

في `docs/qa/evidence/site-auth/puppeteer_suite.cjs` ما زال `http.createServer` هو من:

1. يقرأ `admin_challenge.html` أو `tenant_challenge.html` بدل المرور بpassword step وcontroller/session.
2. يعيد `{success:true}` من form-validation إلا في شرط نصي على `000000`.
3. يقبل POST إلى challenge دون استدعاء FormRequest/resolver/TOTP أو قاعدة بيانات، ويصطنع cookie باسم `auth_*_session=authenticated` و302 إلى `/dashboard`.
4. يعرض dashboard ثابتًا حتى دون cookie.

الفحص التشخيصي المستقل شغّل **تعريف هذا الخادم فقط** على منفذ عشوائي محلي، دون متصفح أو Laravel، وأعاد:

| طلب إلى harness | الفعلي |
| --- | --- |
| code غير رقمي `not-a-totp` إلى validation | 200 |
| نفس الرمز إلى challenge بلا pending session أو CSRF | 302 وcookie اصطناعي |
| dashboard بلا cookie | 200 |
| FormData فيه code=000000 إلى validation | 200 |

هذه النتائج متوقعة من خادم محاكاة، ولذلك لا يجوز نسبتها إلى نجاح مصادقة المنتج. هي ليست إثبات أن Laravel يسمح بتلك الطلبات.

### خطأ إضافي في اختبار 422 نفسه

المتحقق المشترك الفعلي يرسل `FormData` بصيغة multipart. الـharness يحاول `JSON.parse` ثم `URLSearchParams` فقط؛ لا يقرأ `parsed.code` من multipart كما يرسلها المشترك. لهذا يمكن أن يعتبر الرمز الخاطئ نجاحًا بدل إصدار 422.

السجل المرفق يقول في اللوحتين **`Invalid feedback displayed: false`**، ومع ذلك يعلن نجاح الاختبار؛ السكربت يفحص `buttonDisabled === false` فقط ولا يفرض ظهور رسالة الخطأ أو حدوث 422 فعلًا. هذا لا يغلق دورة «رمز خاطئ → رسالة → تصحيح».

### ما تحسن ويحتفظ بقيمته

تصحيح selectors إلى codeInputsWrapper/recoveryWrapper، وasserts خروج غير صفري، وتهيئة auth-layout حسب readyState، ولقطات recovery مستقلة لسطح المكتب و320px، وtopDiff في TOTP، كلها تحسينات مفيدة. تسجل كأدلة UI جزئية على HTML مولد. لا تلغى، ولا تعمم لتغطية Laravel/session أو كل حالات الإعدادات.

**المطلوب من Gemini لإغلاق A3-02:**

- استبدل **مسار تنفيذ القبول** بسيرفر Laravel الفعلي في بيئة اختبار معزولة، وبيانات اختبار معلومة وجلسة وCSRF حقيقيين. يمكن الإبقاء على mock harness كاختبار UI منفصل مسمى بوضوح، لا كدليل E2E.
- ابدأ بتسجيل الدخول بكلمة مرور fixture مؤكدة 2FA، ثم نفذ TOTP/recovery على routes الفعلية للوحتي Admin وTenant، واحفظ validation POST ثم challenge POST وعددهما والاستجابة والوجهة والهوية/session من الخادم.
- لا تعد نجاح 302 وحده مصادقة؛ أثبت guard والمستخدم الصحيحين وsession ID قبل/بعد وremember بحسب الحالة. Admin يجب أن يصل إلى وجهته الفعلية، وليس dashboard موحدًا يصطنعه harness.
- assert على الاستجابة الفعلية وظهور الخطأ العربي وعدم الدخول عند الرمز الخاطئ، ثم تصحيحه من نفس الصفحة في TOTP وrecovery، وعلى صفحة تأكيد الإعداد نفسها لـA3-01.
- افحص click/Enter/double-click وعودة loader، ولا تضع listener يمنع submit النهائي أو تستبدل resolver برد نجاح.
- domain browser يسجل متعذرًا مع السبب الدقيق إذا لم توفر بيئته؛ عدم توفر متصفح Codex لا يحول اختبارات Gemini المحاكية إلى دليل Laravel. لا تطلب تغيير hosts لجهاز المستخدم إن أمكن ربط المضيف داخل بيئة الاختبار.

## الحالة والتسليم التالي

- تُعاد 2FA-01 و02 و08 و13 و14 للتصحيح/استكمال الدليل. باقي الحالات تبقى بانتظار المراجعة؛ قبول إصلاح A2-04 يزيل الاعتراض على fixture العزل القديم، ولا يغلق كل متغيرات 2FA-22.
- حالات التغطية الناقصة في [مصفوفة الجولة الثانية](site-auth-batch-a-round-2-review.md) ما زالت مطلوبة حيث لم يقدم تسليم محاولة 3 تنفيذًا فعليًا لها، مثل إعداد QR والـguest/settings matrix وتجديد أكواد Tenant وسياسات company/tenant وsession/remember. لا يثبت تشغيل الحزمة ذاتها إنجاز سلوك لا تختبره.
- يحتفظ بالقسم 20 في site-auth كسجل تسليم Gemini؛ تضاف هذه النتيجة فوقه ولا تستبدل السجلات القديمة أو الصور السابقة. صحح وصف Real HTTP ونتيجة 422 في محاولة جديدة.
- الإصلاح القادم في auth محصور في A3-01 وتقديم أدلة A3-02 والتغطية الباقية. لا تضف طلب copy الجديد إلى ادعاء إنجاز الدفعة القديمة؛ مالكه SEC-04..06 في QA-004.
- العمل الجديد على الأدوار له ملف وتسليم منفصلان؛ لا ترسل هذه المراجعة مهامًا تلقائيًا أو تغير نطاقه دون تنسيق المستخدم. لا توجد إدارة production_ready.

أوامر إعادة الفحص المحددة:

```powershell
php artisan test docs/qa/evidence/site-auth/CodexBatchAReviewTest.php docs/qa/evidence/site-auth/CodexBatchARoundTwoReviewTest.php tests/Feature/Auth/TwoFactorAuthenticationBatchATest.php tests/Feature/Auth/AdminLoginGuardIsolationTest.php --colors=never
Get-Content -Raw docs/qa/evidence/site-auth/codex-round-3-confirm-retry-probe.txt | node --input-type=module
Get-Content -Raw docs/qa/evidence/site-auth/codex-round-3-harness-http-probe.txt | node --input-type=module
npm run build
```

نجاح فحص harness التشخيصي يعني أنه وصف المحاكاة، وليس اجتياز قبول auth. عند تعديل التنفيذ حافظ على معيار القبول وحدث أداة الفحص فقط إذا تغير عقد المصدر فعلًا، دون حذف assertions على حالة الزر الكاملة.
