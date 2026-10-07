# سجل المرحلة 8 — المسارات والـLegacy

**تاريخ التدقيق:** 7 أكتوبر 2026 — **تحديث الحذف:** 8 أكتوبر 2026
**الحالة:** مكتملة؛ أزيلت مرشحات الملفات المحددة بعد موافقة المستخدم الصريحة.

## نطاق وطريقة الإثبات

يستعمل هذا السجل تعريفات المرحلة 3: `Active` لمسار محمّل، و`Inactive` لتعريف مقصود لا يسجله Laravel، و`Orphan candidate` لملف بلا مستهلك مثبت. لم يُحذف أي migration أو جدول أو بيانات.

تم الفحص في `app` و`routes` و`resources` و`tests` و`config` و`database` بواسطة `rg`، ثم شُغّل `php artisan route:clear` و`php artisan route:list --json`. لا تُظهر قائمة المسارات المحمّلة أياً من المتحكمات أدناه.

## قرارات المسارات المعلّقة

| النطاق | القرار | المالك | الدليل والسبب | موعد المراجعة |
|---|---|---|---|---|
| Super Admin: `transfer-requests` | Inactive / غير مسجل | Product + Super Admin | كانت المجموعة معلقة، ولا توجد route محمّلة لـ`TransferRequestController`. أزيلت declaration المعلقة و`use` فقط؛ بقي الكود والواجهات للتقييم التجاري. | قبل إحياء طلبات التحويل |
| Tenant: تغيير كلمة المرور القديم | Inactive / غير مسجل | Tenant Auth | المجموعة المعلقة أزيلت، ولا تظهر `PasswordChangeController` في route list. التدفق الحالي يستعمل مسارات المصادقة المعتمدة. | عند تغيير سياسة كلمات المرور |
| Tenant: إعدادات `contact-results` المستقلة | Inactive / غير مسجل | Tenant Settings | لا route محمّلة لـ`ContactResultController`. تظل service/model/seeders لأن نتائج الاتصال جزء من عمليات العملاء، وليس هذا دليلاً على حذفها. | قبل إعادة شاشة الإعدادات |
| Tenant: تقارير العقود والمحافظ وأداء الشركات والمستخدمين | Inactive / غير مسجل | Tenant Reports | لا route محمّلة للمتحكمات الأربعة. توجد views/services/DataTables ذات صلة، لذلك لا يجوز حذفها قبل قرار product وربط routes واختبارات جديدة. | قبل إحياء كل تقرير أو نهاية 2026-Q4 |
| Site: `coming-soon` | محذوف | Website | أزيل route المعلّق ثم الصفحة غير المستخدمة في 8 أكتوبر 2026 بعد الموافقة الصريحة. | لا يوجد |
| Tenant: `DomainController@checkSubDomain` القديم و`ActivityLogController@downloadFile` | Inactive / غير مسجل | Domains / Activity Logs | أزيل التعليق فقط. توجد بدائل/مسارات أخرى للمجال والسجل؛ لم يحذف أي controller أو method. | عند تدقيق وحدتي Domain وActivity Logs |

لا توجد compatibility routes بلا مالك أو موعد مراجعة في هذه القائمة. أما alias النشط `admin.plans.reorder` فهو موثق في سجل المرحلة 5 ولا يدخل ضمن الحذف.

## الملفات القديمة المحذوفة

| الملف/الملفات | دليل عدم الاستهلاك | القرار المنفذ |
|---|---|---|
| `SubscriptionManagementController.php.orig` | امتداد `.orig` غير قابل للـautoload، والـcontroller القياسي هو المحمل في route نشط. | حُذف؛ النسخة التاريخية محفوظة في Git. |
| `Tenant.php.orig`, `Subscription.php.orig`, `Plan.php.orig` | لا مراجع تشغيلية للامتداد `.orig`، والـmodels القياسية هي الفعالة. | حُذفت؛ النسخ التاريخية محفوظة في Git. |
| `DashboardService-2-2-2025.php` | لا مرجع للاسم؛ تعتمد الخدمات النشطة `DashboardService.php`. | حُذف؛ أزيل خطر تعريف class مكرر في classmap مستقبلي. |
| `__MoyasarClient.php`, `__MoyasarCallbackHandler.php` | لا import أو FQCN لهما؛ تدفقات الدفع تستخدم النسخ غير المسبوقة بـ`__`. | حُذفا؛ لم تتغير بوابة الدفع الفعالة. |
| `resources/assets/js/site/trial-validation.js`, `resources/assets/js/site/trial/trial-validation.js` | لا entrypoint أو مرجع source؛ التحقق الفعلي عبر المحرك المشترك و`trial.js`. | حُذفا. |
| `resources/views/site/coming-soon.blade.php` | لا route أو `view()` reference بعد إزالة declaration المعلقة. | حُذفت. |

## ما نُفّذ في هذه المرحلة

1. أزيلت declarations المعلّقة وimports غير المستخدمة المقابلة من `routes/admin/admin.php` و`routes/tenant/tenant.php` و`routes/site/site.php`.
2. لم تتغير routes النشطة أو التواقيع أو الجداول أو migrations.
3. ثُبّتت نتيجة route list: لا تظهر متحكمات Transfer Requests أو Contact Results أو التقارير المعلّقة أو Password Change القديم في التسجيل المحمّل.
4. في 8 أكتوبر 2026 أُعيد فحص المراجع وتأكد أن الملفات العشرة tracked في Git ولا يستهلكها التطبيق، ثم حُذفت بموافقة المستخدم الصريحة.
5. نجح `npm run build`، ونجح 32 اختبار Unit (1,544 assertion)، ونجحت اختبارات Website/Checkout/Dashboard المتأثرة: 23 اختباراً و122 assertion.
