# تقرير تدقيق النظام — الحالة الحالية

> تاريخ التقرير: 5 أكتوبر 2026  
> نطاقه: قراءة فنية للكود الحالي بعد تعديلات اليوم، دون تعديل في منطق التطبيق.  
> ملاحظة: لم يتسن تشغيل الفحص التنفيذي الكامل للـroutes محلياً بسبب عدم توفر اتصال MySQL المحلي؛ حالات الاستخدام أدناه مبنية على الربط الثابت في الكود.

## الصورة المعمارية العامة

| الطبقة | النطاق | قاعدة البيانات |
|---|---|---|
| Website | الدومين المركزي | مركزية |
| Super Admin | `/admin` على الدومين المركزي | مركزية |
| Tenant | `/{tenant}/...` أو دومين tenant | قاعدة مستقلة لكل Tenant |
| API | `/api/...` | مركزية أو Tenant حسب الـmiddleware |

نظام الـtenancy يبدل اتصال MySQL الافتراضي عند فتح Tenant إلى قاعدة بياناته الخاصة. تظل بيانات المنصة مثل الباقات والاشتراكات والفواتير والـtenants والدومينات في القاعدة المركزية.

## أولاً: Website

### الصفحات والـroutes المستخدمة

| الصفحة | Route | الوظيفة |
|---|---|---|
| الرئيسية | `/` | عرض الباقات والعملاء والأعمال ونموذج التواصل |
| سياسة الخصوصية | `/privacy-policy` | صفحة ثابتة |
| تفاصيل الاشتراك | `/checkout/{plan}/details` | مراجعة الباقة والبيانات قبل الدفع |
| معالجة الاشتراك | `POST /checkout/{plan}` | إنشاء فاتورة وبدء الدفع أو التحويل البنكي |
| Callback الدفع | `/checkout/callback` | التحقق من Moyasar وإكمال الاشتراك |
| تجربة مجانية | `/trial` | نموذج طلب تجربة |
| إرسال التجربة | `POST /trial` | إرسال الطلب للخدمة الخارجية |

توجد صفحة `coming-soon`، لكن route الخاص بها معلّق، ولذلك فهي غير مستخدمة.

### الـlayout وأجزاء الصفحات

الـlayout الرئيسي هو `site.layouts.main` ويشمل:

- `head`، `header`، محتوى الصفحة، `footer`، و`script`.
- Toastr، page loader، وزر الرجوع للأعلى.
- الصفحة الرئيسية تحتوي أقسام Hero، تعريف المنصة، المميزات، العملاء، الأعمال، الباقات، والتواصل.

معظم هذه الأقسام مكتوب كـBlade يدوي؛ المكوّنات المستخدمة فعلياً في الموقع محدودة في `x-ui.package-card` و`x-shared.toastr`.

### قاعدة البيانات وتدفق العمل

- الصفحة الرئيسية تقرأ من `plans` للباقات النشطة، و`clients` للعملاء النشطين، و`portfolios` للأعمال النشطة.
- التواصل والتجربة يتحققان بـFormRequest وreCAPTCHA على الخادم، ثم يرسلان البيانات لخدمة Etmam خارجية، ويُرسل بريد عبر Queue عند النجاح.
- الاشتراك ينشئ Bill وينفذ الدفع عبر Moyasar أو التحويل البنكي؛ callback يستكمل الاشتراك وإنشاء/استكمال Tenant.

### الـrequests والـresponses

- النماذج العادية تعيد Redirect مع flash message.
- checkout يمر عبر CouponService وBillService وخدمات الدفع والاشتراك.
- لا يوجد API response contract عام لموقع الويب؛ الأسلوب السائد هو Redirect ورسائل نجاح/خطأ.

### ملاحظات Website

1. الصفحة الرئيسية تتحقق من `is_active` للباقات، لكن لا يظهر أنها تطبق إتاحة الباقة الزمنية مثل `available_from`.
2. حساب ضريبة checkout يحتاج توثيقاً business واضحاً: هل السعر المخزن شامل الضريبة أم لا؟
3. يوجد تحقق JavaScript قديم ومتكرر لصفحات الدفع والتجربة؛ لم تكتمل هجرته بعد إلى `shared/form-validation.js`.
4. يمكن لاحقاً تحويل header/footer وأقسام الصفحة الرئيسية وcheckout fields إلى Components موحدة.

## ثانياً: Super Admin

### الـlayout والأجزاء المشتركة

لوحة الإدارة تعتمد `admin/layouts`، وتشمل `layoutMaster` و`contentNavbarLayout` و`horizontalLayout` و`blankLayout` و`layoutFront` و`print`، إضافة إلى navbar/menu/footer/scripts/styles.

تستخدم الإدارة DataTables وComponents مشتركة مثل:

- `x-form.controls.*` و`x-form.wizard.*`.
- `x-filter.select` و`x-data-table.filters`.
- `x-actions.*`.
- `x-admin.dashboard-card` و`x-admin.dashboard-panel`.

### الإدارات وتأثيرها

| الإدارة | الصفحات/الوظائف | الجداول المركزية الأساسية |
|---|---|---|
| Dashboard | لوحة وإحصاءات ونشاط المنصة | قراءة من عدة جداول |
| المستخدمون والأدوار | list/create/edit/profile/archive | `admin_users` وroles/permissions |
| الشركات / Tenants | list/create/show/edit/delete | `tenants`, `company_settings`, `cities` |
| الباقات | list/create/edit/show/reorder/archive | `plans` |
| الكوبونات | CRUD/toggle/archive | `coupons` |
| الاشتراكات | list/create/toggle/delete | `subscriptions`, `subscription_periods`, `license_seats` |
| تنبيهات الاشتراكات | pending/expiring/expired/accept/reject | الاشتراكات والفواتير |
| الفواتير | list/show/print/verify/reject/reactivate | `bills`, `moyasar_transactions`, `banks` |
| إعدادات المنصة | عامة، بنوك، دومينات | `settings`, `banks`, `control_domains`, `domains` |
| الدعم الفني | list/show/reply/status | `tickets`, `ticket_replies`, `ticket_sections` |
| الإصدارات | CRUD/toggle/testers | `versions`, `version_testers` |
| إدارة محتوى الموقع | العملاء والأعمال | `clients`, `portfolios` |
| الأرشيف | استرجاع/حذف نهائي | الجداول السابقة مع Soft Deletes |

### الـrequests والـresponses

- صفحات CRUD تستخدم GET للعرض وPOST/PUT للحفظ، وغالباً Redirect مع flash message.
- DataTables تعمل عبر Yajra responses.
- إجراءات AJAX مثل uniqueness checks وترتيب الباقات وtoggle تعيد JSON.
- الباقات هي أكثر إدارة تم توحيدها حالياً: wizard من خمس خطوات، مسودات، معاينة، وتحقيق من FormRequest الفعلي.

### routes غير المفعلة أو ملاحظات مهمة

1. إدارة Transfer Requests موجودة كـController/Service/Views/DataTable، لكن كل routes الخاصة بها معلّقة؛ غير قابلة للوصول حالياً.
2. يوجد routeان لترتيب الباقات (`update-sort-order` و`reorder`) يستدعيان نفس method؛ يجب لاحقاً تحديد route الرسمي والإبقاء على الآخر كـcompatibility أو حذفه.
3. `admin.mass-delete` يستخدم MassDeleteController من مساحة Tenant؛ هذا خلط بين الحدود المركزية وحدود الـtenant ويحتاج فصلاً لاحقاً.
4. ليست كل الإمكانيات المسجلة في الـroutes ظاهرة بوضوح في القائمة الجانبية؛ يلزم مراجعة navigation قبل أي توسع.
5. عدة صفحات لا تزال تستخدم validation files قديمة منفصلة.

## ثالثاً: Tenant

### الدخول والحماية

الـTenant يعمل عبر path tenancy أو domain tenancy، ويخضع لـ:

- `auth:tenant` و2FA.
- `tenant.subscription` و`tenant.license` للوظائف المحمية.
- `tenant.maintenance`.
- عزل اتصال قاعدة البيانات لكل Tenant.

### الإدارات والصفحات والجداول

| الإدارة | الصفحات/الوظائف | الجداول الأساسية |
|---|---|---|
| Dashboard | إحصاءات، follow-ups، جلسات تحصيل، أداء، موافقات | قراءة من العملاء والتحصيل والمهام |
| المستخدمون | مستخدمون، profile، صور، حالة، كلمة مرور | `users`, `sessions` |
| الفرق | CRUD وربط مستخدمين | `teams` |
| الأدوار والصلاحيات | CRUD وربط roles بالمستخدمين | جداول Spatie |
| الاشتراكات والفواتير | اشتراك، تجديد، تراخيص، دفع وتحويل | مركزياً: `subscriptions`, `bills`, `license_seats` |
| الدومينات | تسجيل، نقل، ربط، DNS، CPanel، تسعير، دفع | مركزياً: `domains`, `cpanel_accounts`؛ و`transfer_domains` داخل Tenant |
| الشركات | CRUD وتفاصيل | `companies`, `company_authorized_people`, `company_customer_status` |
| العقود | CRUD، توليد رقم، عمولات وديون | `contracts`, `contract_commissions`, `contract_debt_types` |
| المحافظ | CRUD وحقول مخصصة | `portfolios`, `portfolio_fields` |
| العملاء | CRUD، هواتف، حقول، استيراد، توزيع، bulk update | `customers`, `customer_phones`, `customer_field_values`, `customer_distributions`, `import_sessions` |
| المتابعات | follow-ups ونتائج اتصال وحسابات مرتبطة | `customer_follows`, `contact_results` |
| التحصيل | تحصيلات، موافقات، أهداف، جلسات، استيراد، تسوية | `collections`, `customer_transactions`, `collection_targets`, `collection_process_sessions` |
| خطط السداد والتسويات | CRUD | `customer_payment_plans`, `customer_settlements` |
| الرسائل والمراسلات | SMS وقوالب وسجلات وخطابات | `customer_sms`, `message_templates`, `message_logs`, `message_individual_logs`, `company_letters` |
| الملاحظات والتعليقات والمرفقات | AJAX CRUD | `customer_notes`, `customer_comments`, `customer_comment_mentions`, `customer_attachments` |
| طلبات تحديث البيانات | عرض واعتماد ورفض وتصدير | `customer_update_requests` |
| الإعدادات العامة | دول وطرق دفع وتواصل وحالات وقوالب | جداول `settings_*` |
| التقارير | العملاء والمتابعات والمالية والشركات والموظفون والتنفيذية | قراءة وتجميع من جداول Tenant |
| التوفيق المالي | رفع وتحليل ومتابعة وتقارير | `payment_reconciliations`, `payment_reconciliation_items` |
| الدعم | تذاكر وردود وتقييم | مركزياً: `tickets`, `ticket_replies` |
| الإشعارات والسجل والأرشيف | عرض وحذف واسترجاع | `notifications`, `activity_log` والجداول المؤرشفة |

### أجزاء غير مفعلة أو legacy

هذه الأجزاء موجودة في المشروع لكن routes الخاصة بها معلقة:

- إدارة `contact-results` المستقلة.
- تقارير العقود والمحافظ وأداء الشركات والمستخدمين.
- route قديم لتغيير كلمة المرور.

ملفات legacy مرشحة للتقييم قبل الإزالة:

- `SubscriptionManagementController.php.orig`.
- Models بامتداد `.orig`.
- `DashboardService-2-2-2025.php`.
- ملفات Moyasar تبدأ بـ`__`.
- DataTables مرتبطة بوظائف أو تقارير غير مربوطة حالياً.

لا ينبغي حذف هذه الملفات مباشرة؛ يجب أولاً البحث عن استدعاءاتها من JavaScript وJobs وConsole Commands والواجهات الخارجية، ثم وضعها في deprecation list.

### ملاحظات Tenant

1. مساحة الـTenant كبيرة جداً: أكثر من 500 تعريف route، وتحتاج لاحقاً إلى modularization تدريجي حسب business domain.
2. كثير من الـforms والواجهات ما زال custom مع JavaScript validation منفصل.
3. بعض domain routes تستدعي Controllers تحت namespace `Admin` مثل Moyasar وAPILayer؛ يفضل نقل المنطق المشترك إلى `Shared`.
4. توجد عمليات حالة/دفع تقبل GET وPOST معاً داخل domain routes؛ يجب أن تكون العمليات المؤثرة POST/PUT/DELETE فقط.
5. routes تبديل الحالة في إعدادات الدول وطرق الدفع والتواصل تشير إلى `edit` بدلاً من method مخصص؛ هذه نقطة مرشحة لخلل وظيفي.
6. navigation الحالية لا تعرض بوضوح كل الوحدات الكبيرة مثل العملاء والعقود والتحصيل والتقارير؛ تحتاج مراجعة Information Architecture.

## التحقق الأمامي والمكوّنات

### النظام الموحد الموجود

المسار الموحد يعتمد:

- `resources/assets/js/shared/form-validation.js`.
- `x-form.controls.*`.
- `x-form.layout.field`.
- `x-form.wizard.*`.
- endpoint `wizard-drafts/validate-step`.

هذا النظام يأخذ FormRequest كمصدر الحقيقة، ويعرض الخطأ في مكان واحد، ويضيف/يزيل `is-invalid` و`is-valid`، ويدير حالة Next والمسودات.

### وضع التغطية الحالي

- نحو 10 views فقط تحتوي `data-form-validate`.
- نحو 72 view ما زالت تحمل مكتبات Form Validation قديمة.
- يوجد عدد كبير من ملفات validation المنفصلة في Website وAdmin وTenant.

بالتالي: الأساس الموحد موجود ويعمل، لكن migration لم يكتمل ولا يجوز اعتبار كل الـforms موحدة بعد.

### فرص التوحيد

- تحويل forms اليدوية تدريجياً إلى `x-form.controls.*`.
- توحيد Actions وstatus toggles وmodal confirmations.
- استخراج أجزاء layouts المتشابهة بين Admin وTenant إلى base/shared layer.
- توحيد JSON contracts للـAJAX، imports، exports وprogress polling.
- توسيع DataTable components بدلاً من التهيئة المتكررة لكل صفحة.

## API والـresponses

### APIs الحالية

- التحقق المركزي من الكوبونات.
- عرض عملاء Tenant عبر token.
- endpoint لترقية migrations الخاصة بالـtenant.

### ملاحظة أمنية عالية الأولوية

`POST /api/tenants/{tenantId}/upgrade` ينفذ migrations للـtenant اعتماداً على `X-Secret-Key` فقط.

قبل اعتماده إنتاجياً ينبغي إضافة authentication أو HMAC، rate limit صريح، audit log، منع التوازي، عدم كشف exception messages، وتشغيل العملية عبر Job إذا طال التنفيذ.

## ملاحظات عامة قبل المرحلة القادمة

1. الـException handler العام يعرض صفحات أخطاء Tenant حتى لطلبات Website أو Super Admin؛ يجب اختيار صفحة الخطأ حسب السياق.
2. يوجد تنوع في response patterns: Redirect + flash، JSON success/data، DataTables، وdownloads/progress. يلزم contract واضح لكل نوع.
3. DataTables مستخدمة جيداً في كثير من القوائم، لكن ليس كل جدول يستخدم نفس filters/status toggle/components.
4. قوة النظام الحالية في: عزل قواعد البيانات، FormRequests، Services، Jobs، DataTables، والصلاحيات. أكبر خطر حالي هو عدم اكتمال التوحيد فوق هذه الطبقات.

## ترتيب العمل المقترح

1. إعداد inventory رسمي للـroutes: active، legacy، compatibility، deprecated.
2. إنهاء migration للـforms إلى shared validation والمكونات المشتركة، module-by-module.
3. توحيد layouts المشتركة بين Admin وTenant دون كسر الهوية الخاصة بكل لوحة.
4. توحيد responses وAJAX actions وstatus toggles.
5. إغلاق أو نقل الكود غير المربوط بالـroutes بعد فترة deprecation.
6. مراجعة أمنية للـAPI upgrade وdomain routes التي تقبل GET للعمليات المؤثرة.
7. إعادة ترتيب Tenant navigation قبل إضافة وظائف جديدة.
