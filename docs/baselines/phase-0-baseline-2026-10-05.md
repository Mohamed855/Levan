# Baseline المرحلة 0 — 5 أكتوبر 2026

> مرجع التنفيذ: [`../system-unification-implementation-plan-ar.md`](../system-unification-implementation-plan-ar.md)  
> الحالة: **سجل baseline تاريخي — أُغلق التحقق التشغيلي اللاحق**.
> نتيجة هذا القياس محفوظة كما كانت في تاريخها؛ أُثبت لاحقاً تشغيل الـroutes والـcache والاختبارات في سجل [مواءمة إغلاق المراحل 1–6](phase-1-6-closure-reconciliation-2026-10-07.md). لا يخلط هذا الملف بين خط الأساس التاريخي وبين قبول الإصدار الإنتاجي، الذي يبقى من اختصاص المرحلة 9.

## 1. طريقة القياس

تم القياس من source code الحالي باستخدام بحث ثابت ومحدود في ملفات routes وBlade وJavaScript وPHP. لا يتم اعتبار عدادات `Route::` عدداً نهائياً للـHTTP routes، لأنها تشمل route groups وresource definitions؛ التعداد النهائي يتطلب `php artisan route:list`.

## 2. بنية الملكية والمسارات

| النطاق | ملف الإدخال | المالك التقني | قاعدة البيانات الافتراضية | ملاحظة |
|---|---|---|---|---|
| Website | `routes/site/site.php` | `Site` controllers/services | مركزية `mysql` | محصور في landing/trial/contact/checkout |
| Super Admin | `routes/admin/admin.php` و`admin-auth.php` | `Admin` controllers/services | مركزية `mysql` | محمي بـadmin auth/active/2FA |
| Tenant | `routes/tenant/tenant.php` و`collection-process.php` و`tenant-auth.php` | `Tenants` controllers/services | اتصال Tenant الديناميكي | يسجل عبر path وdomain tenancy |
| API | `routes/api.php` | `Api` وshared controllers | حسب middleware | coupon مركزي، API tenant token، upgrade |

### عدادات declarations الثابتة

| الملف | عدد أسطر `Route::...` غير المعلقة |
|---|---:|
| `routes/site/site.php` | 10 |
| `routes/admin/admin.php` | 117 |
| `routes/tenant/tenant.php` | 522 |
| `routes/tenant/collection-process.php` | 15 |
| `routes/api.php` | 7 |

### ملاحظات route مهمة قبل أي تعديل

- routes الـTenant نفسها تسجل مرتين: path tenancy وdomain tenancy. لا تستخدم عداد `tenant.php` وحده لتقرير العدد النهائي للـroutes.
- يوجد 17 declaration بصيغة `match(GET, POST)` في وحدة الدومينات. لا يعني ذلك أن السبعة عشر كلها mutations؛ يلزم تصنيفها إلى display/query/callback/mutation قبل تغيير methods.
- تم تحويل ثلاث mutations مثبتة الاستهلاك عبر نماذج POST إلى POST-only في بداية المرحلة 1: معالجة دفع الدومين، معالجة نقل الدومين، وربط DNS. يسجل التفصيل في [`phase-1-security-register.md`](phase-1-security-register.md).
- API upgrade هو `POST /api/tenants/{tenantId}/upgrade` ويعتمد حالياً على `X-Secret-Key` فقط.
- routes معلّقة وغير مسجلة حالياً تشمل Transfer Requests وإدارة Contact Results وبعض تقارير Tenant.

## 3. Baseline طبقات التطبيق

| المورد | العدد |
|---|---:|
| Controllers | 130 |
| FormRequests | 74 |
| DataTables | 60 |
| Blade views | 401 |
| ملفات الاختبارات | 14 |
| Feature tests | 10 |

## 4. Baseline الـforms والـUI

| المقياس | القيمة | التفسير |
|---|---:|---|
| views تستخدم `data-form-validate` | 10 | التغطية الموحدة ما زالت جزئية |
| views تستورد Form Validation قديم أو validation JS | 72 | تدخل في migration المرحلي، لا تحذف دفعة واحدة |
| ملفات JS باسم validation | 22 | يتضمن الملف المشترك؛ 21 ملفاً مرشحاً للمراجعة أو الاستبدال |
| استخدامات `x-form.controls.*` | 134 | المكوّنات مستخدمة لكنها ليست القاعدة في كل forms |
| Blade views مرشحة لوجود controls يدوية بلا عقد form واضح | 21 | قياس heuristic على مستوى الملف، ويستلزم مراجعة control-by-control قبل اتخاذ قرار |

### views المعلَّمة حالياً بالنظام الموحد

- `admin/admin_mangment/users/create.blade.php`
- `admin/admin_mangment/users/edit.blade.php`
- `admin/companies/create.blade.php`
- `admin/companies/edit.blade.php`
- `admin/coupons/create.blade.php`
- `admin/coupons/edit.blade.php`
- `admin/plans/create.blade.php`
- `admin/plans/edit.blade.php`
- `tenants/users_management/users/create.blade.php`
- `tenants/users_management/users/edit.blade.php`

### ملفات validation المنفصلة التي تدخل جرد المرحلة 3

- Website: payment/trial validation scripts.
- Admin: client، tenant management، versions، version testers.
- Tenant: companies، contracts، customers، portfolios، collections، targets، contact results، templates، support/tickets، teams، users.

القرار لكل ملف لا يكون الحذف مباشرة؛ يسجل أولاً كمستبدل أو كاستثناء موثق بعد فحص الـFormRequest والـconsumer الفعلي.

## 5. نتيجة التحقق التشغيلي

### `php artisan route:list --json`

- **الحالة:** فشل قبل توليد القائمة.
- **السبب:** `QueryException` عند الاتصال بـMySQL المحلي `127.0.0.1:3306` وقاعدة `etmam_levan`.
- **أول نقطة استدعاء ظاهرة:** `MicrosoftSettingsService` يحاول قراءة cache key `levan-cache-settings.microsoft` من قاعدة البيانات.
- **التفسير:** عائق إعداد بيئة التطوير، وليس دليلاً على خلل في route definitions.

### `php artisan test --compact`

- **النتيجة:** 8 اختبارات ناجحة، و69 فاشلة، مع 1057 assertions منفذة.
- **سبب الفشل السائد:** نفس عدم الاتصال بـMySQL المحلي قبل الوصول إلى منطق الاختبار؛ يظهر في اختبارات Admin وTenant وWizard Draft.
- **الحكم:** لا تستخدم هذه النتيجة لتقييم regression الحالي. أعد التشغيل بعد توفير قاعدة اختبار قابلة للاتصال أو تهيئة `.env.testing` مستقلة.

## 6. حزمة التحقق المطلوبة لرفع حالة المرحلة 0 إلى مكتملة تشغيلياً

1. توفير MySQL development/testing صالح أو إعداد SQLite صالح إن كانت migrations والتعددية تدعمانه.
2. تشغيل `php artisan route:list --json` وحفظ الناتج كـartifact خارج Git أو داخل مجلد baseline متفق عليه.
3. تشغيل `php artisan test --compact` وتسجيل النتائج.
4. مراجعة أي اختلاف بين الـroute list الفعلي والجرد الثابت، خصوصاً routes المسجلة مرتين للـTenant.
5. ربط كل Active route بمالك module واختبار آلي أو بند smoke checklist.

## 7. قرار المرحلة

- **يسمح بالبدء في المرحلة 1 (تحليل الأمان والعزل) عبر القراءة والتوثيق.**
- **لا يسمح باعتبار الـbaseline التشغيلي ناجحاً أو تنفيذ حذف legacy** قبل إتاحة قاعدة الاختبار وتشغيل القائمة والاختبارات بنجاح.
