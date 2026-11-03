# QA-001 / Batch A — مراجعة Codex المستقلة، الجولة الأولى

**القرار: إرجاع الدفعة إلى Gemini للإصلاح واستكمال الأدلة. لا اعتماد لأي حالة كـverified في هذه الجولة.**

وقت تسجيل المراجعة: 2026-11-03 نحو 17:54 بتوقيت Africa/Cairo. مرجع كود auth المختبر: `ef83d1855acfda358082d5b742419dbf0952a643`، مقارنة بمرجع الإعداد `2f1e4060a251b77d3777dbf92abfbf08ed791054`.

أثناء المراجعة انتقل HEAD إلى `ba03fb165a3c86cc022b6c66ba4742d69b044221`؛ الفرق عن ef83 يقتصر على `.gitignore` و`public/build.zip`، وليس كود auth المختبر. لم ينشئ Codex هذين الالتزامين. توجد تغييرات مزادات داخل ef83، فصلت عن نطاق هذا القبول ولم تعدلها المراجعة أو تعتمدها.

في الفحص الأخير أصبح HEAD `3ac426a66` (تحديث أرشيف البناء)، وأكدت مقارنة الأسماء أن الفرق عن ef83 ما زال `.gitignore` و`public/build.zip` فقط؛ كود auth موضوع النتائج لم يتغير.

## نتيجة التحقق المستقل

| الفحص | النتيجة | ما يثبته وحدوده |
| --- | --- | --- |
| `php artisan test tests/Feature/Auth/TwoFactorAuthenticationBatchATest.php tests/Feature/Auth/AdminLoginGuardIsolationTest.php` | 21 passed / 119 assertions / exit 0 | مجموعة الاختبارات الحالية تعمل؛ لا تنفذ JavaScript أو المتصفح، ولا تغطي جميع متغيرات الحالات الـ24 |
| `php -l` لكل ملفات PHP/Blade المعدلة في نطاق الدفعة والمسارات | نجح | صحة صياغة PHP؛ لا يثبت render/layout/submit في المتصفح |
| `npm run build` | نجح / exit 0 / 12.76s | الأصول تبنى؛ لا يثبت صحة المحاذاة أو معالجة النقر والكيبورد |
| اختبارات Codex الإضافية المعزولة | 4 failed / 6 assertions / test exit 1 | إعادة إنتاج 4 ثغرات في قبول السياق/حالة 2FA/تنظيف الجلسة؛ التفاصيل أدناه |
| المراجعة البصرية والتفاعل في المتصفح | لم تعتمد في هذه الجولة | لا توجد لقطة أو قياسات أو traces متصفح كافية في تسليم Gemini لإثبات المتطلبات B؛ أوقف القبول بعد إخفاقات الخادم المثبتة |
| مقارنة المصدر والفروق | تمت | راجعت ملفات الدفعة الـ14، ملف اختبار Gemini، وسجل الأدلة؛ المكون input تغير أيضًا ويستلزم ارتدادًا للصفحات المستهلكة |

الأدلة الجديدة: [اختبارات إعادة الإنتاج](evidence/site-auth/CodexBatchAReviewTest.php)، [نتائجها الفعلية](evidence/site-auth/codex-batch-a-probes.txt)، [نتيجة البناء المستقل](evidence/site-auth/codex-batch-a-build.txt). الاختبارات تستخدم اتصالي SQLite `:memory:` وتوقف tenancy bootstrappers قبل إنشاء أي بيانات. لا استدعاء دفع أو بريد حقيقي ولا استخدام لحسابات المشروع.

اختبارات Codex مقصودة كاختبارات رفض متوقع؛ فشلها حاليًا يعني أن التطبيق سجل الدخول/احتفظ بالمفتاح بينما المتوقع المنع/التنظيف. وضعت خارج suite المعتادة لتبقى دليل مراجعة مستقلًا دون تغيير إصلاحات Gemini. يستخدم Gemini نفس السيناريوهات ويضيف regression مناسبًا داخل حزمة auth عند المعالجة، دون إضعاف assertions.

## R1 — قبول سياق pending ناقص رغم ادعاء التحقق الصارم

**الأولوية P1؛ مانع اعتماد 2FA-20/21/22.**

المواضع:

- `app/Http/Requests/Admin/Auth/AdminTwoFactorChallengeRequest.php:16`: يسمح بـ`guard === null`.
- `app/Http/Requests/Tenants/Auth/TenantTwoFactorChallengeRequest.php:17`: يسمح بغياب guard، والسطر التالي يسمح بغياب tenant ID.
- `app/Actions/Auth/ResolveTwoFactorLogin.php:26`: يرفض اختلاف guard فقط إن لم يكن null؛ وبالمثل tenant في السطر 30.
- فحوص GET challenge في المتحكمين تستخدم السماح نفسه عند غياب المفاتيح.

إعادة الإنتاج في الذاكرة:

1. أنشئ حساب Admin اصطناعيًا مؤكد 2FA له recovery code معلوم للاختبار.
2. أنشئ pending session فيها `login.id` فقط، دون `login.guard`، وأرسل recovery الصحيح إلى challenge المركزي.
3. **الفعلي: guard admin أصبح authenticated.** الاختبار `test_missing_guard_must_not_complete_admin_password_step` فشل لأن `check()` أعاد true.
4. كرر في Tenant B بجلسة فيها id وguard tenant لكن لا `login.tenant_id`.
5. **الفعلي: guard tenant أصبح authenticated أيضًا.** الاختبار `test_missing_tenant_binding_must_not_complete_tenant_password_step` فشل.

حدود الاستنتاج: هذا اختبار لجلسة قديمة/ناقصة أو مسار لم يربط السياق؛ لا يدعي أن المستخدم يستطيع تزوير session الخادم، ولا أن تدفق login الجديد المعتاد لا يكتب المفاتيح. لكنه يثبت أن شرط العزل الجديد ليس fail-closed عند نقصها، خلافًا للتسليم.

التصحيح المطلوب: اجعل اكتمال وربط pending context شرطًا لازمًا في create/authorize/resolver؛ الجلسات القديمة الناقصة تعود لخطوة كلمة المرور. اختبر guard الصحيح والخاطئ والمفقود، وtenant الصحيح والخاطئ والمفقود، مع مستخدمين فعليين بنفس numeric ID. لا يكفي اختبار الطلب دون وجود حساب قابل للمصادقة أو قبول status من قائمة 403/419/302 دون إثبات غياب الجلسة.

## R2 — إزالة تأكيد 2FA لا تبطل pending challenge

**الأولوية P1؛ مانع اعتماد 2FA-14/17/20/23.**

الموضع: `app/Actions/Auth/ResolveTwoFactorLogin.php` بعد استرجاع المستخدم وقبل اعتماد TOTP/recovery. الفحص الجديد يختبر status، لكنه لا يعيد فحص `two_factor_confirmed_at` أو حالة تفعيل 2FA الحالية.

إعادة الإنتاج: حساب مؤكد 2FA له pending challenge؛ أزل `two_factor_confirmed_at` من fixture لمحاكاة زوال التأكيد/إعادة الإعداد في جلسة أخرى؛ أرسل recovery الصحيح مع guard صحيح.

**الفعلي: تم تسجيل دخول Admin رغم أن 2FA غير مؤكد وقت الاستكمال.** فشل `test_unconfirmed_two_factor_state_must_reject_old_pending_challenge` لأن check أعاد true.

حدود الاستنتاج: هذا يثبت قبول حالة إعداد لم تعد مؤكدة مع بقاء secret/recovery؛ لا يدعي أن disable القياسي يبقي حقولًا يفترض أن يمسحها. غطاء الاختبار المطلوب يشمل كذلك disable ثم إعادة enable غير المؤكد، وتغير الحساب بعد المرحلة الأولى.

التصحيح المطلوب: ارفض pending challenge إذا لم تعد حالة 2FA مؤهلة ومؤكدة، وأعد المستخدم إلى المسار المناسب لإعادة المصادقة. اختبر اللوحتين وحالات active/inactive/deleted/disabled/unconfirmed، مع التأكد أن recovery لا يلتف على فحص الحالة.

## R3 — إلغاء تحدي Admin يترك tenant binding قديمًا

**الأولوية P2؛ مانع اكتمال 2FA-19/24.**

المواضع: `AdminAuthController@login` يكتب guard admin دون إزالة `login.tenant_id` المتبقي من تحدي Tenant؛ و`AdminTwoFactorChallengeController@cancel` و`@store` لا يزيلان هذا المفتاح.

إعادة الإنتاج: pending session فيها id وguard admin وremember و`login.tenant_id=previous-tenant`، ثم GET cancel المركزي.

**الفعلي: التحويل إلى login صحيح، لكن tenant binding بقي.** فشل `test_admin_cancel_must_clear_tenant_context_left_by_panel_switch` لأن المفتاح ليس null.

التصحيح المطلوب: استبدل سياق pending كاملًا عند بدء تحدي جديد، ونظف جميع مفاتيحه عند cancel/نجاح التحقق. لا تمسح مفاتيح Tenant hint المخصصة لتجربة الدخول بلا سبب؛ المقصود هنا مفاتيح `login.*` الخاصة بالتحدي فقط. أضف سيناريو Tenant challenge → Admin challenge → cancel/success ثم حساب آخر.

## R4 — الأدلة لا تثبت إنجاز كل حالات الواجهة والإعدادات

**الأولوية P1؛ مانع اعتماد الدفعة ككل.**

- الاختبارات الـ17 تستخدم Feature requests و`withoutVite()`؛ لا متصفح ينفذ click/Enter/paste/autofill أو JavaScript التبديل. وجود حقل واحد واسم CSS لا يثبت 2FA-01 إلى 08 بصريًا وسلوكيًا.
- لا توجد اختبارات فعلية في الحزمة لرمز TOTP منتهي/خارج window/replay. نجاح current TOTP وحده لا يغلق 2FA-12.
- لا توجد دورة enable → QR → confirm خاطئ/صحيح → reload → login في هذه الحزمة. الإحالة إلى «Fortify workflow» ليست نتيجة تنفيذ لـ2FA-13/14.
- لا يوجد تنفيذ شامل لمصفوفة guest/cross-account/cross-tenant لجميع settings endpoints في 2FA-15.
- اختبار regenerate يثبت عدد JSON=8 فقط؛ لا يثبت uniqueness أو إلغاء الأكواد القديمة أو نجاح الجديدة وحفظ secret في اللوحتين.
- disable الاختياري ودخول ما بعده غير مختبرين؛ policy test الموجود لـAdmin فقط ولا يغطي company/tenant settings أو redirect loop/logout.
- اختبار cancellation لـAdmin فقط؛ cross-tenant الحالي لا ينشئ مستخدمًا فعليًا في كل Tenant يحمل ID متطابقًا؛ اختبار inactive لـAdmin فقط ولا يثبت status 403 المذكور في السجل.
- throttle الموجود لـAdmin فقط ولا يثبت فترة انتهاء الحظر أو remember/session regeneration وتطبيقها باللوحتين.
- لا دليل Tenant domain، ولا سبب موثق لاستبعاده، ولا مصفوفة أجهزة/أحجام للـplaceholder وصف الرابطين أو مقارنة موضع الرجوع في صفحات auth الأخرى.

المطلوب: نفذ الأجزاء الناقصة وسجل نتائج كل variant فعلًا. إذا لم تتوفر بيئة/دومين/متصفح، سجله blocked مع السبب ولا تقدم نجاحًا مستنتجًا من مصدر مكتبة. لا يلزم إعادة تنفيذ اختبارات ناجحة بلا سبب؛ ركز على الأجزاء الناقصة والإصلاحات وآثارها المشتركة.

## R5 — تصحيح أسباب العطل وسجل الأدلة

**الأولوية P2؛ شرط قبول التقرير النهائي.**

1. في Admin كان النموذج السابق POST عاديًا دون data-form-validate. غياب allowlist endpoint لا يثبت بمفرده سبب توقف هذا POST قبل أن يصبح النموذج يستخدم ذلك endpoint. يلزم دليل قبل/بعد يحدد النقطة التي كانت تمنع Submit، كما طلب القسم 6؛ التصحيح الحالي قد يكون صحيحًا كربط للمشترك دون أن يثبت التشخيص التاريخي المعلن.
2. سجل 2FA-05 يقول إن `flex-direction: row-reverse` أضيف؛ لا توجد هذه الإضافة في diff. لا تضف reverse لمجرد مطابقة التقرير؛ تحقق RTL فعليًا ثم صف التنفيذ الموجود. وجود `flex-wrap` و`max-width:49%` مع gap يستلزم اختبار العرض الضيق قبل الادعاء أنه لا ينزل لصف آخر.
3. سجل 2FA-09 ينسب الاستهلاك إلى Fortify/TwoFactorAuthenticationProvider، بينما المسار الحالي هو `ResolveTwoFactorLogin::consumeRecoveryCode` ثم `PersistAuthenticationStateAction::replaceRecoveryCodes`؛ صحح الإسناد.
4. سجل 2FA-23 يقول status 403، لكن الاختبار لا يفحص status أصلًا، والكود يطرح ValidationException. سجل الاستجابة الفعلية HTML مقابل JSON دون اختلاق رقم موحد.
5. المرجع `2f1e4060` هو مرجع ما قبل الإصلاحات؛ لا تستخدمه وحده لإثبات النسخة المعدلة. أرفق المرجع/الفروق أو hashes للنسخة التي نفذتها فعلًا، وميز القراءة عن الاختبار.
6. لا تغير نتائج محاولات Gemini القديمة بأثر رجعي. أضف تصحيحًا ومحاولة جديدة واربطهما بملاحظات Codex هنا.

## حالة كل جزء من الدفعة بعد المراجعة

| الحالات | قرار الجولة الأولى | المطلوب قبل إعادة التسليم |
| --- | --- | --- |
| 2FA-01..08 | reopened — أدلة B ناقصة | browser submit/click/Enter/retry/paste/autofill/toggle/صور وقياسات وفق الأحجام واللوحات؛ سجل تشخيص قبل/بعد |
| 2FA-09..11 | reopened — تحقق backend جزئي ناجح | استكمال tenant replay/الكود الآخر/الأكواد الملغاة وكل payload variants، وإثبات الواجهة والأخطاء |
| 2FA-12..18 | reopened — الادعاء أوسع من التغطية | TOTP window/replay، دورة الإعداد والتأكيد والتعطيل، مصفوفة الصلاحيات والسياسات واللوحتين |
| 2FA-19 | reopened — R3 + نقص Tenant | تنظيف كامل لسياق التحدي واختبار cancel/history/replay/حساب آخر في النطاقين |
| 2FA-20..22 | reopened — R1 + حالات ناقصة | رفض السياق المفقود واختبارات حسابات فعلية متطابقة المعرفات وغياب/انتهاء الجلسة |
| 2FA-23 | reopened — R2 + نقص Tenant | إعادة فحص أهلية 2FA والحساب عند الاستكمال، وتوثيق الاستجابة الفعلية |
| 2FA-24 | reopened — R3 + أدلة جزئية | throttle ومدة الحظر وremember وsession cleanup/regeneration في النطاقين |

إعادة الفتح تعني رفض اكتمال الحالة بكل معاييرها؛ لا تعني أن كل جزء فرعي من الحالات فشل. نجاح 21 اختبارًا مسجل ومحفوظ، ولا توجد حالة verified في هذه الجولة.

## مهمة Gemini التالية — دفعة A، جولة التصحيح الأولى

اقرأ هذه المراجعة ثم عالج R1/R2/R3 في ملفات auth المذكورة فقط، واستكمل R4 وصحح R5. لا تبدأ دفعة B ولا تعدل إدارات المزادات. لا توسع صلاحيات البيئة أو تلغِ CSRF أو validation أو 2FA لحل الإرسال.

أوامر إثبات الإصلاح بعده:

```powershell
php artisan test docs/qa/evidence/site-auth/CodexBatchAReviewTest.php
php artisan test tests/Feature/Auth/TwoFactorAuthenticationBatchATest.php tests/Feature/Auth/AdminLoginGuardIsolationTest.php
```

أضف regression داخل suite الأساسية يغطي السيناريوهات نفسها، ثم شغل ملفات التغطية الناقصة التي أضفتها. عند تعديل JS/CSS/SCSS شغل `npm run build` وأعد التفاعل من ناتج البناء. سجل أدلة المتصفح لكل لوحة/سياق/عرض منطبق؛ لا تجعل مجرد قراءة كود Fortify نتيجة passed.

التسليم التالي: الملفات المعدلة، السبب المثبت، أوامر ونتائج كاملة، مصفوفة الحالات والـvariants، ما بقي blocked، والمرجع الفعلي. لا verified ولا production_ready قبل إعادة مراجعة Codex.
