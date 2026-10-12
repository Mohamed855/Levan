# Golden Pattern — الدليل التنفيذي الكامل لبناء أي إدارة

> **الحالة:** المرجع المعماري الإلزامي والوحيد لكل إدارة حالية أو جديدة في المشروع.
>
> **الهدف:** يستطيع مطور يدخل المشروع لأول مرة بناء إدارة كاملة ومتطابقة مع بقية النظام باتباع هذا الملف بالترتيب، دون تخمين طبقة ناقصة أو إنشاء عقد خاص بالإدارة.
>
> **متابعة المطابقة:** يسجل وضع الإدارات الحالية في [`golden-pattern-manifest.md`](../baselines/golden-pattern-manifest.md). السجل يتابع التنفيذ، أما هذا الملف فهو مصدر القواعد.

---

## 1. معنى «إدارة» وحدود هذا الدليل

الإدارة هي capability كاملة يراها المستخدم، مثل الباقات أو الشركات أو الإسنادات. ليست الإدارة Controller أو صفحة منفردة؛ بل slice رأسي يمر بالترتيب التالي:

```text
Route
  → Middleware / Guard
  → Controller
  → FormRequest
  → Policy
  → Action أو Service
  → Model / Enum / Event / Job
  → DataTable أو Query + Blade Components
  → Shared JavaScript contracts
  → Feature / Unit / Isolation tests
```

لا تعتبر الإدارة مكتملة إذا كان أحد هذه الأجزاء مطلوبًا ولم يُنشأ أو لم يُعلّم صراحةً `not_applicable` مع السبب.

ينطبق الدليل نفسه على SuperAdmin وTenant. الفرق الوحيد هو boundary التنفيذ:

| القرار | SuperAdmin / Central | Tenant |
|---|---|---|
| قاعدة البيانات | الاتصال المركزي | اتصال قاعدة المستأجر المهيأ بواسطة tenancy middleware |
| Guard | `auth:admin` | `auth:tenant` |
| Namespace | `Admin` | `Tenants` |
| Routes | `routes/admin/admin.php` | `routes/tenant/tenant.php` |
| Views | `resources/views/admin/{module}` | `resources/views/tenants/{module}` |
| Drafts | جدول `drafts` المركزي | جدول `drafts` داخل قاعدة كل مستأجر |
| اختبار العزل | صلاحية وسجل مركزي | Tenant A لا يرى أو يعدل بيانات Tenant B |

كود Tenant ممنوع أن يستدعي Model مركزيًا مباشرة. أي عبور للحدود يمر عبر Gateway/Service صريح يستقبل tenant context، مثل نمط `CentralCityOptionsGateway`.

---

## 2. مرحلة التصميم قبل إنشاء الملفات

أنشئ Manifest للإدارة قبل كتابة الكود، ويحتوي على:

| الحقل | المطلوب كتابته |
|---|---|
| الاسم | اسم مفرد للنموذج واسم جمع للإدارة بالإنجليزية |
| Boundary | `central` أو `tenant` |
| Route prefix/name | مثال `plans` و`admin.plans.*` أو `assignments.*` |
| Guard/middleware | guard، subscription/license/verified عند الحاجة |
| Permissions | مفاتيح إنجليزية `module.action` لكل عملية |
| Aggregate root | النموذج الرئيسي والعلاقات التابعة له |
| Operations | index/show/create/store/edit/update/destroy وما عداها |
| State | activity boolean أم workflow enum؛ لا تخلطهما |
| Files | أنواع الملفات، disk، المسارات، وحدود الحجم |
| Side effects | Events/Listeners/Jobs/notifications/cache/external providers |
| UI | DataTable، form عادي أم wizard، archive، filters |
| Draft | resource key، خطوات الاستعادة، الملفات المؤقتة، purge |
| Tests | Feature، Unit، Policy، isolation، files، jobs |
| N/A | كل ملف غير مطلوب وسبب عدم الحاجة إليه |

### قواعد التسمية

- مفاتيح DB والصلاحيات والحالات والأكواد والـslugs إنجليزية فقط.
- الصلاحيات: `{module}.view`, `{module}.create`, `{module}.edit`, `{module}.delete`، وإجراءات متخصصة مثل `{module}_archive.restore`.
- حالات workflow تستخدم lowercase snake_case ويفضل Enum.
- النص العربي للعرض فقط في ملفات الترجمة أو Blade.
- لا تنشئ Repository عامًا حول Eloquent.
- Action لعملية business واحدة ذرّية؛ Service لتنسيق use cases أو queries مشتركة.

---

## 3. خريطة الملفات الإلزامية لكل إدارة

استبدل `{Boundary}` بـ`Admin` أو`Tenants`، و`{Module}` و`{Entity}` باسم الإدارة والنموذج.

```text
routes/{boundary}/{boundary}.php
app/Models/{Boundary}/{Module}/{Entity}.php
app/Enums/{Boundary}/{Module}/{StateEnum}.php                 # عند وجود حالات محددة
app/Http/Controllers/{Boundary}/{Module}/{Entity}Controller.php
app/Http/Requests/{Boundary}/{Module}/Store{Entity}Request.php
app/Http/Requests/{Boundary}/{Module}/Update{Entity}Request.php
app/Http/Requests/{Boundary}/{Module}/Update{Entity}StatusRequest.php # عند وجود activity toggle
app/Policies/{Boundary}/{Module}/{Entity}Policy.php
app/Actions/{Boundary}/{Module}/Create{Entity}Action.php       # عند وجود transaction/relations/files
app/Actions/{Boundary}/{Module}/Update{Entity}Action.php       # عند تعقيد التحديث
app/Services/{Boundary}/{Module}/{Entity}Service.php
app/DataTables/{Boundary}/{Module}/{Entity}DataTable.php       # للقوائم server-side
app/Http/Controllers/{Boundary}/Archive/{Module}/{Entity}ArchiveController.php
app/DataTables/{Boundary}/Archive/{Module}/Archived{Entity}DataTable.php
resources/views/{boundary}/{module}/index.blade.php
resources/views/{boundary}/{module}/create.blade.php
resources/views/{boundary}/{module}/edit.blade.php
resources/views/{boundary}/{module}/show.blade.php             # إن كان العرض مطلوبًا
resources/views/{boundary}/{module}/action.blade.php
resources/views/{boundary}/{module}/archive.blade.php
resources/assets/js/{boundary}/{module}/form.js                # فقط orchestration؛ لا قواعد validation
database/migrations/{tenant-or-central}/...                    # حسب boundary
database/factories/.../{Entity}Factory.php
database/seeders/...                                           # إذا احتاجت الإدارة بيانات مرجعية
tests/Feature/{Boundary}/{Module}/{Entity}CrudTest.php
tests/Unit/{Boundary}/{Module}/{Entity}PolicyTest.php
tests/Feature/{Boundary}/{Module}/{Entity}IsolationTest.php     # Tenant فقط
```

ليس المطلوب إنشاء ملفات فارغة. الملف غير اللازم يوضع `not_applicable` في Manifest بسبب محدد. الملف المطلوب لا يدمج داخل Controller لمجرد تقليل عدد الملفات.

---

## 4. ملف routes

### مكانه ومسؤوليته

- Central: `routes/admin/admin.php` داخل مجموعة `admin.` و`auth:admin`.
- Tenant: `routes/tenant/tenant.php` داخل مجموعة `auth:tenant` وبعد تهيئة tenancy، وتضاف `tenant.subscription` و`tenant.license` عند اعتماد الإدارة عليهما.
- الـroute يعرّف HTTP contract فقط؛ لا يحتوي business logic أو closures لتنفيذ عمليات الإدارة.

### الشكل القياسي

```php
Route::prefix('entities')->name('entities.')->group(function () {
    Route::patch('{entity}/toggle-status', [EntityController::class, 'toggleStatus'])
        ->name('toggle-status');
    Route::resource('/', EntityController::class)
        ->parameters(['' => 'entity']);
});
```

### قواعد إلزامية

- أسماء route مستقرة وتستخدم في Blade وDataTable، لا URLs مكتوبة يدويًا.
- GET للقراءة فقط. عمليات create/update/delete/toggle تستخدم POST/PUT/PATCH/DELETE.
- Route model binding باسم يطابق parameter المستخدم في `authorizeResource`.
- ضع routes الأكثر تحديدًا قبل resource أو wildcard routes.
- callback خارجي له Request للتحقق من signature/idempotency ولا يختبئ داخل CRUD route.
- لا تضف compatibility alias إلا بمالك وموعد إزالة مسجلين.

### اختبار الملف

- route name موجود، HTTP method صحيح، middleware/guard صحيح.
- guest يحصل على redirect/401، والمستخدم بلا صلاحية يحصل على 403.
- mutation عبر GET ترجع 404/405.

---

## 5. ملف Migration

### مكانه

- الجداول المركزية في migrations المركزية المعتمدة بالمشروع.
- جداول Tenant في `database/migrations/tenant` حتى تنفذ في كل قاعدة مستأجر.

### ما يجب أن يحتويه

- أسماء أعمدة ومفاتيح إنجليزية فقط.
- foreign keys وindexes المناسبة للاستعلامات والفلاتر.
- timestamps وsoft deletes إذا للإدارة archive lifecycle.
- status activity يكون boolean مثل `is_active`؛ workflow status يكون string/enum key موثقًا.
- قيود uniqueness تعكس boundary الحقيقي. uniqueness داخل Tenant لا يفحص central DB.
- `down()` قابل للعكس ما لم تكن migration retention معتمدة صراحةً.

### محظورات

- لا تخزن labels عربية.
- لا تجعل JSON بديلًا لعلاقات أساسية تحتاج constraints واستعلامات.
- لا تعدّل migration منشورة لتغيير production schema؛ أضف migration جديدة.
- لا تضع drafts الخاصة بالمستأجر في central DB.

### التحقق

اختبر migrate fresh للمركزي ولـTenant، وفشل القيود، والفهارس المطلوبة في queries الثقيلة.

---

## 6. ملف Model

### مسؤوليته

يمثل البيانات والعلاقات وcasts/scopes البسيطة. لا يدير request، redirect، upload، أو workflow متعدد الخطوات.

### البناء

```php
final class Entity extends Model
{
    use SoftDeletes;

    protected $fillable = [/* الحقول المعتمدة فقط */];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'status' => EntityStatus::class,
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### القواعد

- عرّف كل relation بنوع return واضح.
- استخدم Enum cast للحالات المحددة.
- استخدم scopes لاستعلامات قابلة لإعادة الاستخدام، لا authorization مخفيًا.
- Model Tenant لا يستخدم connection مركزيًا ولا relation مباشرة إلى Central Model.
- accessor للعرض البسيط مقبول؛ queries وعمليات business في Service/Action.
- file path المخزن نسبي، وتحويله إلى URL يتم في طبقة عرض/خدمة مناسبة.

### الاختبارات

relations، casts، scopes، soft delete، uniqueness/domain invariants التي تقع فعلاً داخل النموذج.

---

## 7. ملف Enum

أنشئه لكل status/category/type ذي مجموعة قيم مغلقة.

```php
enum EntityStatus: string
{
    case ACTIVE = 'active';
    case UNDER_REVIEW = 'under_review';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('statuses.active'),
            self::UNDER_REVIEW => __('statuses.under_review'),
        };
    }
}
```

- values إنجليزية lowercase snake_case.
- label مترجم ولا يخزن في DB.
- validation يستخدم `Rule::enum(EntityStatus::class)`.
- لا تستخدم activity toggle لتغيير workflow enum عشوائيًا.

---

## 8. ملفات FormRequest

### الملفات

- `Store{Entity}Request`: إنشاء نهائي.
- `Update{Entity}Request`: تحديث مع uniqueness يتجاهل السجل الحالي.
- Request مستقل لكل mutation متخصصة: status، reorder، import، restore، force delete، callback.
- Wizard create request يمتد من `WizardFormRequest` حتى يستطيع endpoint المشترك التحقق من الخطوة الفعلية.

### المصدر الوحيد للتحقق

`rules()`, `messages()`, `attributes()` هي المصدر الوحيد. ممنوع تكرار القواعد في JavaScript أو Controller.

```php
final class StoreEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('entities.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(EntityStatus::class)],
        ];
    }

    public function messages(): array { return [/* رسائل المجال */]; }
    public function attributes(): array { return [/* أسماء الحقول للعرض */]; }
}
```

### قواعد مهمة

- `authorize()` يستخدم guard والpermission الصحيحين.
- validation للعلاقات يثبت ownership وsoft-delete state، وليس مجرد وجود ID عالمي.
- الملفات لها `file/image`, `mimes`, `max` وقواعد العدد.
- لا تستخدم `$request->all()` بعد التحقق؛ مرّر `validated()`.
- draft saving لا يفرض اكتمال create request؛ final submit وحده يفرضه.
- uniqueness endpoints الإضافية إن وجدت تستخدم Request مستقل ولا تستبدل final validation.

### الاختبارات

اختبر كل required/type/enum/unique/relation/file rule، ورسالة 422 JSON، و403 عند فقد الصلاحية.

---

## 9. ملف Policy وتسجيله

### البناء

```php
final class EntityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('entities.view');
    }

    public function view(User $user, Entity $entity): bool
    {
        return $user->can('entities.view') && $this->belongsToCurrentBoundary($entity);
    }

    public function create(User $user): bool { return $user->can('entities.create'); }
    public function update(User $user, Entity $entity): bool { /* permission + ownership */ }
    public function delete(User $user, Entity $entity): bool { /* permission + invariant */ }
    public function restore(User $user, Entity $entity): bool { /* archive permission */ }
    public function forceDelete(User $user, Entity $entity): bool { /* archive permission */ }
}
```

### القواعد

- permission middleware يحمي capability، والـPolicy يحمي السجل نفسه؛ نحتاج الاثنين.
- سجل Policy في `AuthServiceProvider` أو آلية discovery المثبتة واختبر التسجيل.
- `authorizeResource(Entity::class, 'entity')` في Controller القياسي.
- subresource يفحص parent ownership أيضًا.
- Tenant Policy يرفض أي سجل خارج tenant context حتى مع وجود permission.
- mass delete يعيد فحص Policy لكل row؛ allow-list وحدها ليست authorization.

---

## 10. ملف Controller

### مسؤوليته الوحيدة

تحويل HTTP إلى use case: يستقبل dependencies، يمرر validated data، يستدعي authorization/action/service، ثم يرجع response. لا يحمل transaction أو business algorithm أو file lifecycle.

### constructor

```php
public function __construct(private readonly EntityService $service)
{
    $this->middleware('auth:tenant');
    $this->authorizeResource(Entity::class, 'entity');
    $this->middleware('can:entities.view')->only(['index', 'show']);
    $this->middleware('can:entities.create')->only(['create', 'store']);
    $this->middleware('can:entities.edit')->only(['edit', 'update', 'toggleStatus']);
    $this->middleware('can:entities.delete')->only('destroy');
}
```

### CRUD methods

- `index(DataTable $dataTable)`: يجلب statistics/options من Service أو Gateway ويعمل render.
- `create()`: options/read models فقط، ولا ينشئ records.
- `store(StoreRequest $request, CreateAction $action)`: `execute($request->validated(), actor)` ثم redirect success.
- `show(Entity $entity)`: eager-load العلاقات المطلوبة فقط.
- `edit(Entity $entity)`: record + options.
- `update(UpdateRequest $request, Entity $entity)`: Action/Service بالـvalidated data.
- `destroy(Entity $entity)`: يفوض lifecycle ثم يفرق بين JSON وbrowser redirect.
- `toggleStatus(StatusRequest $request, Entity $entity)`: يرجع `success`, `message`, `status|is_active`.

### عقد الحذف

```php
if (request()->ajax() || request()->wantsJson()) {
    return response()->json(['success' => true, 'message' => __('messages.deleted')]);
}

return redirect()->back()->with('success', __('messages.deleted'));
```

رسائل الخطأ العامة لا تعرض exception message. سجّل التفاصيل داخليًا مع context آمن.

### المحظورات

- `validate()` inline عند وجود FormRequest.
- queries طويلة أو transaction داخل Controller.
- استدعاء Central Model من Tenant Controller.
- raw JSON لعملية بدأت من hidden HTML delete form.
- تمرير request الكامل إلى Service.

---

## 11. ملف Action

أنشئ Action عندما تنفذ العملية الواحدة أكثر من write، أو تربط علاقات، أو تتعامل مع ملفات/side effects.

```php
final class CreateEntityAction
{
    public function execute(array $data, int $actorId): Entity
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($data, $actorId, &$storedPaths) {
                // normalize validated data
                // create aggregate
                // sync owned relations after locking/revalidation
                // store files and record paths
                // dispatch after-commit work
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::delete($path);
            }
            throw $exception;
        }
    }
}
```

### القواعد

- اسم action فعل واحد: Create/Update/Restore/Approve/Reconcile.
- transaction تشمل كل DB writes الخاصة بالعملية.
- تحقق مرة أخرى من العلاقات الحساسة داخل transaction عند وجود race condition.
- نظف الملفات الجديدة عند rollback، واحذف القديمة فقط بعد نجاح الاستبدال.
- dispatch لما يعتمد على commit يكون `afterCommit`.
- لا يرجع RedirectResponse أو View؛ يرجع entity/value/DTO.
- Action لا يصبح Service شاملًا لكل عمليات الإدارة.

---

## 12. ملف Service

يستخدم للقراءات المشتركة، statistics، توليد الأرقام، تنسيق use cases متعددة أو integration abstraction.

- methods ذات أسماء business واضحة: `getStatistics`, `generateNumber`, `updateEntity`, `toggleStatus`.
- لا يعتمد على HTTP request/session إلا إذا كان service مخصصًا لذلك ومعلنًا.
- الـqueries تضيف select/eager loading وتمنع N+1.
- cache key وTTL وآلية invalidation موثقة في نفس الخدمة.
- external provider خلف interface/gateway، مع timeout/retry/idempotency وترجمة أخطائه إلى domain result.
- crossing Central/Tenant خلف Gateway واضح، ولا يعاد كـEloquent model عابر للحدود إذا كان DTO/options كافيًا.

إذا كانت العملية write واحدة بسيطة يمكن Service تنفيذها؛ عندما تصبح transactional متعددة الأجزاء استخرج Action.

---

## 13. ملفات Event / Listener / Job / Notification

أنشئها عند وجود side effect لا يجب أن يثقل request أو عند الحاجة للفصل.

| الملف | متى يستخدم | العقد |
|---|---|---|
| Event | حدث domain تم بالفعل | يحمل IDs/DTOs لازمة، لا request |
| Listener | رد فعل مستقل | idempotent قدر الإمكان |
| Job | عمل queue طويل/قابل لإعادة المحاولة | tenant context، owner، timeout، retries، failed handling |
| Notification | إخطار مستخدم | authorization/recipient scope، ترجمة، deep link آمن |

- Jobs الخاصة بـTenant تحفظ tenant identifier وتعيد تهيئة السياق قبل query.
- export/import files مرتبطة بصاحبها ولا يمكن تنزيلها بالتخمين.
- تقدم العمليات async له عقد `job/session_id`, `status`, `progress`.
- لا ترسل side effect قبل commit إذا كان يفترض وجود record ناجح.

---

## 14. ملف DataTable

يمتد من `AbstractDataTable` ويعرّف query/columns/rendering/filtering/buttons فقط.

### الترتيب القياسي

```text
control/details → hidden id → checkbox → business columns → الحالة → الإجراءات
```

### الأعمدة الإلزامية للحذف الجماعي

```php
['data' => 'checkbox', 'name' => 'checkbox',
 'title' => '<input type="checkbox" id="select-all">',
 'orderable' => false, 'searchable' => false,
 'className' => 'custom-checkbox']
```

وفي `addCustomColumns()`:

```php
->addColumn('checkbox', fn ($row) =>
    '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
```

### الحالة

- activity state قابل للتعديل: `js-table-status-toggle` مع `data-url` وpermission guard.
- عمود الحالة قبل الإجراءات مباشرة.
- workflow/read-only state: badge، وليس toggle.
- لا تكتب AJAX handler خاصًا بالصفحة؛ استخدم `resources/assets/js/shared/table-status-toggle.js`.

### الأزرار

```php
protected function buttons(): array
{
    return [
        $this->getActionsDropdownButton(),
        $this->getAddButton(),
    ];
}
```

- Actions dropdown: copy، excel، وحذف المحدد إذا كان مدعومًا.
- Add button داخل DataTable toolbar فقط ومشروط بـ`{module}.create`.
- archive DataTable يوفر copy/excel، و`getAddButton()` يعيد `[]`.

### الأداء والأمان

- ابدأ query من Model scoped للـboundary الحالي.
- eager-load ما يستخدمه renderer.
- escape user values؛ أي HTML يدخل `rawColumns()` عمدًا فقط.
- filters تعدل query ولا تحمل collection كاملة.
- action view لا يظهر زرًا بلا permission.
- Model يدعم mass delete يجب أن يكون في `config/models.php`.

---

## 15. ملفات Blade

### `index.blade.php`

الترتيب الإلزامي:

1. statistics cards مباشرة؛ لا page heading banner فوقها.
2. filters عبر `<x-data-table.filters>` إن وجدت.
3. card يحتوي DataTable.
4. scripts/routes config اللازمة، مع event delegation على wrapper.

زر الإضافة لا يوضع منفردًا في الصفحة؛ مكانه DataTable header عبر `buttons()`.

### `create.blade.php`

- يستخدم reusable form controls بعقد `data-form-field`, `data-field-name`, `data-form-control` وerror output accessible.
- كل create form يحتوي «حفظ كمسودة» بجوار زر الإنشاء.
- form يعلن resource key وFormRequest الحقيقي للـshared validation endpoint.
- `multipart/form-data` عند وجود files.
- old input والدraft restore لهما precedence واضح، ولا تفقد الملفات المؤقتة.
- wizard buttons موحدة: السابق، التالي/تحقق، حفظ كمسودة، إنشاء نهائي.

### `edit.blade.php`

- نفس shared controls والعرض، لكن لا يعامل edit كcreate draft تلقائيًا إلا إذا اعتمد Manifest ذلك.
- method spoofing إلى PUT/PATCH.
- يعرض الملف الحالي وقرار الاحتفاظ/الاستبدال/الحذف صراحةً.

### `show.blade.php`

- read-only، لا mutation عبر links من نوع GET.
- كل بيانات حساسة تمر بالـPolicy.
- العلاقات الكبيرة تستخدم pagination/DataTable ولا تحمل بلا حد.

### `action.blade.php`

- يستخدم `<x-actions.*>` المشتركة.
- كل action داخل `@can` بالمفتاح الإنجليزي.
- الحذف الفردي يستخدم hidden form `#delete-form-{id}` و`window.confirmDelete(id)`.
- لا يكرر modal/AJAX implementation خاصًا بالإدارة.

### `archive.blade.php`

يتكون فقط من: optional errors، optional filters، table card. ممنوع العنوان والبreadcrumbs والإحصائيات. restore وforce-delete بصلاحيات مستقلة.

---

## 16. ملف JavaScript الخاص بالإدارة

ينشأ فقط عند وجود orchestration لا يغطيه shared code.

### المسموح

- تشغيل wizard والتنقل بين الخطوات.
- جمع IDs المختارة وعرض review state.
- استدعاء endpoints وإدارة loading/empty/error UI.
- تهيئة plugins ومزامنة قيمها مع form controls.
- event delegation الخاص بـ`#select-all` و`.row-checkbox` على `#table-id_wrapper`، وإلغاء select-all عند `draw.dt`.

### الممنوع

- نسخ Laravel validation rules أو الرسائل.
- status-toggle AJAX handler خاص؛ استخدم المشترك.
- URLs hard-coded بدل `window.routes` أو data attributes.
- ابتلاع server errors أو إظهار exception داخلي.
- query selectors لا تعتمد shared validation DOM contract.

بعد أي تعديل `.js`, `.css`, `.scss` يجب تشغيل `npm run build` قبل إغلاق المهمة.

---

## 17. عقد المسودات لكل Create Form

كل create form، سواء بطاقة واحدة أو wizard، متصل بنظام drafts.

### الملفات المشتركة

- `app/Http/Controllers/Shared/Wizard/WizardDraftController.php`
- `app/Services/Shared/Wizard/WizardDraftService.php`
- routes `wizard-drafts.show/store/destroy/file.destroy/validate-step`
- جدول drafts في boundary الصحيح.

### lifecycle

1. فتح create يبحث عن draft لنفس user + resource key.
2. تعرض الاستعادة بوضوح وتعيد values والملفات المؤقتة.
3. «حفظ كمسودة» يحفظ المدخلات الناقصة والملفات دون full create validation.
4. validate-step يشغل FormRequest الحقيقي ويطبق subset الخطوة، لا قواعد JS بديلة.
5. كل رفع تدريجي مرتبط بمسودة ومالك وboundary ومسار غير قابل للتخمين.
6. final submit يستخدم StoreRequest الكامل.
7. بعد نجاح الإنشاء فقط تحذف المسودة وملفاتها المؤقتة.
8. عند الفشل تبقى المسودة قابلة للاستعادة.
9. cleanup job يزيل المسودات المنتهية وفق retention موثق.

اختبر العزل بين المستخدمين والمستأجرين، حذف ملف واحد، purge بعد النجاح، والبقاء بعد rollback.

---

## 18. الأرشيف والحذف

### Soft delete

- `destroy` ينقل للأرشيف بعد Policy وdomain invariants.
- العلاقات/الملفات لا تحذف نهائيًا عند soft delete إلا بقرار domain موثق.

### Restore

- PATCH + Request/Policy مستقلان.
- يعيد العلاقات والحالة اللازمة بصورة transaction-safe.
- يفحص conflicts التي ظهرت بعد الحذف مثل unique keys.

### Force delete

- DELETE + permission/Policy مستقلان.
- Action/Service يحذف العلاقات والملفات في ترتيب آمن.
- يمنع الحذف إذا كان record مستخدمًا في سجل مالي/تاريخي وفق قواعد المجال.

### Mass delete

- IDs array validated، Model موجود في allow-list، Policy لكل row.
- العملية scoped للـboundary، ولا تقبل اسم class من المتصفح مباشرة.
- partial failure contract موثق أو العملية transaction كاملة؛ لا نتيجة مبهمة.

---

## 19. الاستجابات والـfeedback

| العملية | الاستجابة القياسية |
|---|---|
| HTML success | redirect + session `success` |
| HTML validation | redirect back + errors + old input |
| AJAX success | `success`, `message`, وأي payload موثق |
| AJAX validation | 422 + `message`, `errors` |
| Toggle | `success`, `message`, `status` أو `is_active` |
| Async | `success`, `job/session_id`, `status`, `progress?` |
| Unauthorized | 403 دون كشف تفاصيل |
| Unexpected error | رسالة عامة + log داخلي آمن |

Toastr يعتمد flash messages في browser flow. لا ترجع JSON خامًا من hidden-form deletion.

---

## 20. ملفات الاختبارات المطلوبة

### Feature CRUD test

يغطي:

- guard + permission لكل endpoint.
- index/create/show/edit render.
- store/update happy path وكل validation groups.
- Policy للسجل، وليس permission فقط.
- DB writes والعلاقات والملفات.
- destroy browser redirect وAJAX JSON.
- status toggle وعقد response.
- draft save/restore/purge.
- archive restore/force delete.
- DataTable filters/buttons/column contracts عند الحاجة.

### Policy unit test

جدول حالات لكل ability: بدون permission، permission صحيحة، record داخل scope، record خارجه، soft-deleted record.

### Tenant isolation test

- جهز Tenant A وTenant B.
- record في A لا يظهر ولا يرتبط ولا يعدل ولا يحذف من B.
- IDs العلاقات من B تفشل validation/authorization.
- drafts/files/jobs/cache keys لا تتسرب بين المستأجرين.

### Action/Service unit test

- success والـdomain failures.
- rollback الكامل عند exception.
- file cleanup.
- cache invalidation.
- idempotency للcallbacks/jobs.

### Contract tests

حافظ على الاختبارات الأفقية الموجودة للصلاحيات الإنجليزية، DataTable status/actions، route methods، shared validation، وسياسات Central/Tenant.

---

## 21. ترتيب بناء إدارة جديدة من الصفر

نفذ الخطوات بالترتيب ولا تبدأ من الواجهة:

1. اكتب Manifest وحدد boundary والعمليات والحالات والصلاحيات والعلاقات والملفات.
2. أضف permission keys الإنجليزية وترجماتها واختبار seeding/guard.
3. أنشئ migration في قاعدة البيانات الصحيحة.
4. أنشئ Enum ثم Model بعلاقاته وcasts.
5. أنشئ Factory/Seeder الضروريين للاختبارات والبيانات المرجعية.
6. أنشئ Store/Update/operation FormRequests كاملة rules/messages/attributes/authorize.
7. أنشئ Policy وسجلها واختبر abilities.
8. أنشئ Actions للعمليات transactional وService للقراءات/orchestration.
9. أضف Events/Jobs/Notifications اللازمة مع tenant context وafter-commit.
10. أنشئ Controller نحيفًا ووصل dependencies.
11. أضف routes بالmiddleware وHTTP methods الصحيحة.
12. أنشئ DataTable وترتيب checkbox/business/status/actions والأزرار القياسية.
13. أنشئ index/create/edit/show/action باستخدام shared components.
14. اربط كل create form بالمسودات والتحقق المشترك.
15. أضف archive controller/DataTable/view عند استخدام SoftDeletes.
16. أضف model إلى `config/models.php` إذا دعمت mass delete.
17. اكتب Feature/Policy/Isolation/Action tests قبل اعتبار الإدارة مكتملة.
18. شغّل الاختبارات المركزة ثم المجموعة الأفقية المتأثرة.
19. إذا تغيرت assets شغّل `npm run build`.
20. حدّث Manifest إلى `compliant` مع روابط أدلة الاختبار، لا بمجرد وجود الملفات.

---

## 22. قالب مراجعة كل ملف قبل الدمج

انسخ القائمة التالية داخل وصف PR أو سجل الإدارة:

```text
[ ] Route: method/name/middleware/binding صحيحة
[ ] Migration: boundary/keys/indexes/rollback صحيحة
[ ] Model: fillable/casts/relations/scopes بلا crossing ممنوع
[ ] Enum: English values + translated labels
[ ] Requests: authorize/rules/messages/attributes ومغطاة بالاختبارات
[ ] Policy: capability + record scope + registered
[ ] Controller: thin + validated data + correct responses
[ ] Action: transaction/locking/files/after-commit/rollback
[ ] Service: orchestration/query/cache/gateway فقط
[ ] Events/Jobs: idempotency + tenant context + ownership
[ ] DataTable: query/filters/checkbox/status/actions/buttons
[ ] Index: stats first + filters + table؛ لا banner/add button خارجي
[ ] Create: shared controls + draft + restore + final validation
[ ] Edit/Show/Action: authorization + no GET mutation
[ ] Archive: strict layout + restore/force-delete policies
[ ] JavaScript: orchestration only + shared handlers
[ ] Tests: CRUD + validation + policy + isolation + files + drafts
[ ] Verification: focused tests + contracts + npm build عند assets
```

---

## 23. Definition of Done للإدارة

الإدارة `compliant` فقط عندما:

- كل ملف مطلوب أعلاه موجود ويلتزم بمسؤوليته، وكل استثناء موثق بسبب.
- التدفق الفعلي هو Controller → FormRequest → Policy → Action/Service → UI → Tests.
- لا توجد قواعد validation منسوخة في JavaScript.
- لا يوجد Tenant/Central crossing مباشر.
- كل create form يدعم draft lifecycle كاملًا.
- كل activity table يستخدم status toggle المشترك وترتيب الأعمدة الصحيح.
- الحذف والأرشيف وmass delete لها Policies واستجابات صحيحة.
- مفاتيح DB والصلاحيات والحالات إنجليزية وتترجم في العرض.
- الاختبارات المركزة والأفقية ناجحة، وbuild ناجح إذا تغير asset.
- صف الإدارة في Manifest لا يحتوي `gap` بلا owner ودليل معالجة.

أي إدارة لا تحقق بندًا تظل `gap` أو `partial`، حتى لو كانت صفحاتها تعمل يدويًا.

---

## 24. أمثلة المشروع التي يرجع إليها المطور

هذه أمثلة للاستدلال على توصيل الطبقات، وليست إعفاءً من القواعد السابقة:

- Tenant transactional wizard: `AssignmentsController` + `StoreAssignmentRequest` + `AssignmentPolicy` + `CreateAssignmentAction` + `AssignmentService` + `AssignmentsDataTable`.
- Central CRUD مع lifecycle وstatistics: `PlanController` + `PlanRequest` + `PlanPolicy` + `PlanService` + `PlanDataTable`.
- Boundary gateway: `CentralCityOptionsGateway` بدل وصول Tenant المباشر إلى Model مركزي.
- Shared drafts: `WizardDraftController` و`WizardDraftService`.
- Shared table activity: `resources/assets/js/shared/table-status-toggle.js`.

إذا خالف مثال قديم بندًا في هذا المرجع، المرجع هو الحاكم ويسجل المثال كفجوة في الـManifest؛ لا تنسخ المخالفة إلى إدارة جديدة.
