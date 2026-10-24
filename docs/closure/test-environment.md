# بيئة التحقق الآمنة
- phpunit.xml يحدد SQLite :memory: افتراضيًا ويجبر APP_ENV=testing وTENANCY_DB_PREFIX=levan_test_tenant_ وDB_URL فارغًا.
- Tests\\TestCase يفحص config بعد bootstrap وقبل تنفيذ traits مثل RefreshDatabase. يرفض config cache واسم default/central/template غير الآمن.
- MySQL/MariaDB integration مسموح فقط باسم levan_test_central أو اسم worker مطابق levan_test_central_1. يجب إعداد قاعدة اختبار وصلاحياتها صراحة. لا يقبل etmam_levan ولا ملف SQLite التطوير.
- يستخدم .env.testing.example كنقطة بداية؛ APP_KEY يولد محليًا دون إدراجه في Git. القيم الفعلية في .env.testing متجاهلة.
- لا تشغّل migrate:fresh أو tenants:migrate من CLI اعتمادًا على هذه الحماية؛ guard يحمي harness فقط، وأوامر CLI تتطلب بيئة اختبار معزولة ومراجعة targets.
- أي تغيير للconnection أثناء test أو override لاسم tenant DB يحتاج تحققًا مستقلًا؛ الحماية الأولية لا تدعي مراقبة كل config mutation لاحقة.
- إعدادات caches/queues/session الحالية array/sync مخصصة للاختبارات. اختبار queue/Redis الحقيقي يحتاج بيئة معزولة منفصلة قبل الإطلاق.
- لا suite كاملة شغلت في هذه الدفعة وفق قرار المستخدم؛ اختبارات guard مؤلفة وتنتظر بوابة التشغيل. لا يوجد ادعاء RED→GREEN.

