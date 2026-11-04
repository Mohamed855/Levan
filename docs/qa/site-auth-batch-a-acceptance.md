# QA-001 / Batch A — قرارات القبول التفصيلية من Codex

**سجل تاريخي:** حل محله [تقرير إصلاح واعتماد الخادم](site-auth-batch-a-backend-closure.md) بعد تكليف Codex بالإصلاح واختيار المستخدم فحص الواجهة يدويًا. الأعداد والإخفاقات أدناه تخص لحظة هذه المراجعة وليست الحالة الحالية.

تاريخ القرار: 2026-11-04، Africa/Cairo. يحل هذا السجل محل **التصنيف الإجمالي** في مراجعة الجولة الثالثة، مع حفظ تقاريرها وأدلتها التاريخية. مرجع المصادر: `a86bbe93c32b99d9537183647861db18cc97de3b`.

أخطأ سجل الجولة الثالثة بترك 19 حالة قيد المراجعة بعد مراجعة أجزاء منها بالفعل. صُحح ذلك هنا: **38 متغير قبول محددًا معتمدًا كـverified**، ضمن 15 مجموعة فحص. الحالات الأصلية المركبة: **14 partially_verified و10 reopened وصفر pending review**. لا توجد حالة أصلية مكتملة verified ولا إدارة production_ready؛ اعتماد جزء خادمي لا يغلق شروط الواجهة أو الأمان التي لم تختبر. لا يعاد تنفيذ الجزء المعتمد لمجرد نقص جزء آخر؛ يعاد عند تغير مصدره أو ظهور ارتداد مرتبط.

## دليل القرار وحدوده

- إعادة تشغيل الحزم الخمس: **44 passed و3 failed و689 assertions**. الإخفاقات الثلاثة لنفس عيب TOTP في Admin وTenant path/domain، موضح أدناه. هذا تشغيل كامل بنتيجة فشل، وليس 47 اختبارًا ناجحًا.
- [مصدر اختبارات القبول المستقلة](evidence/site-auth/CodexBatchACriterionAcceptanceTest.php)، و[النتيجة الكاملة](evidence/site-auth/codex-criterion-acceptance-results.txt).
- مصدر 30 متغيرًا هو الاختبارات الجديدة. مصدر 8 متغيرات هو إعادة تشغيل [حزمة Batch A](../../tests/Feature/Auth/TwoFactorAuthenticationBatchATest.php) وفحص assertions فيها؛ كل اعتماد أدناه محدد بمداه الفعلي.
- [بصمات المصادر والأدلة](evidence/site-auth/codex-criterion-source-hashes.json)، وتشمل نسختي Fortify/Google2FA المثبتتين. التشخيص مبني على مصدرهما المحلي.
- طلبات HTTP تمر عبر Laravel kernel ومساراته الفعلية. معظم اختبارات challenge تبدأ بحقن pending؛ لا تثبت password step أو cookies/browser. تثبيت وقت TOTP يغير الساعة فقط، ولا يصطنع نتيجة التحقق. يلغى throttle في مصفوفات الرموز حتى لا تختلط نتيجته بالتحقق؛ اختبار 24 يبقيه فعالًا.
- الاتصالات SQLite معزولة، وbootstrapper قاعدة Tenant معطل في بعض اختبارات الخادم؛ اختبار 22 يستخدم قاعدتين مستقلتين فعليًا. لا يشمل القبول تشغيل MySQL حقيقيًا أو row locking تحت التزامن.
- فشل اختبار 24 أولًا بسبب اشتراك fixtures في عنوان IP واحد؛ عُزل كل عميل تجريبي بعنوان من 192.0.2.0/24. النتيجة النهائية نجاح 24 في النطاقات الثلاثة؛ لم يسجل ذلك كعيب إنتاج.
- متصفح Codex غير متاح (browser host/native pipe)، ودليل Gemini المرفق ما زال على خادم محاكاة؛ لذا لا اعتماد UI جديد. [التفاصيل والأدلة السابقة](site-auth-batch-a-round-3-review.md).
- لم يعدل Codex كود المنتج أو ملفات Gemini للأدوار والإدارات الجديدة، ولم يفتح مصدر الـtester. لم تتغير JS/CSS بهذه المراجعة؛ نتيجة البناء السابقة مؤرخة في تقرير الجولة الثالثة.

## معايير مغلقة فعلًا — Codex

المعرف هنا **متغير من الحالة الأصلية**، وليس إعادة تعريف لمعيار إغلاقها الكامل. F فحص خادمي وDOM فحص HTML الناتج فقط. لكل صف قرار قبول مستقل ومحدد؛ عدد الصفوف 38. ملفا الاختبار مذكوران أعلاه، وأسماء الدوال تجعل إعادة الفحص مباشرة.

| المتغير | قرار Codex | ما تم اعتماده تحديدًا | دالة الدليل |
| --- | --- | --- | --- |
| 2FA-03.DOM.ADMIN | `verified` | HTML الناتج: حقل code واحد وplaceholder بقيمة XXXXXX، وlabel/error مرتبطان، وrecovery معطل، دون otpHidden. | `test_2fa03_dom_single_code_label_and_error_contract_for_all_http_scopes` |
| 2FA-03.DOM.TENANT-PATH | `verified` | HTML الناتج: حقل code واحد وplaceholder بقيمة XXXXXX، وlabel/error مرتبطان، وrecovery معطل، دون otpHidden. | `test_2fa03_dom_single_code_label_and_error_contract_for_all_http_scopes` |
| 2FA-03.DOM.TENANT-DOMAIN | `verified` | HTML الناتج: حقل code واحد وplaceholder بقيمة XXXXXX، وlabel/error مرتبطان، وrecovery معطل، دون otpHidden. | `test_2fa03_dom_single_code_label_and_error_contract_for_all_http_scopes` |
| 2FA-09.F.ADMIN | `verified` | recovery الصحيح يدخل الحساب المقصود ويستهلك مرة؛ إعادته في pending جديد تعيد 422 بلا دخول. | `test_2fa09_backend_recovery_consumed_once_in_every_http_scope` |
| 2FA-09.F.TENANT-PATH | `verified` | recovery الصحيح يدخل الحساب المقصود ويستهلك مرة؛ إعادته في pending جديد تعيد 422 بلا دخول. | `test_2fa09_backend_recovery_consumed_once_in_every_http_scope` |
| 2FA-09.F.TENANT-DOMAIN | `verified` | recovery الصحيح يدخل الحساب المقصود ويستهلك مرة؛ إعادته في pending جديد تعيد 422 بلا دخول. | `test_2fa09_backend_recovery_consumed_once_in_every_http_scope` |
| 2FA-10.F.ADMIN | `verified` | رفض recovery خاطئ أو خاص بحساب آخر أو بمجموعة قديمة، دون استهلاك أكواد الحساب الآخر؛ المستهلك مغطى بفحص 09. | `test_2fa10_and_16_backend_regeneration_invalid_and_other_account_codes_in_all_http_scopes` |
| 2FA-10.F.TENANT-PATH | `verified` | رفض recovery خاطئ أو خاص بحساب آخر أو بمجموعة قديمة، دون استهلاك أكواد الحساب الآخر؛ المستهلك مغطى بفحص 09. | `test_2fa10_and_16_backend_regeneration_invalid_and_other_account_codes_in_all_http_scopes` |
| 2FA-10.F.TENANT-DOMAIN | `verified` | رفض recovery خاطئ أو خاص بحساب آخر أو بمجموعة قديمة، دون استهلاك أكواد الحساب الآخر؛ المستهلك مغطى بفحص 09. | `test_2fa10_and_16_backend_regeneration_invalid_and_other_account_codes_in_all_http_scopes` |
| 2FA-11.F.ADMIN | `verified` | رفض فارغ و5/7 أرقام وحروف وarrays واعتمادين؛ قبول اعتماد صحيح مع الحقل الآخر فارغًا. | `test_2fa11_backend_shape_matrix_and_empty_inactive_field_for_every_http_scope` |
| 2FA-11.F.TENANT-PATH | `verified` | رفض فارغ و5/7 أرقام وحروف وarrays واعتمادين؛ قبول اعتماد صحيح مع الحقل الآخر فارغًا. | `test_2fa11_backend_shape_matrix_and_empty_inactive_field_for_every_http_scope` |
| 2FA-11.F.TENANT-DOMAIN | `verified` | رفض فارغ و5/7 أرقام وحروف وarrays واعتمادين؛ قبول اعتماد صحيح مع الحقل الآخر فارغًا. | `test_2fa11_backend_shape_matrix_and_empty_inactive_field_for_every_http_scope` |
| 2FA-12.F-CURRENT-PAST.ADMIN | `verified` | نافذة 1: رفض ±2؛ قبول الحالي والسابق أول مرة ورفض إعادتهما. لا يشمل رمز +1. | `test_2fa12_current_past_boundary_expiry_and_replay_for_all_http_scopes` |
| 2FA-12.F-CURRENT-PAST.TENANT-PATH | `verified` | نافذة 1: رفض ±2؛ قبول الحالي والسابق أول مرة ورفض إعادتهما. لا يشمل رمز +1. | `test_2fa12_current_past_boundary_expiry_and_replay_for_all_http_scopes` |
| 2FA-12.F-CURRENT-PAST.TENANT-DOMAIN | `verified` | نافذة 1: رفض ±2؛ قبول الحالي والسابق أول مرة ورفض إعادتهما. لا يشمل رمز +1. | `test_2fa12_current_past_boundary_expiry_and_replay_for_all_http_scopes` |
| 2FA-16.F.ADMIN | `verified` | توليد 8 أكواد فريدة وإبطال القديم وقبول الجديد؛ السر ثابت وTOTP يعمل، وحساب آخر لا يتغير. | `test_2fa10_and_16_backend_regeneration_invalid_and_other_account_codes_in_all_http_scopes` |
| 2FA-16.F.TENANT-PATH | `verified` | توليد 8 أكواد فريدة وإبطال القديم وقبول الجديد؛ السر ثابت وTOTP يعمل، وحساب آخر لا يتغير. | `test_2fa10_and_16_backend_regeneration_invalid_and_other_account_codes_in_all_http_scopes` |
| 2FA-16.F.TENANT-DOMAIN | `verified` | توليد 8 أكواد فريدة وإبطال القديم وقبول الجديد؛ السر ثابت وTOTP يعمل، وحساب آخر لا يتغير. | `test_2fa10_and_16_backend_regeneration_invalid_and_other_account_codes_in_all_http_scopes` |
| 2FA-17.F.ADMIN | `verified` | تعطيل اختياري يمحو secret/confirmed/recovery؛ pending قديم لا يفتح جلسة. | `test_2fa17_backend_optional_disable_erases_secrets_and_rejects_old_pending_in_all_http_scopes` |
| 2FA-17.F.TENANT-PATH | `verified` | تعطيل اختياري يمحو secret/confirmed/recovery؛ pending قديم لا يفتح جلسة. | `test_2fa17_backend_optional_disable_erases_secrets_and_rejects_old_pending_in_all_http_scopes` |
| 2FA-17.F.TENANT-DOMAIN | `verified` | تعطيل اختياري يمحو secret/confirmed/recovery؛ pending قديم لا يفتح جلسة. | `test_2fa17_backend_optional_disable_erases_secrets_and_rejects_old_pending_in_all_http_scopes` |
| 2FA-19.F.ADMIN | `verified` | cancel يحذف مفاتيح pending الأربعة ويعيد login الصحيح؛ POST القديم مرفوض، ثم يدخل الحساب الجديد الصحيح. | `test_2fa19_backend_cancel_cleanup_old_post_and_new_account_for_all_http_scopes` |
| 2FA-19.F.TENANT-PATH | `verified` | cancel يحذف مفاتيح pending الأربعة ويعيد login الصحيح؛ POST القديم مرفوض، ثم يدخل الحساب الجديد الصحيح. | `test_2fa19_backend_cancel_cleanup_old_post_and_new_account_for_all_http_scopes` |
| 2FA-19.F.TENANT-DOMAIN | `verified` | cancel يحذف مفاتيح pending الأربعة ويعيد login الصحيح؛ POST القديم مرفوض، ثم يدخل الحساب الجديد الصحيح. | `test_2fa19_backend_cancel_cleanup_old_post_and_new_account_for_all_http_scopes` |
| 2FA-20.F.ADMIN | `verified` | غياب pending أو مستخدم محذوف أو سر تالف: رفض آمن بلا جلسة أو 500. لا يشمل انتهاء مهلة جلسة حقيقية. | `test_2fa20_backend_missing_context_deleted_user_and_corrupt_secret_for_all_http_scopes` |
| 2FA-20.F.TENANT-PATH | `verified` | غياب pending أو مستخدم محذوف أو سر تالف: رفض آمن بلا جلسة أو 500. لا يشمل انتهاء مهلة جلسة حقيقية. | `test_2fa20_backend_missing_context_deleted_user_and_corrupt_secret_for_all_http_scopes` |
| 2FA-20.F.TENANT-DOMAIN | `verified` | غياب pending أو مستخدم محذوف أو سر تالف: رفض آمن بلا جلسة أو 500. لا يشمل انتهاء مهلة جلسة حقيقية. | `test_2fa20_backend_missing_context_deleted_user_and_corrupt_secret_for_all_http_scopes` |
| 2FA-24.F-THROTTLE.ADMIN | `verified` | خمسة رموز خاطئة تعيد 422 والسادس 429؛ بعد 61 ثانية يعود 422 مع رفض الدخول. | `test_2fa24_backend_throttle_and_release_for_all_http_scopes` |
| 2FA-24.F-THROTTLE.TENANT-PATH | `verified` | خمسة رموز خاطئة تعيد 422 والسادس 429؛ بعد 61 ثانية يعود 422 مع رفض الدخول. | `test_2fa24_backend_throttle_and_release_for_all_http_scopes` |
| 2FA-24.F-THROTTLE.TENANT-DOMAIN | `verified` | خمسة رموز خاطئة تعيد 422 والسادس 429؛ بعد 61 ثانية يعود 422 مع رفض الدخول. | `test_2fa24_backend_throttle_and_release_for_all_http_scopes` |
| 2FA-15.F-GUEST.ADMIN | `verified` | Admin guest: settings إلى login؛ enable/disable/QR/recovery ترفض 401؛ tenant guard لا يفعّل Admin. confirm وTenant guest غير مشمولين. | `test_settings_endpoints_require_authentication_and_guard_boundaries` |
| 2FA-18.F-DISABLE.ADMIN | `verified` | رفض تعطيل 2FA عند فرضه بواسطة AdminSettings أو TenantSettings؛ لا اعتماد لمصفوفة company أو توجيه dashboard. | `test_disable_2fa_flow_and_policy_enforcement` |
| 2FA-18.F-DISABLE.TENANT-PATH | `verified` | رفض تعطيل 2FA عند فرضه بواسطة AdminSettings أو TenantSettings؛ لا اعتماد لمصفوفة company أو توجيه dashboard. | `test_disable_2fa_flow_and_policy_enforcement` |
| 2FA-21.F-GUARD.ADMIN-TO-TENANT-PATH | `verified` | مستخدمان فعليان ID=1؛ pending من guard آخر لا يفتح جلسة guard المستهدف. | `test_admin_and_tenant_session_isolation` |
| 2FA-21.F-GUARD.TENANT-PATH-TO-ADMIN | `verified` | مستخدمان فعليان ID=1؛ pending من guard آخر لا يفتح جلسة guard المستهدف. | `test_admin_and_tenant_session_isolation` |
| 2FA-22.F-DATABASES.TENANT-PATH-A-B | `verified` | قاعدتا SQLite مستقلتان وID=1 مع اختلاف PDO وبيانات المستخدمين؛ رفض تبديل سياق challenge دون استهلاك الأكواد. لا يشمل reset/domain. | `test_cross_tenant_isolation_rejected` |
| 2FA-23.F-INACTIVE.ADMIN | `verified` | حساب inactive وقت POST لا يدخل برمز صحيح؛ لا يشمل تغيير اشتراك أو سياسة بعد password. | `test_deactivated_admin_and_tenant_user_rejected_during_2fa` |
| 2FA-23.F-INACTIVE.TENANT-PATH | `verified` | حساب inactive وقت POST لا يدخل برمز صحيح؛ لا يشمل تغيير اشتراك أو سياسة بعد password. | `test_deactivated_admin_and_tenant_user_rejected_during_2fa` |

قبول 09 يشمل أيضًا نجاح اختباري Codex السابقين لرفض نسختين محملتين مسبقًا من Admin وTenant بعد استهلاك الأولى؛ لا يدعي إثبات تزامن HTTP في MySQL.

## قرار كل حالة أصلية وما بقي لإغلاقها

هذه طلبات محددة لإكمال القبول، وليست 19 حالة غير مراجعة. reopened قد يعني عيبًا مثبتًا أو رفض دليل لا يثبت المطلوب؛ السبب موضح لكل حالة. متغير DOMAIN-BROWSER يبقى blocked حتى توفر الوصول والدليل الفعلي.

| الحالة | القرار التجميعي | المتبقي فقط |
| --- | --- | --- |
| 2FA-01 | `reopened` | يلزم دليل password → challenge → Submit → guard/session → الوجهة عبر Laravel، في النطاقات الثلاثة؛ حقن pending في HTTP لا يثبت الرحلة كاملة. |
| 2FA-02 | `reopened` | اختبار Enter والنقر المزدوج وإعادة المحاولة في متصفح فعلي مع عد POST/events؛ harness الحالي لا يثبتها. |
| 2FA-03 | `partially_verified` | DOM معتمد؛ تبقى مشاهدة placeholder والمحاذاة عند 360/390/768/1440 على الواجهة الفعلية. |
| 2FA-04 | `reopened` | الكتابة وpaste/autofill وحفظ الصفر الأول وترتيب LTR والقيمة المرسلة في المتصفح. |
| 2FA-05 | `reopened` | لقطات وقياس يثبتان الرجوع يمين والتبديل يسار في السطر نفسه، في الوضعين والأحجام المطلوبة. |
| 2FA-06 | `reopened` | computed styles وhover/focus/keyboard للرابطين ومقارنتهما بمرجع auth-back-link. |
| 2FA-07 | `reopened` | التبديل بعد قيم وأخطاء سابقة، وتنظيف الحقل غير النشط ونقل focus وعدم تعطيل submit فعليًا. |
| 2FA-08 | `reopened` | رمز خاطئ → خطأ عربي مرئي → تصحيح ناجح دون reload، في TOTP/recovery؛ الدليل الحالي يسجل Invalid feedback displayed:false. |
| 2FA-09 | `partially_verified` | الاستهلاك وreplay عبر HTTP معتمدان؛ تبقى رحلة recovery ورسالتها بالواجهة. إعادة قراءة stale snapshots ناجحة؛ SQLite لا يثبت تزامن MySQL. |
| 2FA-10 | `partially_verified` | مصفوفة رفض recovery الخادمية معتمدة؛ تبقى الرسالة العربية المرئية وعدم تسريب الأكواد في UI/logs. |
| 2FA-11 | `partially_verified` | مصفوفة FormRequest عبر HTTP معتمدة؛ تبقى أخطاء الحقول والتبديل الفعلي بالواجهة. |
| 2FA-12 | `reopened` | A4-01/P0 مثبت: رمز TOTP من الخطوة التالية داخل window يعاد قبوله في pending ثانٍ؛ اعتمدت متغيرات الحالي والسابق وخارج النافذة فقط. |
| 2FA-13 | `reopened` | رحلة enable → QR حقيقي → confirm → reload → logout/login؛ إصلاح A3-01 في زر Tenant ثم إثبات المتصفح. |
| 2FA-14 | `reopened` | إصلاح ترتيب resetButtonLoading في Tenant؛ إعادة المحاولة بعد 422 أو network failure، وإغلاق modal أو reload قبل التأكيد ثم استكماله. |
| 2FA-15 | `partially_verified` | Admin guest/guard المحددان معتمدان؛ تبقى confirm للضيف وكل endpoints لضيف Tenant، وtampered context والحساب الآخر وتسريب الأسرار. |
| 2FA-16 | `partially_verified` | التوليد والإبطال وقبول الجديد وثبات TOTP معتمدة؛ يبقى عرض الأكواد للحساب الصحيح وحماية ظهورها في UI/logs. |
| 2FA-17 | `partially_verified` | التخزين ورفض pending قديم معتمدان؛ تبقى حالة UI بعد reload وpassword login جديد بلا challenge. |
| 2FA-18 | `partially_verified` | منع DELETE تحت AdminSettings/TenantSettings معتمد؛ تبقى company وتركيبات السياسة، وdashboard/setup/logout ومنع loops والعزل بين المستأجرين. |
| 2FA-19 | `partially_verified` | تنظيف pending وإعادة POST والحساب الجديد معتمدة؛ تبقى history/cache وremember cookie الفعلي من المتصفح. |
| 2FA-20 | `partially_verified` | غياب السياق والحذف والسر التالف معتمدة؛ تبقى جلسة منتهية فعليًا وCSRF ورسالة الواجهة وعدم بقاء loading. |
| 2FA-21 | `partially_verified` | رفض guard مختلف مع ID متطابق معتمد؛ تبقى cookies والتبويبات بالمتصفح نفسه والتداخل في الاتجاهين. |
| 2FA-22 | `partially_verified` | challenge بمسارين وقاعدتين مستقلتين معتمد؛ تبقى domain وtenant hint وreset وتوجيه النهاية والواجهة. |
| 2FA-23 | `partially_verified` | رفض inactive معتمد؛ يبقى تغيير الحالة بعد password فعليًا، والحذف وسياسة الوصول والاشتراك ومنع الصفحات المحمية قبل إعادة الفحص. |
| 2FA-24 | `partially_verified` | throttle وفك الحظر بالنطاقات الثلاثة معتمدان؛ تبقى remember وتجديد session ID وتنظيف pending والوجهة ورسالة 429 بالمتصفح. |

## A4-01 / 2FA-12 — إعادة قبول TOTP من النافذة التالية

**P0، فشل مثبت في Admin وTenant path وTenant domain.** النافذة الفعلية 1. في وقت T ثابت يولد رمز من T+1 ويقبل أول مرة ضمن نافذة السماح. بعدها يسجل الخروج ويبدأ pending جديد للحساب نفسه، ثم يعاد إرسال الرمز نفسه. المتوقع 422 وبلا auth؛ الفعلي auth=true مرة ثانية. اختبارات القبول الثلاثة الفاشلة تثبت ذلك على مسارات Laravel الفعلية دون الاعتماد على خادم محاكاة.

السبب المتتبع في المكتبات المثبتة:

1. `vendor/pragmarx/google2fa/src/Google2FA.php::findValidOTP` يعيد true بدل timestamp المطابَق عند أول تحقق مع oldTimestamp=null.
2. `vendor/laravel/fortify/src/TwoFactorAuthenticationProvider.php::verify` يحول true إلى timestamp الحالي T ويحفظه في cache، رغم أن الرمز المقبول يخص T+1.
3. في المحاولة الثانية تبدأ نافذة الفحص من T+1، فيقبل الرمز نفسه ويفتح جلسة جديدة. اختبار الحالي والسابق وحده لم يكشف ذلك.

**المطلوب من Gemini:** عالج حفظ الخطوة المقبولة الفعلية وحماية إعادة الاستخدام في تكامل التطبيق مع المزود، دون تعديل مباشر لـvendor. حافظ على نافذة السماح المقررة؛ لا تضيقها صامتًا لإخفاء الخلل. لا تغير الاختبارات الفاشلة لتقبل إعادة الاستخدام. أعد اختبار T-1/T/T+1 و±2 في كل نطاق، بقبول أول ثم خروج وبداية pending جديد. يبقى TOTP الحقيقي الحالي وrecovery والعزل ناجحين. إذا احتاجت الحماية حالة مخزنة، تلتزم باتصال المستخدم وحدود tenant/guard. لا يرسل Codex تكليفًا تلقائيًا.

أولوية الإصلاح المتبقية: **A4-01** ثم **A3-01** (زر Tenant)، ثم **A3-02** (دليل الواجهة على Laravel). بقية الجدول متطلبات إكمال قبول، ولا تستدعي إعادة تنفيذ الأجزاء المعتمدة.

## إعادة التشغيل

```powershell
php artisan test docs/qa/evidence/site-auth/CodexBatchAReviewTest.php docs/qa/evidence/site-auth/CodexBatchARoundTwoReviewTest.php tests/Feature/Auth/TwoFactorAuthenticationBatchATest.php tests/Feature/Auth/AdminLoginGuardIsolationTest.php docs/qa/evidence/site-auth/CodexBatchACriterionAcceptanceTest.php --colors=never
```

نتيجة الأمر: exit 1 بسبب اختبارات A4-01 الثلاثة. الملفات التشخيصية خارج الحزمة المعتادة عمدًا لحفظ دليل الرفض حتى الإصلاح.

