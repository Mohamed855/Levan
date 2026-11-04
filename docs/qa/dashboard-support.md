# QA-007 — الدعم الفني — مراجعة مزدوجة

**الحالة: prepared.** إعداد Codex: 2026-11-04، Africa/Cairo. المرجع المقروء: `a86bbe93c32b99d9537183647861db18cc97de3b`. كل الحالات أدناه `not_run`؛ لم تُصلح الإدارة أو تُختبر رحلاتها في مرحلة الإعداد.

## النطاق ومدخل Gemini

التذاكر داخل اللوحتين حسب routes الفعلية، بما فيها الرد والمرفقات والتقييم والاستبيان وتغيير الحالة وتعطيل الخدمة. ليس contact requests أو تسليم رسائل بريد فعلية إلى أشخاص.

اقرأ [مرجع المراجعة المشترك](dashboard-shared.md) و[README](README.md) أولًا. بلاغات المستخدم U-01..U-10 لها أولوية قصوى، والـtester مؤجل. نفذ هذه الإدارة وحدها بعد تسليمها من المستخدم، مع SHD-01..16 المنطبقة. لا تغيّر ملفات إدارة أخرى إلا dependency مشتركة ثبت أنها سبب عطل داخل هذه الدفعة، وسجل أثرها وارتدادها. لا تُغلق الإدارة لأن نسخة واحدة نجحت.

## Routes / boundary / guard

Admin `admin.tickets.index/show/reply/updateStatus/toggleService`، دون create/store في resource الحالي. Tenant `tickets.index/create/store/show/reply/replies.rate/survey.submit`. رقم التذكرة المعتمد في routes لا يستبدل بـnumeric id بشكل تخميني.

المصدر الحي المحصور: [route-inventory.json](evidence/dashboard-shared/route-inventory.json). Guard: `admin` داخل Central، و`tenant` بعد middleware تحديد المستأجر في path/domain؛ راجع القائمة لتفاصيل middleware الفعلية ولا تلغها لإمرار الاختبار. `auth` عام داخل بعض controllers يستلزم فحص default guard وعدم استبداله بتخمين.

## خريطة الملفات

| الطبقة | SuperAdmin | Tenant |
| --- | --- | --- |
| Controller | [app/Http/Controllers/Admin/Tickets/AdminTicketController.php](../../app/Http/Controllers/Admin/Tickets/AdminTicketController.php) | [app/Http/Controllers/Tenants/Tickets/TicketController.php](../../app/Http/Controllers/Tenants/Tickets/TicketController.php) |
| Requests | [app/Http/Requests/Admin/Tickets/ReplyTicketRequest.php](../../app/Http/Requests/Admin/Tickets/ReplyTicketRequest.php)<br>[app/Http/Requests/Admin/Tickets/UpdateTicketStatusRequest.php](../../app/Http/Requests/Admin/Tickets/UpdateTicketStatusRequest.php)<br>[app/Http/Requests/Admin/Tickets/ToggleTicketsServiceRequest.php](../../app/Http/Requests/Admin/Tickets/ToggleTicketsServiceRequest.php) | [app/Http/Requests/Tenants/Tickets/TicketRequest.php](../../app/Http/Requests/Tenants/Tickets/TicketRequest.php)<br>[app/Http/Requests/Tenants/Tickets/TicketReplyRequest.php](../../app/Http/Requests/Tenants/Tickets/TicketReplyRequest.php)<br>[app/Http/Requests/Tenants/Tickets/RateTicketReplyRequest.php](../../app/Http/Requests/Tenants/Tickets/RateTicketReplyRequest.php)<br>[app/Http/Requests/Tenants/Tickets/SubmitTicketSurveyRequest.php](../../app/Http/Requests/Tenants/Tickets/SubmitTicketSurveyRequest.php) |
| Services | [app/Services/Admin/Tickets/AdminTicketService.php](../../app/Services/Admin/Tickets/AdminTicketService.php) | [app/Services/Shared/Integration/EtmamTicketService.php](../../app/Services/Shared/Integration/EtmamTicketService.php) |
| Models / Policy | [app/Models/Admin/Tickets/Ticket.php](../../app/Models/Admin/Tickets/Ticket.php)<br>[app/Models/Admin/Tickets/TicketReply.php](../../app/Models/Admin/Tickets/TicketReply.php)<br>[app/Policies/Admin/Tickets/TicketPolicy.php](../../app/Policies/Admin/Tickets/TicketPolicy.php) | [app/Models/Tenants/Ticket/Ticket.php](../../app/Models/Tenants/Ticket/Ticket.php) |
| DataTable | [app/DataTables/Admin/Tickets/AdminTicketsDataTable.php](../../app/DataTables/Admin/Tickets/AdminTicketsDataTable.php) | [app/DataTables/Tenants/Tickets/TicketsDataTable.php](../../app/DataTables/Tenants/Tickets/TicketsDataTable.php) |
| Views | [resources/views/admin/tickets/index.blade.php](../../resources/views/admin/tickets/index.blade.php)<br>[resources/views/admin/tickets/show.blade.php](../../resources/views/admin/tickets/show.blade.php) | [resources/views/tenant/tickets/index.blade.php](../../resources/views/tenant/tickets/index.blade.php)<br>[resources/views/tenant/tickets/create.blade.php](../../resources/views/tenant/tickets/create.blade.php)<br>[resources/views/tenant/tickets/partials/_form.blade.php](../../resources/views/tenant/tickets/partials/_form.blade.php)<br>[resources/views/tenant/tickets/show.blade.php](../../resources/views/tenant/tickets/show.blade.php) |
| JS | [resources/assets/js/admin/tickets/index.js](../../resources/assets/js/admin/tickets/index.js) | [resources/assets/js/tenant/tickets/show.js](../../resources/assets/js/tenant/tickets/show.js)<br>[resources/assets/js/tenant/tickets/form-validation.js](../../resources/assets/js/tenant/tickets/form-validation.js) |

ملفات مشتركة/مراجع مباشرة:

- [app/Services/Shared/Integration/EtmamConnector.php](../../app/Services/Shared/Integration/EtmamConnector.php)
- [app/Enums/Tenants/Tickets/TicketStatus.php](../../app/Enums/Tenants/Tickets/TicketStatus.php)
- [resources/views/components/shared/ui/file-card.blade.php](../../resources/views/components/shared/ui/file-card.blade.php)

جميع ملفات الصفحات داخل مجلدات الإدارة، بما فيها create/edit/action/partials/reset/aliases المستخدمة، مدرجة في [حصر الصفحات](evidence/dashboard-shared/module-view-inventory.json). عند التنفيذ طابق كل صفحة بالـroute المستهلك لها؛ لا تجعل view غير مستخدم أساس قبول، ولا تعتبر صفحة بلا CRUD مماثل في اللوحة الأخرى نقصًا يتطلب إنشاء ميزة.

**ملاحظة المصدر:** Tenant يستعمل خدمة تكامل مشتركة قد تقرأ/تعكس سجلات Central مع tenant context. اختبر الحد الصريح وخطأ المزود؛ لا تعد وجود tenant model كافيًا لإثبات مصدر البيانات. استعمل Http fake/transport اختباري في التكامل الآلي، وبيئة sandbox محددة لرحلة المتصفح؛ لا ردود أو تذاكر حقيقية لأشخاص.

المسار المعماري المراجع: Controller → FormRequest → Policy/حد تفويض موثق → Action/Service → UI → Tests. يوضح المنفذ في سجل التنفيذ Model/Enum والعلاقات وEvents/Jobs/cache/external side effects التي يستدعيها المسار الفعلي؛ إن لم يوجد Event/Job فلا ينشئ واحدًا لملء الجدول، بل يسجل N/A بسبب واضح. الإطار: [Golden Pattern](../architecture/golden-pattern.md) و[manifest النظام](../baselines/golden-pattern-manifest.md).

## الشروط والأدلة

طبّق fixtures ومصفوفة الأدوار/ADMIN/Tenant A/B/path/domain والأحجام في المرجع المشترك. إثبات قاعدتي المستأجرين مطلوب في العزل؛ مجرد تغيير tenant context مع تعطيل تبديل الاتصال لا يثبت العزل. كل صف أدناه سيناريو مستقل، وينفذ لكل variant منطبق. الأجزاء غير المتاحة في إحدى اللوحتين تسجل N/A بالدليل، لا passed.

الـBackend يحتاج نتيجة HTTP وتحقق DB/العلاقات/الهوية. الواجهة تحتاج متصفحًا على routes فعلية مع sessions وCSRF والأصول المبنية؛ لقطة محفوظة أو assert على نص المصدر لا يغلقان submit أو toast أو clipboard أو modal. لا تغير بيانات العمل أو credentials حقيقية.

## الحالات — 20 حالة

| ID | الشروط والخطوات القابلة للإعادة | المتوقع ومعيار القبول | الأولوية | الحالة |
| --- | --- | --- | --- | --- |
| SUP-01 | افتح كل route ticket موجود في النطاقين؛ حدد عمليات Admin مقابل Tenant | Admin index/show/reply/status/toggle-service؛ Tenant index/create/show/reply/rate/survey فقط؛ لا افتراض CRUD متناظر غير موجود | P1 | not_run |
| SUP-02 | index: stats/section/status/tenant/user/date filters/search/sort/page/export حسب الواجهة | نطاق وأعداد صحيحة، بيانات العلاقة دون N+1، الفلاتر تعمل في endpoint نفسه، لا IDOR في query params | P1 | not_run |
| SUP-03 | Tenant create ببيانات عنوان/تصنيف/وصف صحيحة ثم اعرضها في النطاق المسموح | تذكرة واحدة بهوية صاحبها ورقمها الصحيح، toast وredirect، لا نجاح إن رفضت الخدمة الحفظ | P1 | not_run |
| SUP-04 | عنوان/وصف فارغ/طويل، تصنيف غير موجود أو غير مصرح، HTML/script في النص | قواعد TicketRequest من الخادم مع shared errors، escape النص في list/show/replies/export، لا حقن أو 500 | P0 | not_run |
| SUP-05 | create/reply: ملفات مسموحة ومتعددة ثم امتداد مزيف/حجم/عدد زائد/اسم خبيث | فحص MIME/حدود فعلي، file-card للعرض، التخزين والتنزيل معزولان، لا تجاوز بالصيغ المعدلة أو paths | P0 | not_run |
| SUP-06 | عرض ticket يملكه المستخدم مقابل زميل غير مصرح وID من A/B؛ Admin بصلاحية ودونها | التفويض على كل endpoint/ملف/رقم تذكرة، لا اعتماد على UUID/رقم تسلسلي كحماية | P0 | not_run |
| SUP-07 | create بنقرة مزدوجة أو retry بعد timeout، وفشل midway في mirror/notification | لا تذكرة/رد/إشعار مكرر أو success زائف؛ الاسترداد من فشل التكامل موثق دون فقد النص/الملفات | P1 | not_run |
| SUP-08 | Tenant create: draft ناقص وملفات ثم استعادة/إنشاء، وفشل حفظ متعمد | U-07 وSHD-05..08؛ toast واحد واضح، draft داخل tenant DB، cleanup بعد إنشاء ناجح فقط | P0 | not_run |
| SUP-09 | أرسل ردًا صحيحًا في Tenant ثم Admin، وكرر فارغًا وخاطئًا ومرفقًا | مرسل وهوية وزمن صحيحون، submit/retry/Enter وloader سليمة، لا رفع صلاحية عن طريق sender_type أو tenant_id | P0 | not_run |
| SUP-10 | Admin يغير status وفق الحالات الحالية، وقارئ يحاول نفس POST؛ جرب انتقالًا غير مسموح | الانتقالات والتفويض صحيحة، تحديث الواجهة والإحصاء والنشاط؛ لا تغيير حالة بنص عربي في DB أو ID تذكرة آخر | P1 | not_run |
| SUP-11 | Tenant يقيم ردًا ثم يعدل/يكرر ويقيم رد نفسه أو رد تذكرة غير مصرح بها | قواعد التقييم والحدود والملكية من الخادم، لا تضخيم تقييمات أو cross-ticket IDs؛ UI متسقة بعد reload | P0 | not_run |
| SUP-12 | استبيان التذكرة: صحيح/ناقص/مكرر/قيم خارج الحدود، قبل/بعد الحالة التي تسمح به | القواعد الفعلية مطبقة، لا تكرار أو تجاوز owner/state؛ رسالة وloader ونتيجة محفوظة | P1 | not_run |
| SUP-13 | Admin toggle service ثم Tenant يحاول create/reply/عرض قديم؛ أعد التشغيل | تعطيل الخدمة له أثر معرّف على العمليات المعنية من الخادم دون إتلاف التذاكر، صلاحية toggle مستقلة | P1 | not_run |
| SUP-14 | مزود Etmam غير مهيأ/timeout/401/422/500/JSON غير صحيح، مع local fallback إن كان معتمدًا | نتيجة صادقة ورسائل لا تكشف API secrets، لا اتصال غير مقصود ببيئة حقيقية أو نجاح وهمي بتذكرة لم تحفظ | P0 | not_run |
| SUP-15 | دورة متكاملة باستخدام transport اختباري معزول: create→رد الدعم→رد المستخدم→status→تقييم/استبيان | نفس التذكرة والرقم والرسائل تظهر للجهات المصرح لها، counts/cache/notifications متسقة؛ لا إرسال رسائل دعم حقيقية ضمن الاختبار | P1 | not_run |
| SUP-16 | show بمرفقات قديمة ومستخدم/تصنيف محذوف ونصوص طويلة وحالات فارغة | layout موحد وfile-card، fallback مفهوم وth/td centered وbadge به …، لا stacktrace أو تسرب ملف | P0 | not_run |
| SUP-17 | كل single/mass delete button إن كان ظاهرًا رغم عدم وجود destroy ضمن routes المقروءة | إما route معتمد يفحص ويعمل بـconfirmation أو إزالة زر مكسور وفق العقد؛ لا إنشاء delete جديد لمجرد وجود زر قديم. U-10 | P0 | not_run |
| SUP-18 | create/edit partial إن وجد وstepper ذي الخطوة الواحدة والمكونات المشتركة | نموذج Tenant الحالي create فقط، header مخفي لخطوة واحدة، draft/actions موحدان؛ Admin بلا create لا يضاف له نموذج موازٍ | P1 | not_run |
| SUP-19 | CSRF ناقص، guard آخر، host مختلف وIDs ملف/رد/تذكرة متطابقة بين النطاقات | Policy/Request/gateway تؤكد الهوية والعزل؛ tenant code لا يستدعي Central models مباشرة خارج gateway صريح | P0 | not_run |
| SUP-20 | mobile/keyboard/slow network وعودة للصفحة بعد جلسة منتهية، ثم باقي SHD المنطبق | لا أزرار ميتة أو loading دائم أو تنبيهات مضاعفة، الدليل الفعلي لكل لوحة، blocked للتكامل الحقيقي منفصل عن tests المعزولة | P1 | not_run |

## الاختبارات الموجودة ونطاق تشغيلها

- [tests/Unit/TenantTicketIsolationContractTest.php](../../tests/Unit/TenantTicketIsolationContractTest.php)
- [tests/Unit/TenantTicketAuthorizationContractTest.php](../../tests/Unit/TenantTicketAuthorizationContractTest.php)

هذه مراجع للانطلاق، وليست أدلة نجاح ولم تُشغّل ضمن إعداد هذه الوثيقة. اقرأ setup/teardown واتصالات DB والـfakes قبل اختيار أمر التشغيل. الاختبار الذي يكتب لمستأجر قائم يعاد عزله أولًا. الاختبارات النصية/contract تثبت عقدًا محددًا فقط. أضف regression للسبب المثبت، ولا تختبر تنفيذًا مختلفًا بالـmocks ثم تعلن نجاح المسار الحقيقي.

## سجل التنفيذ — يملأ لكل variant ومحاولة

| الوقت / commit أو hashes | ID / variant / محاولة | المنفذ | الفعلي / رسالة الخطأ / HTTP وDB | السبب / الملفات والإصلاح | إعادة الاختبار / رابط الدليل | الحالة | مراجعة Codex |
| --- | --- | --- | --- | --- | --- | --- | --- |

لم يبدأ التنفيذ. ينشئ المنفذ صفًا عند تنفيذ الحالة ولا يملأ الجدول بقيم نجاح افتراضية. لا أسرار أو أسماء حسابات عمل في الأدلة. نتائج Gemini `fixed_pending_review` أو `passed_pending_review`، و`verified` لـCodex فقط.

## بوابة الإغلاق

- [ ] طوبقت كل routes والصفحات الفعلية في اللوحتين، وكل variant منطبق أو N/A مسبب.
- [ ] أُغلقت بلاغات المستخدم P0 المنطبقة وأعيد اختبار SHD والمكونات المشتركة وآثار التعديل.
- [ ] اجتازت رحلة Backend + Frontend كاملة والحالات السلبية والصلاحيات والعزل؛ لا blocker أو مشكلة معروفة بلا قرار.
- [ ] نجحت أوامر الفحص المناسبة، و`npm run build` بعد أي JS/CSS/SCSS، ثم أصول البناء في المتصفح.
- [ ] راجع Codex النسخة النهائية والأدلة مستقلًا، وقبل الحالات. لا production_ready بمجرد إعداد الملف أو تقرير Gemini.
