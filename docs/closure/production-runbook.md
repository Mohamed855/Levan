# Runbook النشر والترحيل — Levan

هذه وثيقة تشغيل؛ ليست سجل تنفيذ ولا تخول تشغيل أي أمر على قاعدة بيانات المستخدمين.

## قبل النشر

- ثبّت نسخة Git محددة واحتفظ بنسخة من `composer.lock` ونتيجة بوابة التحقق النهائية.
- خذ backup قابلًا للاستعادة لكل قاعدة مركزية وقواعد المستأجرين والـobject storage. اختبر الاستعادة في بيئة معزولة قبل نافذة النشر.
- جهز أسرار البيئة خارج المستودع: `APP_KEY`، قاعدة البيانات، mail، Moyasar، Namecheap، ومفاتيح التشفير القديمة إن وجدت. لا تضع قيمها في `.env.example` أو التذاكر أو السجل.
- اضبط `APP_ENV=production` و`APP_DEBUG=false` و`APP_URL` وHTTPS/proxy/trusted hosts وفق بيئة النشر.
- تحقق من grants: مستخدم التطبيق يملك فقط صلاحيات قاعدة البيانات المركزية وقواعد المستأجرين المطلوبة؛ worker لا يملك صلاحيات إدارية أوسع.
- جهز storage خاصًا لمرفقات الإيصالات والمسودات، وstorage عامًا منفصلًا للأصول المسموح بعرضها. لا تجعل disk `local` متاحًا عبر web server.

## rehearsal على staging معزول

نفّذ الخطوات التالية في staging فقط، بعد تسمية قواعد الاختبار صراحةً وفحص النسخ الاحتياطي:

1. `composer install --no-dev --prefer-dist --optimize-autoloader`
2. `npm ci` ثم `npm run build`
3. `php artisan migrate --force` للـcentral database فقط.
4. `php artisan tenants:list` ثم `php artisan tenants:migrate --force` بعد مراجعة قائمة المستأجرين المستهدفة.
5. شغّل seed للبيانات المرجعية الآمنة فقط، بعد مراجعة seeder وبيئة التشغيل. لا تشغّل demo seed في production.
6. `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`.
7. أعد تشغيل queue workers وراقب scheduler؛ نفذ smoke tests منفصلة لحساب admin وحسابين tenant وموقع public.
8. تحقق من الدفع في sandbox فقط، ومن إرسال البريد ومن DNS verification دون شراء نطاق أو تحصيل حي.

يُمنع `migrate:fresh` و`tenants:migrate-fresh` و`db:wipe` وseed غير مراجع على أي قاعدة تحتوي بيانات مستخدمين.

## نشر تدريجي

1. فعّل maintenance window إن كان migration غير متوافق مع النسخة السابقة.
2. أوقف workers بأمان، وانشر الكود والـassets، ثم نفذ migrations المركزية أولًا وقواعد tenant على دفعات مراقبة.
3. بعد كل دفعة، راقب logs وfailed jobs وconnection errors وعدادات الفواتير/الاشتراكات.
4. شغّل caches ثم workers/scheduler.
5. نفّذ smoke tests: login/2FA، DataTable AJAX، receipt upload، ticket reply، PDF export من snapshot، callback sandbox، API token rotate، domain TXT verification.

## rollback والاستعادة

- أوقف writes والworkers قبل rollback.
- لا تعمل rollback تلقائيًا لـmigration غير قابلة للعكس أو دفعة payment. استعد backup في بيئة معزولة أولًا.
- ارجع الكود إلى النسخة السابقة فقط إذا كانت schema متوافقة؛ وإلا استخدم migration تصحيحي forward-only.
- صادق أرصدة الفواتير وPaymentAttempt والاشتراكات قبل إعادة queue. أي payment paid مع provisioning غير مكتمل يعالج عبر مسار reconciliation، لا بإنشاء فاتورة/tenant يدوي مكرر.
- لا تحذف ملفات المسودات أو الإيصالات أثناء rollback؛ اربط cleanup بسياسة retention موثقة.

## ترحيل بيانات legacy

لا يوجد تفويض هنا لاستيراد بيانات حية. قبل أي import: نسخة backup، fixture معزول، mapping للroles/permissions والحالات والمبالغ والملفات، وreconciliation للأعداد والإجماليات. لا يقبل import قبل مطابقة tenant-by-tenant وقياس records غير القابلة للنقل.
