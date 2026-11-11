# حصر مداخل تسجيل دخول العميل

تاريخ المراجعة: 2026-11-11.

على مضيفات النظام المركزية، صفحة الدخول والنموذج يستخدمان `/{tenant}/login`، مثل `/test/login`. المسار `path.login` هو المصدر الأساسي، و`tenant_route_name('login')` يختار نسخة المسار المناسبة للمضيف الحالي ويضيف معرّف العميل. يشمل ذلك المضيف الأساسي ونسخة `www`؛ يحتفظ الـhelper بنفس ترتيب المضيفات المستخدم عند تسجيل المسارات.

## التحويلات والإجراءات

| الإجراء | المصدر | النتيجة بعد المراجعة |
| --- | --- | --- |
| دخول زائر إلى صفحة محمية للعميل | `app/Http/Middleware/Authenticate.php` → `redirectTo` | التحويل إلى `/{tenant}/login` مع حفظ الرابط المقصود |
| الرجوع للدخول من مسار يبدأ بـ `admin` مع سياق عميل معروف | نفس الـmiddleware | بناء `path.login` بمعرّف العميل؛ وعند غياب السياق يرجع للرئيسية |
| فتح رابط الدخول المشترك القديم | `app/Http/Controllers/Tenants/Auth/AuthenticatedSessionController.php` → `create` | `GET /admin/login` يحوّل إلى `/{tenant}/login` بعد التحقق من سياق العميل، مع الاحتفاظ برسائل الجلسة |
| تسجيل الخروج | نفس الـcontroller → `destroy` / `redirectAfterLogout` | الرجوع إلى `/{tenant}/login` |
| إنهاء جلسة بسبب تسجيل الدخول من جهاز آخر | `app/Http/Middleware/Tenancy/SingleSessionPerUser.php` | الرجوع إلى `/{tenant}/login` |
| حساب شركة محذوف | `app/Http/Middleware/Tenancy/CheckSubscription.php` | إنهاء الجلسة ثم `/{tenant}/login` |
| عدم وجود اشتراك | نفس الـmiddleware | إنهاء الجلسة ثم `/{tenant}/login` |
| اشتراك قيد التفعيل | نفس الـmiddleware | إنهاء الجلسة ثم `/{tenant}/login` |
| انتهاء التجربة لمستخدم غير المالك | نفس الـmiddleware | إنهاء الجلسة ثم `/{tenant}/login` |
| تعليق الاشتراك دون فاتورة متاحة | نفس الـmiddleware | إنهاء الجلسة ثم `/{tenant}/login` |
| انتهاء الاشتراك لمستخدم غير المالك | نفس الـmiddleware | إنهاء الجلسة ثم `/{tenant}/login` |
| إلغاء الاشتراك | نفس الـmiddleware | إنهاء الجلسة ثم `/{tenant}/login` |
| فتح تحدّي التحقق الثنائي دون تحدٍّ صالح | `app/Http/Controllers/Tenants/Auth/Fortify/TenantTwoFactorChallengeController.php` → `create` | فرعا غياب التحدّي أو المستخدم يرجعان إلى `/{tenant}/login` |
| إلغاء تحدّي التحقق الثنائي | نفس الـcontroller → `cancel` | الرجوع إلى `/{tenant}/login` |
| نجاح إعادة تعيين كلمة المرور | `app/Http/Controllers/Tenants/Auth/NewPasswordController.php` → `store` | الرجوع إلى `/{tenant}/login` |
| تغيير كلمة المرور الذي يستلزم إعادة الدخول | `app/Http/Controllers/Tenants/UserMangment/User/UserController.php` → `updatePassword` | الرجوع إلى `/{tenant}/login` |
| إتمام الدفع وإنشاء الاشتراك | `app/Http/Controllers/Site/SubscriptionController.php` → `handlePaymentCallback` | `redirectToTenantLogin` يستخدم معرّف العميل الناتج لإنشاء `/{tenant}/login` |
| إعادة callback لفاتورة مدفوعة بعد استثناء تحقق | نفس الـcontroller | نفس المسار المحدد للعميل |
| استثناء عام بعد تسجيل الفاتورة كمدفوعة | نفس الـcontroller | نفس المسار المحدد للعميل |

## الروابط والنماذج والإيميلات

| المدخل | المصدر | النتيجة |
| --- | --- | --- |
| إرسال نموذج الدخول | `resources/views/tenant/auth/login.blade.php` | `POST /{tenant}/login`؛ الحقل المخفي يستخدم العميل المحدد في المسار |
| رابط «لديك حساب» في التسجيل | `resources/views/tenant/auth/register.blade.php` | `tenant_route_name('login')` → `/{tenant}/login` |
| العودة للدخول من نسيان كلمة المرور | `resources/views/tenant/auth/forgot-password.blade.php` | نفس المسار |
| العودة للدخول من إعادة تعيين كلمة المرور | `resources/views/tenant/auth/reset-password.blade.php` | نفس المسار |
| زر الدخول في شريط التنقل العام للعميل | `resources/views/tenant/layouts/sections/navbar/navbar-front.blade.php` | نفس المسار |
| زر الدخول للشركة في إدارة طلبات التجربة | `resources/views/admin/public-requests/trial/show.blade.php` | `path.login` بمعرّف عميل الطلب |
| رابط الدخول في رسالة الترحيب بمستخدم العميل | `app/Services/Tenants/UserManagement/User/UserService.php` → `create` ثم `UserWelcomeMail` | `tenant_route_name('login')` → `/{tenant}/login` |
| رابط الدخول في رسالة الترحيب بالشركة، بما فيها الموافقة على التجربة | `app/Services/Shared/Mail/SystemMailService.php` → `sendTenantWelcome` ثم `TenantWelcomeMail` | `Tenant::getLoginUrl()` يستخدم `path.login` → `/{tenant}/login`؛ زر الرسالة والرابط النصي متطابقان |
| رسالة الاشتراك القديمة عبر Microsoft | `app/Services/Admin/Microsoft/Mail/MailService.php` → `NewSubscribtionMail` | يبني بالفعل `https://lot.sa/{tenant}/login` |
| رسالة التجديد القديمة عبر Microsoft | نفس الخدمة → `RenewSubscriptionMail` | نفس المسار الصحيح |
| رسالة الترقية القديمة عبر Microsoft | نفس الخدمة → `UpgradeSubscriptionMail` | نفس المسار الصحيح |

رابط الدخول الموجود داخل تعليق Blade في `footer-front.blade.php` أصبح يستخدم نفس الـhelper أيضًا. رابط القالب التجريبي `/auth/login-cover` داخل تعليق شريط التنقل لا يُعرض ولا يُنفّذ.

`SystemMailService::sendTrialRequestApproved` و`TrialRequestApprovedMail` يستقبلان رابطًا من المستدعي؛ لم يظهر لهما مستدعٍ فعّال يبني رابط دخول مستقلًا. التدفق الحالي للموافقة على التجربة يرسل `TenantWelcomeMail` المذكور أعلاه.

مصادر Microsoft القديمة أعلاه مدرجة لاستكمال الحصر؛ تدفق إرسال رسائل الاشتراك الحالي يستخدم `SystemMailService`.

## الروابط الداخلية المحمية

رسائل `NewSubscriptionMail` و`RenewSubscriptionMail` و`BankTransferApprovedMail` تفتح `/{tenant}/dashboard`، ورسالة `UpgradeSubscriptionMail` تفتح `/{tenant}/users`. عند عدم وجود جلسة، يحوّل `Authenticate` الزائر إلى `/{tenant}/login` ويحتفظ بالوجهة الأصلية للعودة إليها بعد الدخول.

## التوافق وحدود المراجعة

- لم يعد أي رابط أو تحويل مولّد في كود التطبيق يستهدف `/admin/login`؛ ظهوره في كود التطبيق مقتصر على تعريف مسارات التوافق واسمها في فحص الـcontroller.
- الرابط القديم دون عميل معروف، أو مسار عميل غير موجود، يعيدان `404` بدل عرض نموذج دخول مشترك.
- مسار `POST /admin/login` القديم ما زال يقبل الطلبات القديمة بعد التحقق من العميل، لكن النماذج الحالية لا ترسل إليه.
- دخول المشرف العام مستقل ويستخدم `/superadmin/login`.
- التوافق الموجود مسبقًا مع مضيف مخصص لعميل يستخدم `/login` على ذلك المضيف؛ التغيير هنا يخص مداخل العملاء على مضيفات النظام المركزية.
- شمل البحث التطبيق والـroutes والـmiddleware والـBlade وإيميلات النظام وJavaScript المملوك للتطبيق وإعدادات المصادقة والـbootstrap؛ مكتبات الواجهة المضمّنة وملفات البناء لا تمثل مصدر روابط التطبيق.

## التحقق

اختبارات معزولة بقواعد SQLite مؤقتة وجلسات database وكوكيز متصفح حقيقية تغطي عرض النموذج وإرساله، المضيفات المركزية البديلة، الرابط القديم، غياب العميل، الخروج، استبدال الجلسة، إلغاء التحقق الثنائي، وتحويل callback الدفع دون الاتصال ببوابة دفع. اختبارات المصادقة الأخرى تغطي عزل جلسات المشرف والعميل والتحقق الثنائي وتغيير كلمة المرور.

الاختبارات الأقدم التي تعتمد قاعدة بيانات التطوير حُدّثت توقعاتها للمسار الجديد؛ لم تُشغّل ضمن التحقق المعزول.

- مجموعة المصادقة المعزولة: **79 ناجحًا، 7 متخطّية، 603 assertions**. التخطي الموجود مسبقًا يخص سياسة فرض التحقق الثنائي المحذوفة.
- إعادة اختبار المضيفات ببيئة `APP_CENTRAL_DOMAIN=login.test` و`APP_URL=http://login.test`: **اختبار ناجح، 6 assertions** تشمل `login.test` و`www.login.test`.
- فحص بناء PHP للملفات المعدلة و`npm run build`: ناجحان.
- فحص Pint أظهر مخالفات سابقة في خمسة ملفات موجودة؛ إعادة الفحص على نسخها من `HEAD` أظهرت نفس المخالفات. لم يُعد تنسيقها خارج نطاق هذا التغيير.
