# سجل المرحلة 9 — الاختبارات وقبول التشغيل

**تاريخ التدقيق:** 7 أكتوبر 2026 — **تحديث قبول محلي:** 8 أكتوبر 2026
**الحالة:** مغلقة محلياً؛ سجل التشغيل الفعلي في [phase-9-local-smoke-register-2026-10-08.md](phase-9-local-smoke-register-2026-10-08.md).

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

## تغطية الـsmoke المحلية المنفذة

نفذت البنود القابلة للاختبار في البيئة المحلية عبر HTTP وChromium headless وsessions حقيقية للحسابات المزروعة، مع اختبارات Feature/Unit للـvalidation والعزل. التفاصيل والنتائج في [سجل smoke المحلي](phase-9-local-smoke-register-2026-10-08.md). تبقى [`manual-smoke-checklist.md`](manual-smoke-checklist.md) مرجعاً لإعادة التشغيل في أي بيئة جديدة.

عند إعادة التنفيذ في بيئة أخرى، يسجل المنفذ التاريخ والبيئة والنتيجة لكل قسم، وبالأخص:

1. Website: Contact وTrial وCheckout/Callback في وضع الاختبار فقط.
2. Super Admin: خطة الباقات، المسودات، الصلاحيات، الجداول والحذف المؤكد.
3. Tenant isolation: path/domain tenancy ومنع تجاوز Tenant A إلى Tenant B.
4. Assignments/Properties/Auctions: السلسلة والعلاقات والملفات والأرشيف.
5. عمليات الدفع والدومينات: callback idempotency وعدم وجود mutation عبر GET.

## حدود قبول البيئة المحلية

- لا يساوي هذا القبول المحلي تأكيد عملية مالية أو DNS حي في production.
- لا تدخل مفاتيح production أو العمليات المالية الحية ضمن هذا السجل.
- اكتمل شرط المرحلة 9 للبدء في المرحلة 10 داخل بيئة التطوير المحلية.
