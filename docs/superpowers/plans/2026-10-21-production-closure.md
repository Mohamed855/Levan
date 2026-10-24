# خطة التنفيذ الصارمة لإقفال LOT / Levan
> للمنفذ: تنفذ المهام بالترتيب باستخدام executing-plans، مع تحديث سجل الأدلة. لا تعاد كتابة وحدة موجودة لمجرد اختلاف أسلوبها.

**Goal:** إغلاق كامل نطاق النقل المعتمد وظيفيًا ومعماريًا وأمنيًا، وإثبات جاهزية نسخة محددة للنشر.
**Architecture:** Laravel Modular Monolith؛ Admin مركزي وTenant معزول بقاعدة مستقلة؛ Actions للكتابة وGateways لعبور الحدود؛ Yajra server-side DataTables للقوائم.
**Tech Stack:** Laravel 12، PHP وفق composer.lock، stancl/tenancy، Yajra، Blade، Bootstrap، jQuery، Vite.
**Spec:** [خطة النقل الأصلية](../../dashboard-migration-plan.md)، [جرد الأعمال](../../dashboard-legacy-inventory.md)، [عقد DataTables](../../architecture/datatable-contract.md)، وAGENTS.md في جذر المشروع.
**تاريخ إعداد الخطة:** 2026-10-21. الأرقام التالية baseline للمراجعة السابقة وليست شهادة جاهزية: 223 route؛ 35 ملف DataTable منها طبقة abstract؛ 34 view تستدعي table/scripts؛ 17 ملف اختبار؛ صفر ملفات Policies آنذاك.
**الحالة الابتدائية:** تنفيذ واسع موجود؛ الإقفال غير مثبت. لا تعتمد عبارة 100% في التقرير السابق كدليل.

## القيود الحاكمة
- النقل يغير structure والتنظيم والواجهة مع حفظ جميع متطلبات الأعمال والحقول والفلاتر والصلاحيات والحالات والعلاقات والعمليات ودورة الملفات الواردة في الجرد وold/.
- الاستثناء المعتمد: مصمم القوالب المرئي وعقد مخرجات المزاد كما هو موضح في خطة النقل الأصلية. أي استثناء آخر يسجل كقرار أعمال قبل التنفيذ.
- لا reset/checkout --/clean، ولا تعديل vendor، ولا حذف old أو ملفات المستخدم أو قاعدة فعلية. تحفظ كل التعديلات القائمة وتفحص قبل العمل.
- جميع مفاتيح DB والصلاحيات والحالات إنجليزية؛ الترجمات في lang. العلاقات Role::adminUsers وtenantUsers منفصلة.
- كل mutation: FormRequest + authorization على السجل + Action/Service؛ لا اعتماد على إخفاء الزر.
- DataTables شرط أساسي لكل قائمة تشغيلية. لا pagination Blade بديل، ولا اعتبار وجود class إثباتًا لتشغيل endpoint.
- لا APIs أو أسرار وهمية، ولا رسالة نجاح قبل تحقق الأثر. مفاتيح البيئة الفعلية لا تدخل الكود أو التقرير.
- قرار المستخدم المستمر: تؤلف الاختبارات اللازمة مع التنفيذ، وتشغل المجموعة الكاملة في مرحلة التحقق النهائية بعد إتمام النقل. قبلها syntax/routes/views/build وفحوصات قراءة فقط؛ لا يكتب المنفذ RED→GREEN دون تشغيله فعليًا.
- migrations وseed على قواعد اختبار معزولة مسماة صراحة؛ migrate:fresh أو tenants:migrate على قواعد قائمة ممنوعان بلا تحقق وقرار صريح.
- بعد أي JS/CSS/SCSS: npm run build. لا commit لتغييرات المستخدم ولا push/deploy ضمن هذا التفويض.
- لا جدول أسابيع مفترض. الانتقال حسب دليل اجتياز البوابة.

## Review Focus
1. Gate::before قد يتجاوز Policy أو يرفضها قبل التنفيذ: اختبارات مستخدم صاحب صلاحية مع سجل غير مملوك له.
2. Builder مركزي ينفذ بعد مغادرة central context، أو tenant فارغ: اختبار Query/Stats/DataTable بهويتين منفصلتين.
3. دفع أو طلب تجربة يتكرر/يفشل منتصف provisioning: لا حساب أو اشتراك أو فاتورة مكررة ولا رسالة نجاح كاذبة.
4. ملف/مسودة/أرشيف من Tenant آخر أو cleanup بعد فشل DB: رفض الوصول وعدم فقد الملف الصحيح.
5. تقرير نجاح لتكوين class بدل query/HTTP حقيقي، أو suite تعمل على DB التطوير: أدلة HTTP وDB وexit codes واختبار guard لقاعدة الاختبار.

## ملفا المتابعة الإلزاميان
- docs/baselines/golden-pattern-manifest.md: صف لكل إدارة + صف لكل workflow غير CRUD.
- docs/closure/implementation-ledger.md: المهمة، الملفات، القرار، الأمر، exit code، النتيجة، الدليل، المخاطر.
- الحالات: unverified → gap → implemented_unverified → verified. blocked لحاجة خارجية محددة؛ not_applicable مع سبب مرجعي واعتماد نطاق، وليس لإخفاء وحدة ناقصة.
- نسبة الإقفال = المتطلبات verified / المتطلبات المنطبقة. لا تساوي نسبة الملفات الموجودة.
- أدلة الفحص ترتبط بمراجعة Git أو بصمات الملفات وتاريخ الفحص. أي تعديل مؤثر يبطل دليل الجزء المتأثر.

## Task 0 — تثبيت baseline والمطابقة مع الأعمال
**Files:** docs/baselines/golden-pattern-manifest.md؛ docs/closure/implementation-ledger.md؛ docs/closure/business-parity.md.
**Consumes:** الجرد والخطة الأصلية، routes/{web,admin,tenant}.php وold/routes.
**Produces:** مصفوفة قابلة للتتبع لكل عملية: legacy reference → current files → expected behavior → gap → test → evidence.
- [ ] حفظ git status/diff ملخصًا؛ لا تعديل ملفات متغيرة من منفذ آخر دون قراءة نسخته الحالية.
- [ ] جرد Admin: dashboard، tenants، plans، subscriptions ومتابعتها، coupons، users، roles، templates، bills/payments، tickets، contact/trial، banks، control domains، FAQ، settings، activity، الأرشيفات الخمسة.
- [ ] جرد Tenant: dashboard، users/roles/permissions، assignments، properties، auctions ومخرجاتها، companies، bills، شراء/تجديد/ترقية الاشتراك، notifications، tickets، domains، licenses، settings/API token، activity، جميع الأرشيفات الواردة بالجرد.
- [ ] جرد Website: content/plans، contact/trial، checkout/verification/coupon/callback/retry، privacy، إشعارات البريد.
- [ ] مقارنة الحقول والعمليات لا أسماء الملفات فقط. لا تحوّل قسمًا ناقصًا إلى مرحلة مستقبلية تلقائيًا.
- [ ] توثيق المطابقة فعليًا مع حالات العمل القديمة؛ أي تغيير status أو route أو schema له mapping وخطة توافق.
**Gate:** كل متطلب له صف؛ لا تعريف مكتمل لوحدة بلا تحقق.

## Task 1 — بيئة تحقق آمنة وقابلية إعادة الإنتاج
**Files:** phpunit.xml، tests/TestCase.php، .env.testing.example، tests/Support/TestDatabaseGuard.php (جديد)، docs/closure/test-environment.md.
**Test:** tests/Unit/TestDatabaseGuardTest.php.
- [ ] حصر connections الفعلية دون عرض passwords؛ تحديد DB مركزية اختبار وprefix tenant اختبار.
- [ ] منع harness من destructive migrations أو RefreshDatabase إذا لم يكن APP_ENV=testing واسم DB/prefix ضمن allowlist اختبار صريحة.
- [ ] اختبار guard يسمح بقاعدة اختبار محددة ويرفض اسم التطوير وقاعدة فارغة وtenant prefix فعلي.
- [ ] suites السريعة يمكن أن تستخدم SQLite إذا كانت متوافقة؛ اختبارات provisioning/constraints/isolation تستخدم MySQL مطابقًا لمحرك الإنتاج المعتمد.
- [ ] لا fallback صامت من .env.testing إلى .env عند نقص config، ولا تكوين production cache أثناء اختبارات.
- [ ] إذا منع sandbox الوصول إلى DB يوثق السبب ويطلب الوصول المطلوب؛ لا تحويل الخطأ إلى نجاح.
**Gate:** بيئة الاختبار مسماة ومعزولة ويمكن إعادة إنشائها؛ لم تمس DB التطوير.

## Task 2 — الصلاحيات وPolicies على مستوى السجل
**Files:** app/Policies/{Admin,Tenant}/*Policy.php؛ app/Providers/AppServiceProvider.php؛ requests/controllers/DataTables/actions ذات الصلة؛ database/seeders/{AccessControlSeeder,TenantDatabaseSeeder}.php؛ lang/ar/permissions.php.
**Tests:** tests/Feature/Security/{AdminAuthorization,TenantAuthorization,MassActionAuthorization}Test.php.
- [ ] فحص Gate::before الحالي: لا true عام يتجاوز ملكية السجل ولا false يمنع وصول التنفيذ إلى Policy abilities.
- [ ] إنشاء وربط Policy لكل aggregate يحتاج authorization؛ صلاحية القائمة والإنشاء والكتابة والاسترجاع والحذف النهائي.
- [ ] توحيد قدرة Request/Controller/Policy/toolbar/sidebar مع الحفاظ على aliases القديمة اللازمة دون كسر مفاتيح مزروعة.
- [ ] معالجة toggle/destroy/restore/forceDelete/mass-delete عبر Requests وActions؛ رفض model alias غير مسموح أو guard مختلف.
- [ ] حفظ حقول الحالة بصيغة idempotent عند طلب قيمة محددة بدل عكس القيمة دون اعتبار الطلب.
- [ ] حماية آخر مدير قادر على الإدارة من التعطيل/حذف دوره أو نزع صلاحياته، بما يشمل العمليات الجماعية.
- [ ] seed idempotent؛ لا زر يرسل إلى route غير موجود؛ capabilities غير المطبقة لا تعرض للمستخدم.
**Gate:** اختبارات 403/404/200 للقدرات والملكية والجماعي؛ لا Policy صورية.

## Task 3 — فحص كل DataTable فحصًا تشغيليًا
**Files:** app/DataTables/**؛ resources/views/{admin,tenant}/**/{index,action}.blade.php؛ resources/assets/js/shared/datatable-*.js؛ config/models.php.
**Tests:** tests/Feature/DataTables/{AdminDataTables,TenantDataTables,ArchiveDataTables}Test.php (provider لكل جدول فعلي).
- [ ] توثيق عدد concrete classes منفصلًا عن abstract؛ مطابقة كل جدول بroute وview ومصدر query.
- [ ] تنفيذ HTTP AJAX فعلي ببيانات fixture لكل جدول؛ draw/recordsTotal/recordsFiltered/data صحيحة.
- [ ] التحقق من search/sort/page/filters/computed columns/enums/relations؛ ترتيب بأسماء أعمدة مسموحة فقط.
- [ ] إدخال HTML في الاسم والرسالة والكود للتحقق من escaping؛ rawColumns للـHTML المقصود فقط.
- [ ] خمس stat cards حقيقية للفهارس العادية، filters وفق الجرد، add داخل toolbar، status قبل actions، toggle لحالة النشاط فقط لا workflow مالية.
- [ ] الأرشيف: لا stats/title/breadcrumb/add؛ copy/excel فقط، مع الإجراءات المصرح بها.
- [ ] توضيح export: DataTables server-side client export عادة الصفحة المحملة؛ راجع عقد old وأضف export خلفيًا إذا كان المطلوب كل النتائج المرشحة.
- [ ] اختبر user بلا صلاحية القائمة، وآخر معه view بلا update؛ اختيار الكل محصور بالجدول ويصفّر عند draw.
**Gate:** إنشاء class أو html builder وحده لا يحسب verified.

## Task 4 — Tenant/Central boundaries وmigrations
**Files:** app/Services/Tenants/Shared/Central*Gateway.php؛ app/Models/Admin/**؛ app/Providers/TenancyServiceProvider.php؛ database/migrations/**.
**Tests:** tests/Feature/Tenancy/{CentralGatewayIsolation,ProvisioningFailure,MigrationIsolation}Test.php.
- [ ] فحص الاتصال الفعلي للـBill/Ticket والـBuilders الراجعة من gateway؛ لا تفترض أن اسم Gateway يكفل central connection.
- [ ] رفض tenant context فارغ قبل أي query أو mutation؛ تحديد tenant_id من السياق الموثوق لا مدخل المستخدم.
- [ ] نقل الاستعلام المباشر عن Admin Bill في TenantBillDataTable::getStats إلى gateway صريح.
- [ ] gateway write يعيد تحقق tenant/ownership داخل العملية؛ لا يثق في Model مُمرر أو تحقق قديم.
- [ ] lockForUpdate داخل transaction على الاتصال نفسه؛ فحص submitBankTransferReceipt تحديدًا.
- [ ] تأليف migration جديدة عند إصلاح schema منشورة؛ constraints/indexes/soft deletes متوافقة مع queries.
- [ ] اختبار fresh + upgrade للمركزي وTenant A/B على بيئة اختبار، وفشل منتصف provisioning وتعويض آمن.
- [ ] تثبيت مسودات وملفات/notifications/activity في tenant connection الصحيح.
**Gate:** نفس هوية record من tenant آخر لا يمكن قراءتها/تعديلها/تصديرها/استعادتها.

## Task 5 — إقفال Admin حسب الأعمال
**Files:** modules القائمة في app/{Http/Controllers,Http/Requests,Actions,DataTables}/Admin وresources/views/admin.
**Tests:** tests/Feature/Admin/{TenantManagement,SubscriptionWorkflow,CouponWorkflow,PublicRequestWorkflow,BillWorkflow,TicketWorkflow,SettingsWorkflow,ReferenceData,Archives}Test.php.
- [ ] العملاء: الحقول والفلاتر المفصلة بالجرد، تعديل بيانات العميل/المسؤول المسموح، إنشاء owner/roles/database/subscription، failure recovery.
- [ ] الباقات: جميع الحدود والأسعار والإتاحة والترتيب والتفعيل والأرشيف؛ قواعد editing المعتمدة.
- [ ] الاشتراكات ومتابعتها: pending/near-expiry/expired lists، قبول/رفض فردي وجماعي مع السبب، transitions من enum/old المعتمد؛ لا اختراع state machine.
- [ ] coupons: expiry field mapping، percentage ≤100، حدود الاستخدام والخصم، تفاصيل الاستخدام، منع تطبيق متكرر متزامن غير مسموح.
- [ ] users/roles: الصور، uniqueness، إسناد الأدوار، مصفوفة مصنفة مترجمة، guard isolation، profile إن كان في النطاق.
- [ ] bills: إنشاء من workflow المعتمد، تفاصيل/طباعة، توثيق التحويل/رفض بسبب/إعادة تفعيل؛ amounts/settled_amount/status/accounting محفوظة.
- [ ] tickets: الرد والكاتب والمرفقات والحالات وتفعيل الخدمة؛ لا إنشاء/حذف غير مسموح من القديم.
- [ ] contact/trial: تفاصيل ورد فعلي، reason، إعادة معالجة ممنوعة؛ prefilling لإنشاء العميل لا يثبت provisioning، approved بعد الأثر التجاري المعتمد فقط.
- [ ] banks/domains/FAQ/settings: الحقول القديمة كاملة والتبويبات والتفعيل، أسرار محمية ولا تعرض في HTML/JSON/logs.
- [ ] activity ينشأ عند الحدث التجاري بفاعل وسياق وقيم قبل/بعد منقحة؛ ليس جدول قراءة فقط.
- [ ] استرجاع/حذف نهائي للأرشيفات الخمسة مع تنظيف آمن وعلاقات سليمة.
**Gate:** كل متطلب Admin في parity manifest مرتبط بقبول ودليل؛ وجود CRUD أقل من القديم لا يغلق الوحدة.

## Task 6 — إقفال Tenant الأساسي والواجهات المشتركة
**Files:** modules Tenant Users/Roles/Assignments/Properties/Auctions/Companies؛ shared form/wizard/uploader components.
**Tests:** tests/Feature/Tenant/{UserWorkflow,RoleWorkflow,CompanyWorkflow,AggregateForms,FilesAndDrafts}Test.php، وتوسيع الاختبارات الحالية.
- [ ] users: employee number/license state/avatar/reset/roles/uniqueness وفق الجرد؛ roles مصنفة؛ permissions management إذا ما زالت جزءًا من الأعمال.
- [ ] properties: حقول الملكية والوكالة والعنوان والملفات والوحدات والحدود؛ لا سقوط حقول في edit أو mass assignment.
- [ ] assignments: selection server-side؛ حفظ الاختيار عبر pagination/filter؛ إعادة تحقق server-side لمنع ربط عقار لآخر أو tenant مختلف.
- [ ] auctions: dates/types/attachments/assignment consistency/templates، وعدم تغيير بيانات تاريخية من edit جديد.
- [ ] companies: logo، target، authorized_people، default contact وقواعد حذف العلاقات.
- [ ] جميع create forms، حتى bank/role/user أحادية الخطوة: draft save/restore/discard/purge مع ملفات؛ edit لا يرث draft create.
- [ ] validation DOM contract/endpoint من نفس FormRequest؛ authorize قبل validation يعبر عن القدرة.
- [ ] الشكل: stats+DataTable؛ wizard open grid؛ review-card/quick edit/live preview؛ show عمودان؛ RTL/mobile؛ لا اعتماد على وجود tags فقط.
**Gate:** حفظ ثم إعادة فتح وتعديل ومراجعة الملفات والمسودات ببيانات حقيقية يثبت السلوك.

## Task 7 — بقية Tenant والأرشيفات والحدود التكاملية
**Files:** Tenant Bills/Tickets/Notifications/Settings/ActivityLog/Archive؛ gateways؛ إضافة modules Domains/Licenses/Subscriptions/ApiTokens حيث يثبت الجرد أنها مطلوبة.
**Tests:** tests/Feature/Tenant/{BillingWorkflow,TicketWorkflow,NotificationOwnership,SettingsAndApiToken,ArchiveLifecycle,DomainLicenseSubscription}Test.php.
- [ ] bills/read/payment/bank transfer: ملكية tenant والرفع الآمن وحالات receipt وإعادة المحاولة.
- [ ] tickets: section/title/description/attachments وفق القديم، replies بهوية tenant user واضحة لا مجرد admin_user_id=null.
- [ ] notifications: per-user ownership للعدّ/القراءة/الحذف والجماعي.
- [ ] settings: general/printing/SMS/support emails/2FA/API token؛ لا checkbox أمان بلا enforcement.
- [ ] archives: users/companies/properties/auctions/assignments وفق الجرد؛ التقرير السابق عدّد ثلاثة فقط، لا يعتبر الغائبان خارج النطاق.
- [ ] domains وlicenses وشراء/تجديد/ترقية subscription: استعادة workflow القديم وسياساته عبر gateways، وحدود الباقات والحسابات.
- [ ] أي سياسة مزود أو قرار شراء غير محسوم يسجل blocked باسم القرار المطلوب؛ تبقى بوابة 100% مغلقة. لا تستبدل الأعمال بـmock أو تخفي الرابط ثم تسمي النقل مكتملًا.
**Gate:** لا وحدة من الجرد مؤجلة ضمن ادعاء النقل الكامل.

## Task 8 — مصمم القوالب وعقد المخرجات
**Files:** Admin AuctionTemplates؛ app/Services/Shared/AuctionTemplates؛ Tenant Auctions؛ renderer/design assets.
**Tests:** توسيع AuctionTemplateDesignerTest/AuctionTemplateCompatibilityTest/AuctionManagementPagesTest.
- [ ] schema موحدة للkeys/slots؛ صفحات متعددة وخط عربي وصور وطبقات وصلاحيات design/publish/toggle.
- [ ] رفض key مجهول أو عنصر خارج canvas أو asset غير مملوك أو slot مطلوب مفقود.
- [ ] اختيار نسخة منشورة نشطة متوافقة؛ snapshot immutable؛ إصدار جديد لا يغير مخرج مزاد قديم.
- [ ] preview/export فعلي قابل للتنزيل حيث تنص الأعمال، لا صورة واجهة ثابتة؛ العربية/RTL والصور الطويلة والخطوط تعمل.
**Gate:** سيناريو Admin design→publish→Tenant choose→render→new version مع ثبات المخرج السابق.

## Task 9 — دورة الموقع/الفوترة/الدفع/provisioning (مانع إطلاق)
**Files:** app/Http/Controllers/Web/CheckoutController.php؛ Web Requests؛ Actions/Services للفوترة والدفع والتحقق؛ config/services.php؛ routes/web.php؛ jobs/notifications.
**Tests:** tests/Feature/Checkout/{CheckoutProvisioning,PaymentVerification,PaymentIdempotency,EmailVerification,CouponConcurrency}Test.php.
**الفجوة المثبتة:** completePayment يمسح session ويعلن التفعيل دون إنشاء مورد في المسار؛ callback/verifyEmail/retry لا يحققون دورة دفع/تحقق كاملة.
- [ ] إزالة أي نجاح تجاري غير مدعوم بأثر DB؛ يمكن فقط إعلان الطلب المعلق عند إثبات إنشائه.
- [ ] تعريف سلسلة واحدة: هوية/تحقق → quote server-side → invoice/payment attempt → provider confirmation → provisioning/subscription → إشعار after-commit.
- [ ] الحساب المالي والتقريب والضريبة والخصم تستعاد من old/سياسة التسعير؛ لا افتراض مبلغ العميل ولا نسبة ضريبة عشوائية.
- [ ] حفظ attempt/idempotency/transaction identifiers بقيود uniqueness؛ callback يعتمد server verification/توقيع مطابق لعقد المزود وليس query status.
- [ ] provider response: amount/currency/merchant/reference ownership؛ callback مكرر/متأخر/فاشل/cancelled لا يكرر الحساب أو الفاتورة أو التفعيل.
- [ ] recovery عند دفع ناجح وprovisioning فاشل دون ضياع المال/السجل، retries وreconciliation قابلة للرصد.
- [ ] email OTP/token حقيقي بexpiry/rate limit؛ retry آمن وعدم فقد بيانات حساسة أو تسجيل password/hash في logs/drafts.
- [ ] Website content/plans/contact/trial/privacy مرتبطة بDB؛ endpoints apply-coupon المستخدمة بالواجهة موجودة.
**Gate:** sandbox مزود حقيقي أو official test adapter للاختبارات مع توثيق عدم إجراء live charge؛ لا استبدال production provider بمزود وهمي.

## Task 10 — 2FA والبريد والإشعارات والوظائف
**Files:** Auth middleware/routes/Requests؛ Services للتكامل؛ config/services.php؛ Mail/Notifications/Jobs؛ scheduler.
**Tests:** tests/Feature/Security/TwoFactorEnforcementTest.php؛ tests/Feature/Integrations/{MailSmsQueue,SchedulerRecovery}Test.php.
- [ ] توصيل force_two_factor_auth بتحدٍ وتحقق واسترداد وجلسات موثوقة بحسب سياسة النظام؛ لا تخزين flag فقط.
- [ ] SMTP/SMS ومزوّدات الإعدادات المطلوبة موصولة؛ لا keys افتراضية، وإخفاق التوصيل مسجل وقابل لإعادة المحاولة.
- [ ] jobs تحمل tenant ID وتهيئه وتنهيه بأمان؛ اختبر وظيفتين متتاليتين لمستأجرين مختلفين.
- [ ] expiry/reminder/license/accounting tasks مجدولة حسب متطلبات الأعمال؛ retries لا تكرر side effects.
- [ ] demo users/passwords في seed للتطوير فقط؛ إنتاج يبدأ بحساب إداري آمن دون default password.
**Gate:** يمكن تشغيل النظام بعد ضبط البيئة، لا يلزم كتابة تكامل جديد عند النشر.

## Task 11 — التطابق النهائي للتنقل والواجهة والموارد
**Files:** sidebars/navbar/dashboard/layouts؛ lang؛ assets/Vite؛ جميع views المتأثرة.
**Tests:** tests/Feature/Ui/{NavigationPermissions,DashboardMetrics,ViewRendering}Test.php؛ سجل browser evidence.
- [ ] كل رابط يطابق route/capability؛ إشعارات وعدادات ومؤشرات حقيقية scoped؛ لا أرقام ثابتة أو مجرد existence checks.
- [ ] تصيير HTTP فعلي لكل create/edit/show/index/archive/settings/preview؛ view:cache لا يكشف undefined variables أو runtime data errors كلها.
- [ ] browser desktop/mobile/RTL: search/order/filter/paging/export/toggle/draft/upload/delete/restore؛ HTTP 403/422 يعرض feedback مفهومًا.
- [ ] التحقق من /static/dashboard/icons/saudi-riyal.png الذي ظهر تحذيرًا أثناء build؛ وجود الملف ومساره عند runtime أو إصلاح المرجع.
- [ ] مراجعة references/dead code بعد فحص المستهلكات؛ لا حذف غير مصرح به ولا تغيير brand LOT وألوانه.
**Gate:** لا broken asset/link/console exception في السيناريوهات المعتمدة.

## Task 12 — بوابة التحقق النهائية المجمّعة
**Consumes:** المهام السابقة implemented_unverified؛ DB guard من Task 1.
**Produces:** docs/closure/verification-results.md، مخرجات محفوظة، قائمة كل requirement verified أو فشل صريح.
- [ ] تثبيت نسخة الملفات/lockfiles التي تفحص؛ منع تزامن التعديلات أثناء التشغيل.
- [ ] syntax لكل app/routes/config/database/tests بأداة تسجل الإجمالي وexit code لكل فشل؛ لا اعتماد على loop خرجت صامتة أو انتهت بtimeout.
- [ ] على DB اختبار معزولة فقط: central migrations + idempotent seed مرتين؛ tenant A/B migrations/seeds؛ اختبار ترقية بيانات legacy fixture إذا كانت هناك بيانات ستنقل.
- [ ] php artisan test كامل لأول دورة نهائية؛ جمع عدد tests/assertions/failures/skips والوقت. كل skip متعلق بمتطلب مغلق يفك الإقفال.
- [ ] إصلاح كل failure مع regression test، ثم إعادة suite كاملة بعد الإصلاح، وليس إعادة أمر واحد ثم إعلان النجاح.
- [ ] route:list --json، route:cache، config:cache، view:cache، npm run build؛ اختبارات HTTP بعد caches ثم restoration لبيئة التطوير.
- [ ] composer audit ومراجعة lockfiles/config/secret exposure/dependency compatibility؛ معالجة المخاطر القابلة للاستغلال.
- [ ] إثبات DataTable HTTP بكل concrete table، policies/ownership لكل mutation، عزل tenant/central/job/files/drafts.
- [ ] لا تشغيل fresh/seed/tenants:migrate على قواعد المستخدم الحالية؛ لا تعديل vendor لتجاوز harness.
**Gate:** صفر failures؛ صفر متطلبات حرجة skipped/blocked/gap؛ كل دليل يخص النسخة الحالية.

## Task 13 — runbook النشر والترحيل والاسترجاع
**Files:** docs/closure/production-runbook.md، docs/closure/release-acceptance.md، .env.example.
- [ ] المتطلبات/الإصدارات، database grants، storage/private media، APP_URL وpath tenancy، proxy/HTTPS، trusted hosts.
- [ ] خطوات build/deploy/migrations مركزي وtenants بالتدرج/seed reference آمن، queue restart، scheduler، cache، health check، log rotation.
- [ ] أخذ backup والتحقق من الاسترجاع في بيئة معزولة؛ خطة rollback للكود/schema والملفات وعمليات الدفع المعلقة.
- [ ] عند وجود بيانات فعلية للقديم: mapping/import trial/reconciliation counts/financial totals/attachments/roles؛ backup قبل أي ترحيل. إذا لا توجد، يوثق عدم انطباق نقل البيانات بدليل.
- [ ] تحقق sandbox للمزودات، قائمة keys دون قيم؛ smoke tests Admin/Tenant/Website بحسابات مختلفة.
- [ ] لا deployment ولا live payment ولا force-delete لبيانات المستخدم ضمن إعداد الخطة/التنفيذ المحلي.
**Gate:** نشر من نسخة نظيفة في بيئة staging مع اختبار recovery، ثم قبول الإصدار.

## Definition of Done — معنى 100% هنا
- [ ] كل صف من جرد الأعمال والخطة الأصلية له تنفيذ ودليل runtime مناسب، أو not_applicable معتمد لا ينتقص النطاق.
- [ ] لا شروط مزود/قرار أعمال عالقة ولا نجاحات صورية ولا workflows مؤجلة من النطاق.
- [ ] Policies/Requests/Actions/DataTables/drafts/files/archives والحدود مطابقة ومختبرة.
- [ ] full suite + build + caches + DB isolation + browser scenarios ناجحة على النسخة المسجلة.
- [ ] security/financial/concurrency/recovery failures مغلقة.
- [ ] production runbook ونتيجة rehearsal وخطة rollback موجودة.
- [ ] التقرير النهائي يتضمن الأوامر والنتائج والقيود بدقة؛ لا عدد مسارات أو files كبديل للاكتمال.
- [ ] إذا تعذر أي بند، الحالة «غير مغلق» مع سبب وخطوة مطلوبة، ولا نسبة 100%.

## ترتيب التنفيذ والتسليم
0 → 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8 → 9 → 10 → 11 → 12 → 13.
تؤلف الاختبارات مع المهمة وتؤجل المجموعة الكاملة إلى 12 حسب المستخدم. لا تعاد بناء الوحدات القائمة؛ إصلاح الفجوة الموثقة فقط.
كل تسليم مهمة يذكر: متطلبات مغطاة، ملفات، قرارات، فحص فعلي وexit code، وما ينتظر الاختبارات. لا عبارة «الفحوصات اجتازت» إذا كان الذي جرى syntax فقط.

