# Golden Pattern — عقد الإدارات الموحد

> **الحالة:** معيار إلزامي لجميع إدارات SuperAdmin وTenant الحالية والجديدة.
>
> **خطة التطبيق:** [المرحلة 10 من خطة توحيد النظام](../system-unification-implementation-plan-ar.md#16-المرحلة-10--عقد-الgolden-pattern-والتوحيد-المعماري-الكامل).
>
> **القواعد المختصرة الملزمة:** [`AGENTS.md`](../../AGENTS.md).

## 1. الغرض والنطاق

هذا المستند هو المرجع التنفيذي لإنشاء أو تعديل أي إدارة في Levan. هدفه أن يكون لكل إدارة نفس الحدود الأمنية، تدفق البيانات، تجربة الواجهة، وعقد الاختبار، من دون فرض إعادة تسمية أو نقل شامل للملفات الحالية قبل الحاجة.

ينطبق على نطاقين فقط:

| النطاق | أمثلة | قاعدة البيانات والحارس |
|---|---|---|
| `Central` / SuperAdmin | الشركات، الباقات، الاشتراكات، الفواتير، المحتوى، إعدادات المنصة | قاعدة مركزية، `auth:admin` |
| `Tenant` | المستخدمون، العملاء، العقود، المزادات، التحصيل، التقارير، الإعدادات | قاعدة المستأجر الحالية، `auth:tenant` |

لا يعني توحيد العقد أن كل إدارة CRUD؛ فالتقارير، لوحات المتابعة، الاستيراد، checkout، والأرشيف لها عقودها الخاصة أدناه.

## 2. القرارات المعمارية

### 2.1 Modular Monolith

النظام Laravel واحد، لكن كل إدارة تنتمي بوضوح إلى نطاق ومجال أعمال. لا تُنشأ Microservices، ولا طبقة `Repository` عامة تكرر ما يقدمه Eloquent.

```text
HTTP route
  → guard + tenancy + broad permission middleware
  → Controller
  → FormRequest + Policy
  → Action / Service
  → Eloquent Model + Enum + Event / Job
  → Blade / DataTable / JSON
  → Shared frontend contracts
  → Feature and Unit tests
```

### 2.2 فصل المسؤوليات

| الطبقة | مسؤوليتها | لا تفعل |
|---|---|---|
| Route | المسار، الاسم، method، middleware، model binding | قواعد أعمال أو validation |
| Controller | orchestration خفيف وتحويل النتيجة إلى HTTP response | استعلامات/transactions/رفع ملفات معقدة |
| FormRequest | authorization على مستوى الفعل وvalidation ورسائلها | حفظ البيانات أو قواعد واجهة مكررة |
| Policy | قرار الوصول إلى سجل محدد وtenant ownership | validation أو HTML |
| Action | عملية أعمال واحدة، غالباً transactional | تنسيق شاشة أو multiple use cases غير مترابطة |
| Service | orchestration لـuse case أو عائلة عمليات مترابطة | generic CRUD مكرر لكل Model |
| Model | العلاقات، casts، scopes الصغيرة، invariants المحلية | تدفق HTTP أو presentation |
| DataTable / View | العرض فقط، formatting محدود ومؤمن | authorization أو business rules |

### 2.3 متى نستخدم Action أو Service

- استخدم `CreateAuction`, `UpdateAuction`, `ArchiveAuction` عند وجود transaction أو إدارة علاقات/ملفات/side effects.
- استخدم `AuctionService` لتنسيق العملية أو لإعادة استعمالها بين HTTP وJobs وCommands.
- لا تنشئ `BaseCrudService` أو `BaseRepository`.
- إذا كان الاستعلام ثقيلاً أو يستخدم في أكثر من سياق، أنشئ Query/Report service واضح الاسم بدلاً من إخفائه داخل Controller.

## 3. حدود Central وTenant

### 3.1 قواعد غير قابلة للتجاوز

1. لا يحدد العميل `tenant_id` كي يختار بياناته؛ السياق يأتي من tenancy middleware.
2. لا يستدعي كود Tenant Model مركزيًا مباشرة.
3. أي قراءة أو كتابة مركزية من Tenant تمر عبر gateway/service معلن يستخدم `tenancy()->central(...)`.
4. كل Job أو Event أو Notification عابر للـtenant يحمل `tenantId` صراحة ويهيئ السياق قبل لمس البيانات أو التخزين أو cache.
5. كل مورد tenant-bound يثبت في الاختبار أن Tenant A لا يرى أو يعدل مورد Tenant B.
6. جدول المسودات (`drafts` / `wizard_drafts`) في نطاق `Tenant` يقع وجوباً في قاعدة بيانات المستأجر (`tenant connection/migrations`)، بحيث تخزن نماذج الإنشاء التابعة للمستأجرين مسوداتها محلياً داخل قاعدة المستأجر، التزاماً بمبدأ العزل التام وعدم كتابة أو قراءة كود الـ Tenant في نماذج أو جداول مركزية مباشرة. بينما نماذج `Central` تخزن مسوداتها في جدول المسودات بالقاعدة المركزية.

### 3.2 مثال لعبور نطاق مشروع

```php
final class TenantSubscriptionGateway
{
    public function currentSubscription(string $tenantId): ?Subscription
    {
        return tenancy()->central(fn () => Subscription::query()
            ->where('tenant_id', $tenantId)
            ->latest()
            ->first());
    }
}
```

لا يستدعي Controller أو Blade هذا الاستعلام مباشرة.

## 4. عقد الإدارة (Module Manifest)

قبل تعديل الإدارة أو اعتبارها موحدة، يُسجل صف لها في `golden-pattern-manifest` يحتوي على:

| الحقل | المطلوب |
|---|---|
| الاسم والمالك | اسم المجال ومالك تقني واضح |
| النطاق | `Central` أو `Tenant`، connection، guard |
| routes | name، method، middleware، bindings، consumer خارجي إن وجد |
| الصلاحيات | مفاتيح English dot notation وقدرات Policy |
| البيانات | Models، relations، enums، migrations، files/cache/queues |
| التطبيق | Controllers، FormRequests، Policies، Actions، Services |
| العرض | views، DataTables، shared components، scripts/styles |
| الاختبارات | Feature، Unit، isolation، smoke checklist |
| الحالة | `compliant` أو `gap` أو `not_applicable` مع سبب وowner |

لا تعني كلمة `not_applicable` الإعفاء؛ تعني أن البند لا ينطبق بوضوح، مثل عدم وجود delete في تقرير read-only.

## 5. Backend Contract

### 5.1 Routes وHTTP methods

| الفعل | method القياسي |
|---|---|
| عرض index/create/show/edit | `GET` |
| إنشاء | `POST` |
| تعديل كامل | `PUT` أو `PATCH` بحسب العقد الحالي |
| حذف ناعم | `DELETE` |
| استعادة أو toggle status | `PATCH` |
| حذف نهائي | `DELETE` |

- لا mutation عبر `GET`.
- تبقى أسماء routes وحقول الطلب الحالية متوافقة ما لم يوافق قرار مستقل.
- لا يستعمل `Route::resource()` إذا احتاجت الإدارة endpoints خاصة غير موثقة؛ تُكتب المسارات بوضوح مع أسماء ثابتة.

### 5.2 Controller

```php
final class AuctionController extends Controller
{
    public function __construct(
        private readonly CreateAuction $createAuction,
    ) {
        $this->middleware('can:auctions.view')->only('index');
        $this->middleware('can:auctions.create')->only(['create', 'store']);
    }

    public function store(StoreAuctionRequest $request): RedirectResponse
    {
        $auction = $this->createAuction->handle($request->validated());

        return to_route(tenant_route_name('auctions.show', $auction))
            ->with('success', __('auctions.created'));
    }
}
```

القواعد:

- تستخدم dependencies عبر constructor injection؛ يمنع `new SomeService()` داخل Controller.
- لا تعرض exception message للمستخدم.
- الطلب العادي يعيد redirect مع flash؛ الـAJAX يعيد JSON بالعقد الموحد.
- لا يختفي authorization عند endpoint ثانوي مثل download، restore، import progress أو uniqueness check.

### 5.3 FormRequest

كل mutation لديه FormRequest مخصص أو مبرر موثق لاستعمال request مشترك. الـFormRequest يعرّف دائماً:

```php
public function authorize(): bool;
public function rules(): array;
public function messages(): array;
public function attributes(): array;
```

- `rules/messages/attributes` هي المصدر الوحيد لقواعد النموذج.
- JavaScript يرسل البيانات إلى endpoint التحقق المرتبط بالـFormRequest؛ لا ينسخ القواعد التجارية.
- استخدم `prepareForValidation()` لتطبيع input، لا Controller ولا JavaScript.
- قواعد القيم المخزنة تبقى English؛ labels والرسائل العربية في `attributes()` أو files الترجمة.

### 5.4 الصلاحيات وPolicies

الصلاحية ذات مستويين:

```text
Permission / middleware: هل يملك المستخدم نوع الفعل؟
Policy: هل يجوز لهذا المستخدم تنفيذ الفعل على هذا السجل تحديداً؟
```

```php
final class AuctionPolicy
{
    public function update(User $user, Auction $auction): bool
    {
        return $user->can('auctions.edit')
            && $auction->belongsToCurrentTenant();
    }
}
```

- مفاتيح permissions والأدوار والحالات English only: `auctions.edit`, `active`, `SuperAdmin`.
- يستخدم Blade `@can('auctions.edit')` للعرض، لكنه لا يغني عن middleware أو Policy على الخادم.
- Policy مطلوبة لكل Model يمكن الوصول له برقم أو binding، أو يبرر عدم انطباقها في الـManifest.

### 5.5 Models والبيانات

- حدّد `$fillable` و`$casts` والعلاقات وscopes الصغيرة.
- استخدم Enum backed string للحالة عندما تمثل state ثابتة؛ يوفر enum `label()` و`badgeClass()` للعرض.
- لا تخزن نصوصًا عربية كمفاتيح أو statuses أو slugs أو permissions.
- استخدم eager loading و`withCount()` في القوائم، ولا تنفذ query لكل صف في DataTable أو Blade.
- ضع indexes للـforeign keys والـfilters والبحث الفعلي عند إضافة/تعديل schema.

### 5.6 المعاملات والـside effects

```text
Action
  → DB transaction
  → حفظ المورد والعلاقات
  → commit
  → event / queued job / notification
```

- أوقف أو نظّف الملف الجديد إذا فشل workflow قبل اكتمال العملية.
- لا ترسل event أو job يعتمد على بيانات غير committed.
- عمليات الدفع، callbacks، import، وupgrade تحتاج idempotency key أو حالة تمنع التنفيذ المكرر.

### 5.7 الحذف والأرشيف

```php
if (request()->ajax() || request()->wantsJson()) {
    return response()->json(['success' => true, 'message' => __('messages.deleted')]);
}

return redirect()->back()->with('success', __('messages.deleted'));
```

- الحذف العادي، الاستعادة، والحذف النهائي يراجعون Policy وtenant scope.
- archive view لا يظهر stats أو heading أو add button.
- لا يُحذف ملف أو child record إلا وفق سياسة lifecycle موثقة واختبار مخصص.

### 5.8 المسودات ودورة حياتها (Drafts Lifecycle)

- توفر المنصة عقد endpoints موحد للتعامل مع المسودات (`show`, `store`, `destroy` / `discard`).
- حفظ المسودة لا يُلزم استيفاء قواعد الـ Validation الصارمة الخاصة بـ `Store...Request` النهائي؛ بل يسمح بالحفظ المرحلي لبيانات النموذج غير الحساسة (`withoutSecrets`) والملفات المرفوعة (`draft_files`).
- فور نجاح عملية الإنشاء النهائية (`store`) وإنشاء السجل في قاعدة البيانات، تُحذف المسودة المرتبطة تلقائياً لتفادي بقاء بيانات معلقة (عبر middleware أو cleanup action).
- **الفصل المعماري والتخزين:**
  - في نطاق `Tenant`: يُشترط أن يقع جدول المسودات (`drafts` / `wizard_drafts`) داخل قاعدة بيانات المستأجر (`Tenant Database`) حصراً، وتتعامل معه نماذج الـ Tenant عبر اتصال المستأجر لضمان العزل التام للبيانات وعدم اختراق حدود النطاقات.
  - في نطاق `Central`: تُخزن مسودات نماذج الـ SuperAdmin في جدول المسودات بالقاعدة المركزية.

## 6. Frontend Contract

### 6.1 صفحات Index

```blade
<x-data-table.page table-id="auctions-table">
    <x-slot:stats>...</x-slot:stats>
    <x-slot:filters>...</x-slot:filters>
    <x-slot:table>...</x-slot:table>
</x-data-table.page>
```

الترتيب ثابت: stats ثم filters ثم table. لا يوجد banner أو `<h1>` أو زر إضافة مستقل فوقها. زر الإضافة و`الإجراءات` داخل DataTable toolbar فقط.

### 6.2 النماذج

استخدم فقط `x-form.controls.*` و`x-form.layout.field` حيث يناسب الحقل. كل control submitted يلتزم بـ:

```text
wrapper: data-form-field + data-field-name
control: data-form-control
error:   data-form-error + aria-describedby + aria-live
```

- النموذج البسيط: card واحد.
- النموذج المركب: wizard مشترك، وخطوة Next لا تفعل قبل نجاح تحقق FormRequest.
- `x-filter.select` للـfilters فقط وليس submitted form field.
- accessibility جزء من العقد: label مرتبط، `aria-invalid`، ومخرج خطأ واحد للحقل.

#### 6.2.1 معيار زر «حفظ كمسودة» وجدول المسودات (إلزامي لجميع نماذج الإنشاء)

1. **إلزامية زر «حفظ كمسودة» في جميع نماذج الإنشاء:**
   - كل نموذج إنشاء (`create form`) — سواء كان شاشة بسيطة (`single-card form`) أو نموذجاً متعدد الخطوات (`wizard`) — **يجب وجوباً** أن يحتوي على زر «حفظ كمسودة» إلى جانب أزرار الإجراءات الأساسية (`Submit` / `Cancel`).
2. **الربط مع جدول المسودات الموحد:**
   - ترتبط كافة نماذج الإنشاء بجدول المسودات عبر Endpoint موحد (`drafts` / `wizard-drafts`).
   - عند النقر على «حفظ كمسودة»، يتم حفظ البيانات والمدخلات الحالية والملفات المرفوعة مرحلياً دون فرض شروط الـ Validation الإلزامية الكاملة الخاصة بالإرسال النهائي.
   - عند معاودة فتح نموذج الإنشاء من قِبل المستخدم، يتم فحص وجود مسودة سابقة وتنبيه المستخدم لإمكانية استعادتها تلقائياً لتجنب ضياع العمل غير المكتمل.
   - عند إتمام عملية الإنشاء بنجاح وحفظ السجل، يتم حذف/تفريغ المسودة المرتبطة فوراً لمنع تراكم البيانات المؤقتة.
3. **تموضع جدول المسودات وعزل بيانات المستأجرين (Tenant Database Requirement):**
   - **جدول المسودات لنماذج المستأجرين يجب أن يكون متواجداً داخل قاعدة بيانات المستأجر (`Tenant Database / Migrations`) حصراً.**
   - هذا التموضع إلزامي لكي تتمكن نماذج الـ Tenant من تخزين مسوداتها محلياً داخل قاعدة المستأجر، التزاماً بمبدأ العزل التام للبيانات (Tenant Isolation) وقاعدة الـ Golden Pattern الصارمة: *«لا يستدعي أو يكتب كود Tenant في نماذج أو جداول مركزية مباشرة»*.
   - نماذج المركز (`Central / SuperAdmin`) تخزن مسوداتها في جدول المسودات التابع للقاعدة المركزية.

### 6.3 DataTables

ترتيب الأعمدة عند وجود CRUD قابل للحذف:

```text
# → checkbox → business columns → الحالة → الإجراءات
```

- checkbox وmass delete يظهران فقط عند وجود action فعلي وصلاحية وموديل مسجل في `config/models.php`.
- الحالة القابلة للتعديل تستخدم `js-table-status-toggle` فقط وendpoint `PATCH` وFormRequest للحالة.
- حالة workflow مثل فاتورة أو اشتراك قد تكون badge فقط؛ يوثق السبب في الـManifest.
- filters تربط بـ`filterKey` يطابق اسم الحقل الذي يقرأه DataTable query.
- لا تكتب handler AJAX خاصًا لكل صفحة لـtoggle أو mass-delete إذا كان shared handler يغطيها.

### 6.4 Responses وfeedback

| الحالة | العقد الأدنى |
|---|---|
| Mutation AJAX | `success`, `message`, `data?` |
| Validation | `success: false`, `errors`، HTTP 422 |
| Toggle | `success`, `message`, `status` أو `is_active` |
| Async import/export | `success`, `job/session_id`, `status`, `progress?` |
| API resource | `data`, `meta?`, `errors?` |

لا يلتقط JavaScript exception text ويعرضه للمستخدم. يستخدم رسالة عامة مع logging آمن على الخادم.

## 7. Dashboards والتقارير

- Dashboard ليس صفحة Index ولا يطبق عليها شرط toolbar.
- كل Dashboard يستخدم Service لإعداد view-data/DTO واضح؛ لا يحوي Controller تجميع KPIs أو استعلامات تجارية طويلة.
- cache للمؤشرات الثقيلة بمفتاح يشمل scope وtenant/user/role عند الحاجة، مع invalidation معلن.
- endpoints الرسوم والتقارير تعيد عقد JSON موثقًا وتخضع للصلاحية والـtenant scope.
- التقارير read-only، لكنها تحتاج validation للفلاتر، authorization، eager loading، وتصدير مؤمن.

## 8. الاختبارات المطلوبة

### 8.1 لكل إدارة CRUD

- authorized index وDataTable response.
- 403 عند فقد permission.
- 403 عند محاولة الوصول إلى سجل خارج scope أو Tenant.
- validation: redirect للـform العادي و422 للـAJAX.
- create/update/delete/restore/force-delete وفق ما ينطبق.
- حفظ كمسودة من نموذج الإنشاء واستعادتها وحذفها تلقائياً عند الإنشاء الناجح.
- status toggle وmass delete إن وُجدا.
- archive lifecycle والملفات والعلاقات عند وجودها.

### 8.2 للنطاقات الحساسة

| النطاق | اختبارات إضافية |
|---|---|
| Tenant | Tenant A مقابل Tenant B (بما في ذلك عزل جدول ومسودات المستأجرين داخل قاعدة بيانات المستأجر)، queues/cache/files عند استخدامها |
| Central cross-tenant | دخول صريح إلى `tenancy()->central()`، permission وaudit |
| Payment/callback | idempotency، توقيع/authentication، concurrency، لا mutation بـGET |
| Import/export | owner-only progress/download، cancellation، files، background job |
| Wizard | validation endpoint، drafts، Select2/repeater/files، next button |

### 8.3 بوابة التحقق

بعد تعديل PHP/Blade/JS/SCSS ضمن الإدارة، شغّل ما ينطبق:

```bash
php artisan test
php artisan view:cache
php artisan route:cache
php artisan route:clear
git diff --check
npm run build # إلزامي بعد أي تعديل JS/CSS/SCSS
```

## 9. Definition of Done

لا تعتبر الإدارة مطابقة للـGolden Pattern إلا إذا:

- اكتمل الـManifest ولم يبق بند `gap` بلا owner وخطة معالجة.
- routes وguard وconnection وpermissions وPolicies موثقة ومختبرة.
- لا يوجد mutation بلا FormRequest أو response contract.
- لا يوجد business validation مكرر في JavaScript.
- الواجهة تستخدم shared components والعقود المناسبة أو استثناء موثق.
- جميع نماذج الإنشاء تحوي زر «حفظ كمسودة» وترتبط بجدول المسودات في قاعدة البيانات الصحيحة (قاعدة بيانات المستأجر لنماذج Tenant، والقاعدة المركزية لنماذج Central).
- DataTable أو Dashboard أو Report يطبق العقد الخاص به.
- tenant isolation وlifecycle files/jobs/cache اختبرت عند انطباقها.
- مرت اختبارات الإدارة وبوابة التحقق العامة.

## 10. قائمة إنشاء إدارة جديدة

1. حدّد النطاق: `Central` أو `Tenant`، ومالك الإدارة وحارسها.
2. سجّل permissions الإنجليزية وPolicy abilities قبل إنشاء route.
3. أضف Model/Enum/migration والعلاقات وindexes اللازمة.
4. أضف routes صريحة وFormRequests وPolicy.
5. اكتب Action/Service للعملية التجارية؛ لا تضعها في Controller.
6. أنشئ DataTable أو View والنموذج بالمكونات المشتركة، مع تضمين زر «حفظ كمسودة» في كل نموذج إنشاء وربطه بجدول المسودات التابع لقاعدة بيانات النطاق المعني (Tenant DB للمستأجرين، Central DB للمركز).
7. أضف responses، notifications، Jobs، files، cache بالعقد المناسب.
8. اكتب الاختبارات ثم أضف صف الإدارة إلى الـManifest.
9. شغّل بوابة التحقق وسجل الدليل.

## 11. الاستثناءات والتغيير

- أي استثناء يكتب في الـManifest مع السبب والمالك وتاريخ المراجعة.
- لا يحذف legacy أو route أو migration أو بيانات تاريخية عبر هذا المستند؛ يتبع ذلك مرحلة deprecation/retention المعتمدة.
- تغيير قاعدة أعمال، تسعير، ضريبة، مزود دفع، أو route public يحتاج قرار منتج وخطة منفصلة.
- عند تعارض هذا المستند مع `AGENTS.md`، تكون قواعد `AGENTS.md` الإلزامية مقدمة؛ ويحدّث المستندان معًا عند اعتماد قاعدة جديدة.
