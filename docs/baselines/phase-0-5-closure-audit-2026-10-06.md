# تدقيق إغلاق المراحل 0–5 قبل Tenant

**التاريخ:** 6 أكتوبر 2026  
**القرار:** **مُعتمدة تقنياً للانتقال إلى المرحلة 6.**

هذا السجل يثبت ما تم تشغيله فعلياً بعد مراجعة التعديلات، ولا يساوي بين ما اختُبر آلياً وما يحتاج دخولا يدوياً. لا تشمل هذه البوابة أي عمل جديد على Moyasar، تنفيذاً لقرار مالك المنتج بإخراجه من النطاق الحالي.

## ملخص الإغلاق

| المرحلة | النتيجة | دليل الإغلاق |
|---|---|---|
| 0 — Baseline والمسارات | مكتملة | قائمة المسارات تعمل، و`route:cache` ينجح ثم يُمسح، وحُذف مسار `dashboard/collection-sessions-page` اليتيم بعد فحص مراجع PHP وBlade وJS؛ يثبت الاختبار أنه `404`. |
| 1 — الأمن والعزل | مكتملة ضمن النطاق | اختبارات HMAC/replay/legacy window تمر، واختبارات صفحات الأخطاء والعزل Central/Tenant تمر. |
| 2 — عقود UI | مكتملة آلياً | بناء Vite ناجح، والعقود المشتركة في tokens/forms/buttons قائمة. يلزم فقط المرور البصري اليدوي المعتاد قبل الإصدار. |
| 3 — Forms وWizard | مكتملة كأساس مشترك | `SharedFormValidation` يستدعي FormRequest الحقيقي، يعرض `is-valid`/`is-invalid` ومخرج خطأ قابل للوصول، ويحفظ/يستعيد المسودات دون كلمات مرور. |
| 4 — Website | مكتملة | Trial وCheckout وContact تستخدم endpoint موحداً؛ Contact اختُبر HTTP ضد `StoreContactRequest`. |
| 5 — Super Admin | مكتملة | لا يبقى `Request::validate()` داخل Controllers الإدارية النشطة؛ العمليات المنقولة تستخدم FormRequests، وتغطي اختبارات قبول الحذف الجماعي والإعدادات والتذاكر و2FA. |

## ما أُنجز في هذه المراجعة

### المسارات والعزل

- أزيل تعريف route `dashboard.collectionSessionsPage` ومرجعه في Dashboard؛ لم يكن لـ`DashboardController` method مقابل ولا consumer فعلي.
- أصبح التسجيل على أكثر من central domain قابلاً لـ`route:cache`: يحتفظ أول domain بأسماء routes الرسمية، وتحمل aliases في domains الإضافية prefix فريداً (`central-domain-{n}.`). لا تتغير أسماء routes الرسمية التي يستعملها التطبيق.
- تحققت بنجاح عملية `route:cache` ثم `route:clear`، ولذلك لا يظل cache اختبار في مساحة العمل.

### التحقق والنماذج

- Contact انتقل من قواعد JavaScript مكررة إلى `StoreContactRequest` عبر `site.form-validation`؛ يبقى reCAPTCHA تكامل إرسال فقط.
- endpoint التحقق يقبل `store` و`update` افتراضياً، ويقبل action مختلفاً فقط عندما يعلن route صراحة `shared_form_validation`. المسارات المعلنة هي Contact وTrial وCheckout فقط.
- endpoint يطابق host وbindings لمسار الهدف، فيعمل مع central domain الصحيح بدلاً من إنشاء Request داخلي يفقد الـhost.
- المحرك المشترك لا يخفي رسالة Laravel موجودة بعد إعادة تحميل الصفحة، ويستعمل مخرجاً واحداً `data-form-error` مع `aria-live`.
- حُولت كل قواعد الإدارات المركزية الموجودة داخل Controllers إلى FormRequests، بما فيها: البنوك، امتدادات النطاق، التذاكر، تحديث/رفض/العمليات الجماعية للاشتراكات، فحوصات التفرد، login، تأكيد 2FA، والحذف الجماعي للمستخدمين.

### تدقيق Stage 5

- `AdminMassDeleteController` يبقى نقطة الحذف الجماعي الوحيدة بقائمة بيضاء وصلاحية إنجليزية لكل model.
- حالات DataTable الإدارية التي تمثل نشاطاً تستخدم toggle المشترك؛ حالات الاشتراك/الفواتير workflow وليست boolean activity، لذلك لا تتحول زوراً إلى toggle.
- صفحة Website Contact والصفحات الإدارية المحولة تتبع عقد الحقول المشترك. ملفات FormValidation الموجودة في Tenant هي backlog صريح للمرحلة 6؛ أما imports vendor الباقية في صفحات Admin لا تستدعي المحرك القديم كتحقق form، وتدخل إزالة payload غير المستخدم في تنظيف المرحلة 8.

## أدلة إعادة التشغيل

| الأمر/المجموعة | النتيجة |
|---|---|
| `npm run build` | ناجح. التحذيرات الوحيدة أصول موجودة مسبقاً غير محلولة وقت البناء: `saudi-riyal.png` وخطوط Zain. |
| `php artisan view:cache` | ناجح. |
| `php artisan route:cache` ثم `route:clear` | ناجح. |
| `php artisan test --testsuite=Unit` | **22 passed، 1,204 assertions**. |
| `WebsiteFormValidationFeatureTest` | **2 passed، 6 assertions**. |
| اختبارات Super Admin المعدلة | **4 passed، 61 assertions**: bulk delete، banks/domains، ticket/bill actions، 2FA/auth. |
| Central/Error/Tenant/Wizard acceptance | **42 passed، 374 assertions**. تشمل العزل المركزي، صفحات الأخطاء، CRUD Tenant، Dashboard، المسودات، وWebsite validation. |

## حدود معلنة وليست فشلاً للبوابة

1. لم يُشغّل اختبار Moyasar ولم تُعدّل له وظائف جديدة؛ هو خارج النطاق بطلب صريح، وأي إعادة ربط له تحتاج قراراً مستقلاً وبيانات اختبار جديدة.
2. ملفات FormValidation القديمة داخل `resources/assets/js/tenants` مرتبطة بوحدات Tenant وستعالج وحدةً بوحدة في المرحلة 6. الملفات المنفصلة غير المستوردة في Website وimports vendor غير المستخدمة في Admin تدخل تنظيف المرحلة 8 فقط بعد إثبات عدم الاستهلاك والموافقة.
3. يلزم تنفيذ `manual-smoke-checklist.md` في متصفح وعلى بيئة متصلة بخدماتها قبل النشر الإنتاجي. هذا فحص إصدار نهائي في المرحلة 9، وليس مانعاً لبدء تنفيذ المرحلة 6.
4. `git diff --check` ما زال يرصد ثلاثة أسطر فارغة في نهاية ملفات SCSS معدلة سابقاً (`_alerts.scss` و`_buttons.scss` و`_cards.scss`). لم تُمس لأنها تعديلات خارج هذا الإغلاق ولا تؤثر في البناء.

## قرار المرحلة التالية

يُسمح ببدء المرحلة 6 من وحدة Tenant الأولى: **Authentication, Profile, Users, Teams, Roles, Branches**. ويظل شرط كل وحدة: FormRequest هو المصدر الوحيد للقواعد، والمحرك المشترك هو واجهة التحقق، واختبارات route/HTTP الخاصة بها تمر قبل الانتقال للوحدة التالية.
