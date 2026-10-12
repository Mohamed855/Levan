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
| Drafts | جدول `wizard_drafts` المركزي | جدول `wizard_drafts` داخل قاعدة كل مستأجر |
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

استخدم placeholders حسب نوع المسار، ولا تحولها آليًا بصيغة واحدة:

- `{PhpBoundary}` = `Admin` أو `Tenants` داخل `app/` و`tests/`.
- `{route-boundary}` = `admin` أو `tenant` داخل `routes/`.
- `{view-boundary}` = `admin` أو `tenants` داخل `resources/views/`.
- `{asset-boundary}` = `admin` أو `tenant` داخل `resources/assets/`.
- `{migration-boundary}` = `admin` أو `tenant` حسب قاعدة البيانات.
- `{Module}` و`{Entity}` يطابقان namespace واسم النموذج الفعليين، ولا يفترضان جمعًا آليًا غير صحيح.

```text
routes/{route-boundary}/{route-boundary}.php
app/Models/{PhpBoundary}/{Module}/{Entity}.php
app/Enums/{PhpBoundary}/{Module}/{StateEnum}.php                 # عند وجود حالات محددة
app/Http/Controllers/{PhpBoundary}/{Module}/{Entity}Controller.php
app/Http/Requests/{PhpBoundary}/{Module}/Store{Entity}Request.php
app/Http/Requests/{PhpBoundary}/{Module}/Update{Entity}Request.php
app/Http/Requests/{PhpBoundary}/{Module}/Update{Entity}StatusRequest.php # عند وجود activity toggle
app/Policies/{PhpBoundary}/{Module}/{Entity}Policy.php
app/Actions/{PhpBoundary}/{Module}/Create{Entity}Action.php       # عند وجود transaction/relations/files
app/Actions/{PhpBoundary}/{Module}/Update{Entity}Action.php       # عند تعقيد التحديث
app/Services/{PhpBoundary}/{Module}/{Entity}Service.php
app/DataTables/{PhpBoundary}/{Module}/{Entity}DataTable.php       # للقوائم server-side
app/Http/Controllers/{PhpBoundary}/Archive/{Module}/{Entity}ArchiveController.php
app/DataTables/{PhpBoundary}/Archive/{Module}/Archived{Entity}DataTable.php # أو الاسم القائم المكافئ داخل الإدارة
resources/views/{view-boundary}/{module}/index.blade.php
resources/views/{view-boundary}/{module}/create.blade.php
resources/views/{view-boundary}/{module}/edit.blade.php
resources/views/{view-boundary}/{module}/show.blade.php             # إن كان العرض مطلوبًا
resources/views/{view-boundary}/{module}/action.blade.php
resources/views/{view-boundary}/archive/{module}/index.blade.php
resources/views/{view-boundary}/archive/{module}/action.blade.php
resources/assets/js/{asset-boundary}/{module}/form.js                # فقط orchestration؛ لا قواعد validation
database/migrations/{migration-boundary}/...                         # حسب boundary
database/factories/.../{Entity}Factory.php
database/seeders/...                                           # إذا احتاجت الإدارة بيانات مرجعية
tests/Feature/{PhpBoundary}/{Module}/{Entity}CrudTest.php
tests/Unit/{PhpBoundary}/{Module}/{Entity}PolicyTest.php
tests/Feature/{PhpBoundary}/{Module}/{Entity}IsolationTest.php     # Tenant فقط
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
});
Route::resource('entities', EntityController::class);
```

`Route::resource()` هو الأصل لكل CRUD. ممنوع كتابة routes منفصلة لـ`index/create/store/show/edit/update/destroy` عندما يستطيع resource التعبير عنها. استخدم `only()` أو `except()` عندما لا تدعم الإدارة كل العمليات، ثم أضف فقط العمليات غير القياسية بجوار resource:

```php
Route::prefix('entities')->name('entities.')->group(function () {
    Route::patch('{entity}/toggle-status', [EntityController::class, 'toggleStatus'])
        ->name('toggle-status');
});
Route::resource('entities', EntityController::class)
    ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
```

### قواعد إلزامية

- أسماء route مستقرة وتستخدم في Blade وDataTable، لا URLs مكتوبة يدويًا.
- GET للقراءة فقط. عمليات create/update/delete/toggle تستخدم POST/PUT/PATCH/DELETE.
- Route model binding باسم يطابق parameter المستخدم في `authorizeResource`.
- ضع routes الأكثر تحديدًا قبل resource أو wildcard routes.
- لا تفكك resource إلى سبعة تعريفات route يدوية. الاستثناء الوحيد عملية لا يمثلها resource مثل toggle، reorder، import، export، restore أو callback.
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
- كل إدارة تحتوي activity state من نوع active/inactive يجب أن تعرض toggler داخل DataTable؛ لا تعرض badge نصيًا للمستخدم الذي يملك صلاحية التعديل.
- كل togglers في المشروع تستدعي method عامة واحدة مشتركة لتغيير activity state وتوحيد الاستجابة. لا تنشئ `toggleStatus` business implementation مكررة داخل كل Controller؛ الاختلاف المسموح هو Policy/FormRequest واسم الحقل المعتمد (`status` أو `is_active`) عبر العقد العام.

### الصورة الرئيسية

- إذا كان للنموذج صورة واحدة أو صورة رئيسية، يجب أن يكون لها column مستقل في DataTable، ولا تدمج داخل عمود الاسم أو الإجراءات.
- الصورة تستخدم component عرض مشترك يثبت الأبعاد و`object-fit` وalt text وlazy loading.
- كل صور الصفوف داخل جداول الـIndex وDataTables تعرض بشكل دائري موحد باستخدام `rounded-circle`؛ ويمنع عرضها بشكل مربع أو مستطيل داخل عمود الصورة.
- عند غياب الصورة تستخدم fallback تلقائيًا: شعار المنصة في إدارة Central/SuperAdmin، وشعار الـTenant الحالي في إدارة Tenant، ثم شعار المنصة إذا لم يكن للـTenant شعار.
- لا تكرر منطق بناء URL أو fallback داخل DataTables؛ يمر عبر component/presenter مشترك.

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

التوزيع والترتيب الإلزامي:

1. **كروت الإحصائيات (Stat Cards)** مباشرة في البداية؛ لا page heading banner أو عناصر إضافية فوقها.
   - التوزيع القياسي: **5 كروت متساوية ومتناسقة تمامًا باستخدام `col-sm-6 col-xl`** لتغطية دورة حياة البيانات:
     1. الإجمالي العام (`primary`).
     2. الحالات النشطة أو الجاهزة (`success`).
     3. الحالات المكتملة أو قيد المراجعة (`info`).
     4. الحالات غير المرتبطة أو المعلقة (`warning`).
     5. الحالات المتوقفة أو التي تحتاج لمراجعة (`danger`).
   - تعتمد الكروت على مكون `<x-ui.stat-card>` مع أيقونات Tabler متناسقة وعناوين واضحة.
   - إذا لم يملك المجال خمس حالات فعلية، تبقى الكروت الخمسة مطلوبة لكن تعرض مؤشرات حقيقية مفيدة مشتقة من المجال (مثل المرتبط/غير المرتبط أو المكتمل/الناقص). ممنوع اختراع status مخزن أو تسمية مضللة فقط لإكمال العدد.
2. **شريط الفلاتر** عبر `<x-data-table.filters table="#table-id">`:
   - عناصر تحكم متساوية الارتفاع وموزعة بشكل شبكي متناسق.
   - الفصل التام بين `x-filter.select` المخصص للـDataTable وبين حقول النماذج العادية.
3. **بطاقة الجدول (DataTable Card)**:
   - الجدول يملأ البطاقة بالكامل مع شريط أدوات DataTable الموحد.
   - عمود الصورة: صورة دائرية `rounded-circle` بأبعاد ثابتة.
   - عمود الكود: كود قابل للنسخ بنقرة واحدة عبر `.copyable-code-wrapper` مع Toastr إشعار بالنسخ.
   - عمود الحالة: سويتش تفاعلي `js-table-status-toggle` قبل عمود الإجراءات مباشرة.
   - زر الإضافة لا يوضع منفردًا في الصفحة إطلاقًا؛ مكانه الحصري داخل شريط أدوات الـDataTable عبر `buttons()`.
4. scripts/routes config اللازمة، مع event delegation على wrapper للحذف الجماعي والتحديد.

### العقد العام لـ`create.blade.php` و`edit.blade.php`

- كل الحقول تستخدم shared form controls بعقد validation وaccessibility الكامل؛ لا raw controls محلية.
- Create، سواء كان single-card أو wizard، يعلن FormRequest الحقيقي وdraft resource key ويرتبط بعقد `wizard_drafts` كاملًا.
- Create الذي يحتوي ملفات يستخدم `multipart/form-data`، ويحافظ على old input والمسودة والملفات المؤرشفة وفق precedence واضح ومختبر.
- Edit يستخدم `PUT/PATCH` ويعرض قرار الاحتفاظ بالملف الحالي أو استبداله أو حذفه صراحةً حسب المجال.
- Edit لا يرث مسودة Create تلقائيًا، ولا يستخدم resource key نفسه.
- النموذج أحادي الخطوة يبقى single-card منظمًا، مع «حفظ كمسودة» بجوار زر الإنشاء في Create؛ ولا يضاف له stepper أو review step صوريان.

| نوع النموذج | مسودة إلزامية | Stepper | Review أخيرة |
|---|---:|---:|---:|
| Create أحادي الخطوة | نعم | لا | لا |
| Create متعدد الخطوات | نعم | نعم | نعم |
| Edit أحادي الخطوة | لا، إلا بعقد مستقل | لا | لا |
| Edit متعدد الخطوات | لا، إلا بعقد مستقل | نعم | نعم |

### `create.blade.php` و`edit.blade.php` متعددا الخطوات (معمارية وتجربة الـWizard)

القواعد البصرية وقواعد المكونات التالية مشتركة بين Create وEdit. عقد المسودات إلزامي لـCreate فقط؛ لا يضاف إلى Edit إلا إذا عرّف المجال صراحةً ميزة edit drafts مستقلة بعقد واختبارات خاصين بها. كل Create أو Edit متعدد الخطوات (خطوتان فأكثر) ينتهي بخطوة مراجعة أخيرة، أما النموذج أحادي الخطوة فلا يحول إلى wizard مصطنع.

- **مرجعية التصميم المعتمدة**: إدارة الإسنادات (Assignments) والعقارات (Properties) هما المرجع المعماري الموحد لجميع المعالجات متعددة الخطوات في النظام.
- **شريط مؤشر الخطوات (Stepper Header)**:
  - يحتوي على عناوين واضحة ومقتضبة للخطوات (`['id' => '...', 'title' => '...']`).
  - **يمنع تمامًا** إقحام نصوص فرعية مشوشة (`subtitles`) داخل مؤشرات الخطوات العلوية.
- **تخطيط خطوات الإدخال (Step Grid Layout)**:
  - تعتمد الخطوة على شبكة مدخلات مفتوحة ونظيفة بعرض الصفحة الكامل `<div class="row g-4">` باستخدام `col-md-6` و `col-12`.
  - **يمنع تمامًا** بناء أعمدة جانبية اصطناعية (`col-lg-4` تحتوي نسب مئوية أو صناديق إرشادات استعراضية) داخل خطوات إدخال البيانات؛ لأنها تضيق مساحة الإدخال وتكسر التناسق.
- **عقد الجداول الفرعية الديناميكية (Dynamic Subtables)**:
  - عند احتواء الخطوة على كيانات فرعية متعددة (مثل حدود العقار، الملاك، الوحدات، البنود الفرعية):
    1. توضع داخل بطاقة فرعية قياسية بنمط `.assignment-properties-table-card` داخل الخطوة.
    2. رأس البطاقة يحتوي على عنوان واضح، ووصف صغير، وزر إضافة أزرق في أقصى اليسار (`btn-sm btn-primary`).
    3. الجدول يعتمد تنسيق `table table-bordered table-sm align-middle`.
    4. زر الحذف لكل صف يكون أيقونة سلة مهملات حمراء موحدة `btn-icon btn-sm btn-label-danger`.
    5. ترقيم وتسمية المدخلات المصفوفية (`items[idx][field]`) يعاد فهرستها برمجيًا عند الإضافة والحذف مع إطلاق حدث `change`.
- **خطوة الملفات والمستندات**:
  - توزيع متوازن لحقول الرفع (`col-md-4` للحقول الفردية، و `col-md-6` أو `col-12` للمرفقات المتعددة).
  - شبكة عرض بطاقات الملفات تعتمد `.assignment-file-list` و `.assignment-file-card` الموحدة.
- **خطوة المراجعة والاعتماد (Review Step Architecture)**:
  - **تقسيم الشاشة إلزامي**:
    - **عمود كروت المراجعة الأيمن (`col-lg-9`)**: يحتوي على كروت المراجعة الموحدة `<x-form.wizard.review-card title="..." step="1" body-id="...">`.
    - **عمود المعاينة الحية الأيسر (`col-lg-3`)**: يحتوي على بطاقة المعاينة البصرية الحية `<article class="card assignment-preview h-100">` متضمنة الصورة الرئيسية بالأعلى (`.assignment-preview__image` بأبعاد متناسقة `aspect-ratio: 4 / 3`) وأسفلها اسم الكيان ووصفه.
  - **أزرار التعديل السريع**: بداية كل كارت مراجعة تحتوي زر تعديل مباشر ذكي ينقل المستخدم فورًا إلى رقم الخطوة المعنية عبر `wizard.bsStepper.to(step)`.
  - زر التعديل السريع يستخدم أيقونة `ti ti-edit` فقط مع `aria-label` وtooltip واضحين، ولا يعرض كلمة «تعديل».
  - لا تنفذ عملية submit نهائية إلا من داخل خطوة المراجعة الأخيرة.
- **عقد أزرار التنقل (Wizard Actions)**:
  - Create — الخطوة الأولى: زر «العودة» إلى الفهرس + زران في اليسار: «حفظ كمسودة» و«التالي».
  - Create — الخطوات الوسطى: زر «السابق» + «حفظ كمسودة» و«التالي».
  - Create — خطوة ما قبل المراجعة: زر «السابق» + «حفظ كمسودة» و«المراجعة».
  - Create — خطوة المراجعة الأخيرة: زر «السابق» + «حفظ كمسودة» وزر «إنشاء الكيان».
  - Edit — نفس اتجاهات «السابق/التالي/المراجعة»، لكن بلا زر مسودة افتراضيًا، ويظهر زر «تحديث الكيان» فقط في خطوة المراجعة الأخيرة.
  - الأسهم تأتي حصريًا من مكونات `next-button` و`previous-button`: في RTL «التالي» بسهم لليسار و«السابق/العودة» بسهم لليمين؛ لا تمرر الصفحة أيقونات معكوسة محليًا.

### `show.blade.php` (هندسة صفحة العرض والتفاصيل)

تعتمد صفحة العرض على نظام **العمودين غير المتناظرين (Two-Column Asymmetric Layout)** الموحد:

1. **شريط العنوان والإجراءات (Page Header Toolbar)**:
   - عنوان الكيان في اليمين مع أيقونة معبرة.
   - كود/رقم السجل بخط مميز `code.copyable-code` قابل للنسخ بالنقر مع Toastr إشعار.
   - شارات (Badges) الحالة التشغيلية وحالة الارتباط بالكيانات الأبوية.
   - أزرار الإجراءات في اليسار: زر «تعديل» رئيسي (`btn-primary`) وزر «العودة» للقائمة (`btn-secondary` مع أيقونة `ti ti-arrow-right`).
2. **العمود التعريفي الأيسر (`col-xl-4 col-lg-5 col-md-5 mb-4`)**:
   - **كارت الهوية والملخص (Entity Profile Card)**:
     - صورة الكيان الرئيسية في المنتصف بحجم موحد (`width: 100px; height: 100px; object-fit: cover`) وبالشكل الدائري `rounded-circle`.
     - اسم الكيان وشارة التصنيف/النوع.
     - شريط إحصائي سريع مقسم أفقيًا بخطين حدوديين (`d-flex justify-content-around py-2 border-top border-bottom`) يعرض الأرقام المفتاحية (مثل المساحة والعدادات التابعة).
     - قائمة البيانات الوصفية المنظمة (`info-container text-start` مع `list-unstyled`) لعرض الخصائص والمحددات الرسمية والتواريخ.
   - **كارت المستندات والمرفقات (Documents & Attachments Card)**:
     - يوضع إلزاميًا في العمود الأيسر أسفل كارت الهوية.
     - يعرض قائمة المرفقات بتنسيق سطور أنيقة مع أيقونة الملف وزر تحميل مباشر في أقصى اليسار (`btn-icon btn-sm btn-label-primary` مع أيقونة `ti ti-download`).
3. **العمود الرئيسي الأيمن (`col-xl-8 col-lg-7 col-md-7`)**:
   - مخصص حصريًا لعرض **الجداول الفرعية والعلاقات التشغيلية التابعة (Child Subtables & Related Records)**.
   - كل علاقة تابعة (مثل الحدود، الملاك، الوحدات الفرعية، المزادات، العمليات، إلخ) توضع في بطاقة كلاسيكية مستقلة:
     - رأس البطاقة: `<h5 class="card-title mb-0">اسم القسم (العدد)</h5>` مع إمكانية إضافة زر إجراء سريع مشروط بالصلاحية (مثل زر «إضافة عقار» أو «إضافة مزاد»).
     - جسم البطاقة: جدول تفاعلي نظيف من نوع `table table-hover` داخل حاوية متجاوبة `table-responsive`، مع رسالة واضحة في حال خلو السجلات (`empty state`).

قواعد الأمان والأداء في Show:

- الصفحة read-only؛ لا mutation عبر GET أو رابط عادي.
- ظهور كل إجراء والبيانات الحساسة يمر عبر Policy/permissions المناسبة.
- العلاقات الكبيرة تستخدم pagination أو DataTable ولا تحمل بلا حد.
- الصورة والمرفقات تستخدم URL/fallback/download contracts المشتركة، ولا تبني مسارات التخزين داخل Blade.

### مكونات حقول النماذج

- كل `select` داخل create/edit يستخدم component المشروع المبني على Select2؛ لا يسمح بعنصر `<select>` خام داخل صفحات الإدارات.
- كل input أو textarea أو file uploader أو radio/checkbox group أو date/number/password/tel/email control يستخدم Blade component مشتركًا.
- إذا لم يوجد component لنوع الحقل المطلوب، يُنشأ component عام أولًا بعقد validation وaccessibility ثم يستخدم في الإدارة؛ ممنوع كتابة control محليًا كحل مؤقت.
- كل component يطبق `data-form-field`, `data-field-name`, `data-form-control` وerror output accessible، ويدعم old value وdisabled/readonly/help text.

### قاعدة المكونات المشتركة

- أي markup أو interaction يتكرر أو يمكن استعماله في أكثر من إدارة يستخرج إلى component مشترك.
- ابدأ دائمًا من component مشترك بين Admin وTenant. لا تنشئ نسخة Admin ونسخة Tenant إلا عندما يوجد اختلاف وظيفي حقيقي، لا مجرد اختلاف بيانات أو route أو permission.
- لوحتا Admin وTenant تستخدمان غالبية نفس مكونات cards، metrics، loading، empty/error states، filters والـlayout؛ المكونات الخاصة بواحدة منهما يجب أن تبقى استثناءً محدودًا ومبررًا.
- الاختلافات في logo، route، permission، labels أو values تمر كـprops/slots، ولا تبرر نسخ component.
- الأسماء الحالية مثل `.assignment-properties-table-card` و`.assignment-preview` تعد عقد styling مشتركًا موروثًا من المرجع البصري؛ تستخدم من المصدر المشترك نفسه ولا تنسخ SCSS لكل إدارة. عند استخراجها إلى component عام يحافظ component على هذه classes للتوافق حتى migration مقصودة ومختبرة.

### `action.blade.php`

- يستخدم `<x-actions.*>` المشتركة.
- كل action داخل `@can` بالمفتاح الإنجليزي.
- الحذف الفردي يستخدم hidden form `#delete-form-{id}` و`window.confirmDelete(id)`.
- لا يكرر modal/AJAX implementation خاصًا بالإدارة.

### `archive/{module}/index.blade.php`

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
- جدول `wizard_drafts` في boundary الصحيح: migration مركزي لـCentral ومثيله داخل `database/migrations/tenant` لقواعد المستأجرين.

### lifecycle

1. فتح create يبحث عن draft لنفس user + resource key.
2. إذا وجدت مسودة، يجب إظهار modal قبل تحرير النموذج بخيارين صريحين: «استرجاع المسودة» أو «تجاهل والبدء من جديد»؛ لا تسترجع أو تتجاهل بصمت.
3. «استرجاع المسودة» يعيد فعليًا كل scalar values والمصفوفات والعلاقات المختارة وحالة خطوات الـwizard والملفات المرفوعة المؤرشفة مع metadata وروابط preview/remove، وليس أسماء الملفات فقط.
4. ملفات المسودة تحفظ كملفات مؤرشفة خاصة بالمسودة ومالكها والـboundary حتى الإنشاء النهائي أو التجاهل، ولا تعامل كملفات record نهائية قبل نجاح العملية.
5. «تجاهل والبدء من جديد» يحذف سجل المسودة وكل الملفات المؤرشفة التابعة له أولًا، ثم يعيد النموذج فارغًا؛ فشل الحذف يمنع البدء الجديد ويعرض رسالة قابلة لإعادة المحاولة حتى لا تترك ملفات orphaned.
6. «حفظ كمسودة» موجود في كل create form ويحفظ المدخلات الناقصة والملفات دون full create validation.
7. validate-step يشغل FormRequest الحقيقي ويطبق subset الخطوة، لا قواعد JS بديلة.
8. كل رفع تدريجي مرتبط بمسودة ومالك وboundary ومسار غير قابل للتخمين.
9. final submit يستخدم StoreRequest الكامل.
10. بعد نجاح الإنشاء فقط تنقل/تعتمد الملفات المطلوبة ثم تحذف المسودة وبقايا ملفاتها المؤرشفة.
11. عند الفشل تبقى المسودة والملفات المؤرشفة قابلة للاستعادة.
12. cleanup job يزيل المسودات المنتهية وملفاتها وفق retention موثق.

اختبر ظهور modal، كلا القرارين، الاسترجاع الكامل لكل نوع حقل وملف، حذف الملفات عند البدء من جديد، العزل بين المستخدمين والمستأجرين، حذف ملف واحد، purge بعد النجاح، والبقاء بعد rollback.

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
- draft save/restore/purge لكل Create form، ولا يطلب من Edit إلا إذا كانت edit drafts capability معلنة.
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

### تعديل إدارة قائمة

لا يعاد بناء الإدارة من الصفر ولا تفترض أن حالتها السابقة ما زالت صالحة. اتبع هذا التسلسل:

1. افتح بطاقة الإدارة في الـManifest وحدد العمليات والملفات المتأثرة فقط.
2. تتبع التدفق من route إلى الاختبار، وسجل أي طبقة يتغير عقدها: Request، Policy، Action/Service، UI، files أو side effects.
3. إذا تغير schema أضف migration جديدة قابلة للعكس؛ لا تعدل migration منشورة.
4. إذا تغير input أو response حدّث FormRequest والاختبارات أولًا، ثم الطبقة التطبيقية والواجهة دون نسخ validation إلى JavaScript.
5. حافظ على route names وDB keys والعقود العامة، أو وثق migration/compatibility window صريحة إن كان التغيير الكاسر مطلوبًا.
6. أعد فتح حالة كل بند متأثر إلى `partial` أثناء العمل، ثم أعدها إلى `compliant` فقط بعد أدلة الاختبار والبناء المطلوبة.
7. شغّل الاختبار المركز للإدارة والعقود الأفقية التي تمسها، و`npm run build` عند أي تغيير `.js/.css/.scss`.

---

## 22. قالب مراجعة كل ملف قبل الدمج

انسخ القائمة التالية داخل وصف PR أو سجل الإدارة:

```text
[ ] Route: resource + only/except أولًا، ثم العمليات غير القياسية فقط
[ ] Migration: boundary/keys/indexes/rollback صحيحة
[ ] Model: fillable/casts/relations/scopes بلا crossing ممنوع
[ ] Enum: English values + translated labels
[ ] Requests: authorize/rules/messages/attributes ومغطاة بالاختبارات
[ ] Policy: capability + record scope + registered
[ ] Controller: thin + validated data + correct responses
[ ] Action: transaction/locking/files/after-commit/rollback
[ ] Service: orchestration/query/cache/gateway فقط
[ ] Events/Jobs: idempotency + tenant context + ownership
[ ] DataTable: query/filters/checkbox/image/status/actions/buttons + shared activity toggler
[ ] Index: stats first + filters + table؛ لا banner/add button خارجي
[ ] Create: shared controls فقط + draft modal + full data/files restore + discard purge + final validation
[ ] Multi-step create: final review step + reusable assignment-style cards + icon-only edit navigation
[ ] Multi-step edit: final review step + reusable review cards + icon-only edit navigation؛ بلا drafts إلا بعقد صريح
[ ] Components: كل UI قابل لإعادة الاستخدام مشترك بين Admin/Tenant ما لم يوجد اختلاف وظيفي حقيقي
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
- كل create متعدد الخطوات ينتهي بخطوة مراجعة تستخدم review-card components المشتركة وزر edit icon ينقل للخطوة المطلوبة.
- كل form control يستخدم component المشروع؛ وأي نوع غير موجود له component عام جديد.
- كل activity table يستخدم status toggle المشترك والـmethod العامة وترتيب الأعمدة الصحيح.
- كل model له صورة رئيسية يعرض image column مستقلًا مع platform/tenant logo fallback المناسب.
- المكونات القابلة للمشاركة موحدة بين Admin وTenant، والنسخ boundary-specific لها مبرر وظيفي موثق.
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
