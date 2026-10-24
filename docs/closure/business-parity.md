# مطابقة الأعمال — فجوات مثبتة ونطاق التدقيق
مرجع: [الجرد](../dashboard-legacy-inventory.md) و[manifest](../baselines/golden-pattern-manifest.md). التاريخ: 2026-10-23.

## فجوات مشاهدة مباشرة
| ID | الدليل | الأثر المطلوب تصحيحه | الاختبار المطلوب |
|---|---|---|---|
| W02 | CheckoutController::completePayment يحذف checkout session ثم يعلن نجاح التفعيل دون إنشاء/تحقق دفع؛ callback مجرد redirect | تأكيد مزود موثوق وفاتورة/provisioning وعدم تكرار قبل إعلان نجاح | CheckoutProvisioningTest / PaymentIdempotencyTest |
| X01 | AppServiceProvider::Gate::before يعيد bool لكل ability؛ app/Policies مفقود | policies على السجلات دون bypass/short-circuit | AdminAuthorizationTest / TenantAuthorizationTest |
| T09 | TenantBillDataTable::getStats يستدعي Admin Bill مباشرة؛ Bill بلا اتصال مركزي ثابت | gateway/central connection صريح للquery والstats | CentralGatewayIsolationTest |
| T09 | CentralBillingGateway::submitBankTransferReceipt يستخدم lockForUpdate دون transaction | قفل داخل transaction على نفس central connection | BankTransferConcurrencyTest |
| T11 | CentralTicketGateway يرجع Builder ويحسب stats خارج central context؛ Ticket بلا اتصال مركزي ثابت | تنفيذ queries في اتصال مركزي موثوق | CentralGatewayIsolationTest |
| A10 | Bill::casts يحول status إلى BillStatus؛ UpdateBillStatusRequest يستخدمه كمفتاح array | تطبيع Enum إلى value والتحقق من انتقالات enum الفعلية المعتمدة | UpdateBillStatusRequestTest |
| X06 | AdminUserSeeder وDashboardDemoSeeder يزرعان حسابات password ويحدثان كلمات المرور عند seed | حصر demo seed في local/testing، bootstrap آمن منفصل للإنتاج | ProductionSeederSafetyTest |
| A02 | Tenant soft deletes مقترن بمسار TenantDeleted→DeleteDatabase في provider | تدقيق توقيت event وتجربة archive/restore على DB اختبار؛ لا إسقاط DB tenant عند أرشفة قابلة للاستعادة | TenantArchiveDatabaseLifecycleTest |
| W03 | `apply-coupon.js` يطلب `/api/central/coupon/validate-coupon` و`remove-coupon` بينما لا يوجد routes/api.php أو endpoint مقابل؛ CheckoutController وInitiateCheckoutPaymentAction يسعران الفاتورة من `plan->price_after_discount` ولا يطبقان coupon_code | quote مركزي خادمي من plan الموثوق، قيد الصلاحية/الحد/max_discount، snapshot بالمبلغ على Bill/PaymentAttempt، وزيادة used_count مقفلة فقط عند التسوية الناجحة | CouponConcurrencyTest / CheckoutProvisioningTest |

## أعمال لم تثبت مطابقتها بعد
كل صف unverified في manifest يتطلب مقارنة old route/controller/request/service/view مع النسخة الحالية. لا تعتبر أي عملية غير موجودة في التقرير السابق خارج النطاق. خصوصًا Tenant API token/domains/licenses/subscription purchase-renew-upgrade وأرشيف users/auctions، وفلاتر وأعمدة إدارة العميل الكاملة.
لا يعتمد جدول انتقال حالات تخميني؛ يقارن legacy enum وخدمة المجال والschema الحالية قبل كتابة migration أو تغيير workflow.

## قاعدة ترحيل البيانات
إذا كانت هناك بيانات قديمة فعلية، يلزم trial import وreconciliation للأعداد والمبالغ والعلاقات والملفات مع backup مستعاد تجريبيًا. لا توجد موافقة هنا على حذف أو تصفير قواعد قائمة.

