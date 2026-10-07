# سجل المرحلة 9 — الاختبارات وقبول التشغيل

**تاريخ التدقيق:** 7 أكتوبر 2026
**الحالة:** مكتملة تقنياً محلياً؛ قبول المتصفح والتكاملات الخارجية يتطلب منفذاً بشرياً وبيئة الخدمات.

## ما تم تشغيله محلياً

| الفحص | النتيجة | الدليل |
|---|---|---|
| Unit contracts | ناجح: 32 اختباراً و1,548 assertion | يشمل عقد DataTables، التحقق، الصلاحيات، tenant auth، HMAC، وعقد routes/legacy الجديد. |
| Feature tests على MySQL | ناجح: 89 اختباراً و1,219 assertion | شُغلت الملفات على مجموعات قصيرة بسبب حد التنفيذ، وتشمل الإدارة المركزية، الشركات والاشتراكات، Website، tenancy، CRUD، dashboards، profile/users، وموديولات Assignments/Properties/Auctions. |
| إجمالي PHPUnit | ناجح: 121 اختباراً و2,767 assertion | مجموع Unit وFeature أعلاه؛ لا توجد حالة متروكة من suite المشروع. |
| تحقق ما بعد حذف legacy في المرحلة 8 | ناجح في 8 أكتوبر 2026 | 32 اختبار Unit (1,544 assertion) و23 Feature test للموقع/Checkout/Dashboard (122 assertion) بعد إزالة الملفات غير المستخدمة. |
| Route compilation | ناجح | `php artisan route:cache` نجح، ثم `route:list --json`، ثم `route:clear` لإعادة البيئة إلى وضعها المعتاد. |
| PHP syntax | ناجح | `php -l` لملفات routes المعدلة. |
| Frontend build | ناجح | `npm run build` نجح. بقيت تحذيرات أصول static/fonts الموجودة مسبقاً؛ Vite أبقاها runtime URLs ولم يحوّلها إلى أخطاء. |
| Route cleanup proof | ناجح | لا توجد `Route::` معلقة في ملفات routes، ولا تظهر متحكمات الوظائف المؤرشفة في route list. |

## تغطية الـsmoke المطلوبة قبل الإصدار

قائمة التنفيذ هي [`manual-smoke-checklist.md`](manual-smoke-checklist.md)، وقد حدّثت لتشمل Assignments → Properties → Auctions وأرشيفاتها. هذه البنود لا تدّعي النجاح تلقائياً، لأنها تحتاج متصفحاً وهوية مستخدمين وخدمات reCAPTCHA/Moyasar/Namecheap أو بدائلها في البيئة المستهدفة.

يجب أن يسجّل المنفذ التاريخ والبيئة والنتيجة لكل قسم، وبالأخص:

1. Website: Contact وTrial وCheckout/Callback في وضع الاختبار فقط.
2. Super Admin: خطة الباقات، المسودات، الصلاحيات، الجداول والحذف المؤكد.
3. Tenant isolation: path/domain tenancy ومنع تجاوز Tenant A إلى Tenant B.
4. Assignments/Properties/Auctions: السلسلة والعلاقات والملفات والأرشيف.
5. عمليات الدفع والدومينات: callback idempotency وعدم وجود mutation عبر GET.

## حدود القبول

- لا يساوي نجاح PHPUnit أو Vite تأكيد عمل بوابة دفع أو مزود DNS حي.
- لا تدخل مفاتيح production أو العمليات المالية الحية ضمن هذا السجل.
- لا تبدأ المرحلة 10 في هذه الدفعة قبل اعتماد نتيجة الـsmoke المناسبة لبيئة الإصدار.
