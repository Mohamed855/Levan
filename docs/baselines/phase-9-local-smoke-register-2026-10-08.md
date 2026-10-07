# سجل تنفيذ Smoke المحلي — المرحلة 9

**التاريخ:** 8 أكتوبر 2026  
**البيئة:** Local (`APP_ENV=local`، قاعدة بيانات `etmam_levan`)  
**الحالة:** ناجح — اعتماد محلي للمرحلة 9.

## ما تم تشغيله فعلياً

| النطاق | الفحص | النتيجة |
|---|---|---|
| Website | `/` و`/privacy-policy` و`/trial` | HTTP 200، مع تحميل الصفحة العامة في Chromium headless. |
| Super Admin | `/admin/login` ثم الدخول بالحساب المحلي المزروع | HTTP 200 ثم الوصول إلى `/admin` وعرض لوحة القيادة. |
| Super Admin | صفحات plans/companies/subscriptions/users بعد الدخول | HTTP 200. |
| Tenant path tenancy | `/test/login` ثم الدخول بحساب Tenant المزروع | HTTP 200 ثم الوصول إلى `/test/dashboard`. |
| Tenant domain tenancy | `test.localhost/login` | HTTP 200 وCSRF form contract حاضر. |
| Tenant isolation | مستخدم غير مسجل إلى `/test/assignments`، ومستأجر غير موجود | 302 إلى صفحة الدخول، ثم 404 للمستأجر غير الموجود. |
| Tenant modules | assignments/properties/auctions/users/companies/customers بعد الدخول | HTTP 200. |
| DataTables | Admin users وTenant assignments/auctions بطلب AJAX | JSON يتضمن `data` في الحالات الثلاث. |
| 2FA | صفحة إعداد 2FA للإدارة بعد الدخول | HTTP 200 ومحتواها ظاهر. |
| Validation | Trial بلا بيانات | redirect validation متوقع إلى `/trial`. |
| Validation | Super Admin login | أضيف route `admin.form-validation` وعقد النموذج؛ اختبار invalid payload يعيد 422 وحقلَي `email` و`password`. |

## الاختبارات الآلية المرافقة

- `AdminAuthenticationValidationFeatureTest` و`TenantAuthenticationValidationFeatureTest` و`WebsiteFormValidationFeatureTest`: **6 passed، 18 assertions**.
- Unit contracts: **33 passed، 1,548 assertions**.
- `route:cache` ثم `route:clear`: نجحا.

## حدود هذا القبول

هذا سجل قبول للبيئة المحلية، وهو المطلوب قبل بدء المرحلة 10. لا يمثل إثباتاً لنشر production أو transaction حقيقي لدى مزود خارجي؛ تلك مسؤولية release مستقلة عند فتح بيئة production.
