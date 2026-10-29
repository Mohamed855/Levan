# خطة المعالجة والمطابقة المعمارية لإدارات الأصول (الإسنادات — العقارات — المزادات) وفق الـ Golden Pattern

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** مطابقة وتوحيد إدارات الأصول الثلاث في المستأجر (الإسنادات `T05`، العقارات `T06`، المزادات `T07/T08`) مع معايير الـ Golden Pattern بالكامل، عبر دمج خطة الـ superpowers الشاملة مع نتائج التدقيق العميق (إنشاء الفورم المشترك `_form.blade.php`، تنظيف الأعمدة الميتة والمسارات المهملة، فك الاشتباك بين النشاط وسير العمل، وسد ثغرة بيع العقار مرتين).

**Architecture:** اتباع تدفق النمط الذهبي الإلزامي `Route → Middleware → Controller → FormRequest → Policy → Action/Service → Model/Enum → DataTable → UI → Tests`. استئصال الأكواد الميتة والازدواجية، توحيد هياكل النماذج بين Create و Edit، تأمين دورة حياة الملفات الذرية، وحماية سلامة قواعد البيانات.

**Tech Stack:** Laravel 11، PHP 8.2+، Blade، Bootstrap 5، SCSS/Vite، jQuery، BS-Stepper، Select2، Yajra DataTables، Multi-tenancy (Stancl/Tenancy).

**Spec:**
- مرجع النمط الذهبي: [`docs/architecture/golden-pattern.md`](../../architecture/golden-pattern.md)
- تعليمات المشروع: [`AGENTS.md`](../../../AGENTS.md)
- تقرير الفحص والتدقيق: [`C:\Users\moham\.gemini\antigravity\brain\87725d94-4587-4e52-a3cc-708b3bdb3349\comprehensive_audit_properties_assignments_auctions.md`](file:///C:/Users/moham/.gemini/antigravity/brain/87725d94-4587-4e52-a3cc-708b3bdb3349/comprehensive_audit_properties_assignments_auctions.md)
- خطة الـ superpowers الأساسية: [`docs/superpowers/plans/2026-10-29-golden-pattern-remediation.md`](2026-10-29-golden-pattern-remediation.md) و ملحق [`tenant.md`](2026-10-29-golden-pattern/tenant.md) (حزم `T05`, `T06`, `T07`, `T08`).

---

## Global Constraints

- **Multi-Tenancy Isolation:** كافة التعديلات في نطاق قاعدة بيانات المستأجر (`tenant_public` / `tenant_private` / `auth:tenant`) ولا يتم استدعاء نماذج مركزية مباشرة.
- **English Keys Only:** مفاتيح قاعدة البيانات، أسماء الأعمدة، الحالات، والصلاحيات إنجليزية بصيغة snake_case/dot-notation، والنصوص العربية لطبقة العرض والترجمة فقط.
- **Shared Form Parity:** يُمنع تكرار كود الاستمارات بين Create و Edit؛ الاستمارة توضع حصرياً في `partials/_form.blade.php` وتُدار بواسطة المتغير `$is_edit` مع تطابق تام في عدد الخطوات والحقول.
- **Button Standards:** الالتزام الحرفي بنصوص الأزرار الافتراضية («حفظ كمسودة»، «التالي»، «السابق»، «حفظ») دون تمرير `text="..."` مخصص، وتجميع أزرار الإجراء والمسودة داخل `<div class="d-flex gap-2">`.
- **File Previews:** جميع المعاينات عبر النظام بنمط البطاقات المربعة الموحدة `<x-ui.file-card layout="card">` بحجم 150×150 بكسل؛ ويُلغى `layout="list"` نهائياً.
- **Activity vs Workflow:** يُمنع قطعياً استخدام سويتش النشاط `js-table-status-toggle` لتعديل حالات الـ Workflow Enum؛ النشاط يحكم `is_active` فقط.
- **Safe Error Responses:** رسائل الأخطاء العامة ودية ولا تكشف `$e->getMessage()` للمستخدمين في الفلاش أو الـ JSON.

---

## Review Focus

1. **حالة العقار المباع:** التأكد من أن بيع العقار في مزاد يمنع ظهوره أو إعادة اختياره في أي مزاد جديد لنفس الإسناد.
2. **سلامة الحالات في المزادات:** التأكد من أن تعطيل مزاد منتهي (`completed`) أو قيد الإعداد (`in_preparation`) وإعادة تفعيله لا يحوله قسرياً إلى `active`.
3. **الملفات اليتيمة على القرص:** التأكد من أن استبدال الملفات الأحادية يحذف الملف القديم فعلياً من القرص بعد نجاح الـ Transaction، مع تنظيف الجديد إذا حدث Rollback.
4. **تطابق خطوات الإسنادات:** التأكد من أن شاشة تعديل الإسناد تحتوي 4 خطوات كاملة وتسمح بمراجعة العقارات والمستندات السابقة وتعديلها.
5. **توافق بيئة PHP 8.2:** منع أي قراءة لـ `->value` على متغيرات نصية في الواجهات لضمان عدم حدوث Fatal Errors.

---

## خطة المهام التفصيلية (Task Breakdown)

### Task 1: تنظيف قاعدة البيانات واستئصال المسارات والأعمدة الميتة (Database Cleansing)

**Files:**
- Create: `database/migrations/tenant/2026_10_29_000001_cleanup_dead_columns_from_auctions_and_assignments.php`
- Modify: `app/Models/Tenants/Auctions/Auction.php:20-65`
- Modify: `app/Models/Tenants/Assignments/Assignment.php:20-45`
- Modify: `routes/tenant.php:330-365`
- Delete: `app/Http/Controllers/Tenants/Auctions/AuctionOutputController.php`
- Delete: `app/Services/Tenants/Auctions/AuctionOutputService.php`
- Delete: `app/Services/Tenants/Auctions/AuctionSnapshotExporter.php`
- Delete: `app/Services/Tenants/Auctions/AuctionTemplateRenderer.php`
- Test: `tests/Feature/Tenants/Auctions/AuctionDatabaseCleanlinessTest.php`

**Interfaces:**
- Consumes: schema table definitions for `auctions` and `assignments`.
- Produces: Clean `Auction` and `Assignment` models without dead columns, proper `$casts` for `status => AuctionStatus::class`.

- [ ] **Step 1: كتابة اختبار التحقق من نظافة الأعمدة ونموذج المزاد**

```php
// tests/Feature/Tenants/Auctions/AuctionDatabaseCleanlinessTest.php
public function test_auction_model_has_no_dead_template_columns_and_casts_status_to_enum(): void
{
    $auction = new Auction();
    $fillable = $auction->getFillable();
    $casts = $auction->getCasts();

    $this->assertNotContains('template_versions', $fillable);
    $this->assertNotContains('brochure_template_id', $fillable);
    $this->assertNotContains('template_snapshot', $fillable);
    $this->assertSame(\App\Enums\Tenants\Auctions\AuctionStatus::class, $casts['status'] ?? null);
}
```

- [ ] **Step 2: تشغيل الاختبار للتأكد من فشله**
Run: `php artisan test --filter="AuctionDatabaseCleanlinessTest"`
Expected: FAIL

- [ ] **Step 3: إنشاء Migration لحذف الأعمدة الميتة وتعديل النماذج**
- حذف أعمدة `template_versions`, `template_snapshot`, `template_bindings`, `brochure_template_id`, `billboard_template_id`, `social_template_id` من جدول `auctions`.
- حذف عمود `entity_logo` من جدول `assignments`.
- تحديث `$fillable` و `$casts` في `Auction.php` وإضافة `status => AuctionStatus::class`.
- إزالة الـ routes القديمة الخاصة بـ `AuctionOutputController` من `routes/tenant.php` وحذف الملفات الأربعة المعطلة.

- [ ] **Step 4: تشغيل الاختبار للتأكد من نجاحه**
Run: `php artisan test --filter="AuctionDatabaseCleanlinessTest"`
Expected: PASS

- [ ] **Step 5: الالتزام (Commit)**
```bash
git add database/migrations/tenant/ app/Models/ routes/tenant.php app/Http/Controllers/Tenants/Auctions/AuctionOutputController.php app/Services/Tenants/Auctions/ tests/
git commit -m "refactor(assets): drop dead auction template columns and remove legacy output subsystem"
```

---

### Task 2: تصحيح دورة حياة الحالات وسد ثغرة بيع العقار مرتين (Business Invariants & State Machine)

**Files:**
- Modify: `app/Services/Tenants/Auctions/AuctionService.php:46-55`
- Modify: `app/Http/Requests/Tenants/Auctions/StoreAuctionRequest.php:143-160`
- Modify: `app/Http/Controllers/Tenants/Assignments/AssignmentsController.php:193-212`
- Modify: `app/Actions/Tenants/Auctions/CreateAuctionAction.php:110-165`
- Modify: `app/Actions/Tenants/Auctions/UpdateAuctionAction.php:120-195`
- Test: `tests/Feature/Tenants/Auctions/AuctionPropertyLifecycleIntegrationTest.php`

**Interfaces:**
- Consumes: `Property`, `Auction`, `AuctionStatus`, `auction_properties` pivot.
- Produces: `toggleStatus` modifying only `is_active`, validation rejecting sold/active properties, atomic sync of property sales status.

- [ ] **Step 1: كتابة اختبار التحقق من فصل النشاط عن الحالات ومنع بيع العقار مرتين**

```php
// tests/Feature/Tenants/Auctions/AuctionPropertyLifecycleIntegrationTest.php
public function test_toggle_status_does_not_corrupt_workflow_enum_status(): void
{
    $auction = Auction::factory()->create(['status' => AuctionStatus::COMPLETED, 'is_active' => true]);
    app(AuctionService::class)->toggleStatus($auction, false);
    $this->assertFalse($auction->fresh()->is_active);
    $this->assertSame(AuctionStatus::COMPLETED, $auction->fresh()->status);
}

public function test_cannot_add_sold_property_to_a_new_auction(): void
{
    // عقار مسجل كمباع في مزاد سابق
    // التحقق من أن StoreAuctionRequest يرفض اختياره بـ 422
}
```

- [ ] **Step 2: تشغيل الاختبار للتأكد من فشله**
Run: `php artisan test --filter="AuctionPropertyLifecycleIntegrationTest"`
Expected: FAIL

- [ ] **Step 3: تنفيذ التعديلات البرمجية**
- تعديل `AuctionService::toggleStatus`: تحديث `is_active` فقط دون لمس `status`.
- تعديل `StoreAuctionRequest`: إضافة تحقق في `property_ids.*` لمنع العقارات التي ترتبط بمزاد نشط أو حالتها مباعة (`sold` في pivot أو `status = sold`).
- تعديل `AssignmentsController::getProperties`: فلترة العقارات لتوضيح المتاح منها واستبعاد العقارات المباعة أو تمييزها بصرياً.
- تحديث `CreateAuctionAction` و `UpdateAuctionAction` لتحديث حالة العقار الحقيقية عند تسجيل نتائج البيع.

- [ ] **Step 4: تشغيل الاختبار والتأكد من نجاحه**
Run: `php artisan test --filter="AuctionPropertyLifecycleIntegrationTest"`
Expected: PASS

- [ ] **Step 5: الالتزام (Commit)**
```bash
git add app/Services/Tenants/Auctions/ app/Http/Requests/Tenants/Auctions/ app/Http/Controllers/Tenants/Assignments/ app/Actions/Tenants/Auctions/ tests/
git commit -m "fix(auctions): decouple active toggle from workflow status and prevent double selling properties"
```

---

### Task 3: مطابقة إدارة الإسنادات (Assignments T05) — الفورم المشترك والتطابق الهيكلي

**Files:**
- Create: `resources/views/tenant/assignments/partials/_form.blade.php`
- Modify: `resources/views/tenant/assignments/create.blade.php`
- Modify: `resources/views/tenant/assignments/edit.blade.php`
- Modify: `resources/views/tenant/assignments/show.blade.php:40-45`
- Modify: `app/Http/Requests/Tenants/Assignments/UpdateAssignmentRequest.php`
- Modify: `app/Actions/Tenants/Assignments/UpdateAssignmentAction.php`
- Modify: `app/Services/Tenants/Assignments/AssignmentService.php:51-76`
- Test: `tests/Feature/Tenants/Assignments/AssignmentFormParityTest.php`

**Interfaces:**
- Consumes: `$is_edit`, `Assignment`, `AssignmentPropertySelectionDataTable`.
- Produces: 4-step shared wizard for create & edit, atomic file update with safe commit.

- [ ] **Step 1: كتابة اختبار تطابق خطوات الإسناد ودورة استبدال الملفات الآمنة**

```php
// tests/Feature/Tenants/Assignments/AssignmentFormParityTest.php
public function test_create_and_edit_views_render_identical_four_steps(): void
{
    $assignment = Assignment::factory()->create();
    $createView = $this->actingAs($this->tenantUser)->get(tenant_route_name('assignments.create'));
    $editView = $this->actingAs($this->tenantUser)->get(tenant_route_name('assignments.edit', $assignment->id));

    $createView->assertSee('assignment-properties-validation');
    $editView->assertSee('assignment-properties-validation');
    $editView->assertSee('assignment-review-validation');
}
```

- [ ] **Step 2: تشغيل الاختبار للتأكد من فشله**
Run: `php artisan test --filter="AssignmentFormParityTest"`
Expected: FAIL (Edit view contains only 2 steps currently)

- [ ] **Step 3: بناء `_form.blade.php` المشترك وتحديث الـ Action/Service**
- إنشاء `resources/views/tenant/assignments/partials/_form.blade.php` يحوي الخطوات الأربع (البيانات الأساسية، العقارات المرتبطة، الملفات والمستندات، المراجعة والحفظ).
- استدعاء الفورم في `create.blade.php` (`$is_edit = false`) وفي `edit.blade.php` (`$is_edit = true`).
- إزالة `:text="العودة"` واستخدام نصوص الأزرار الافتراضية المعتمدة وتجميعها في `d-flex gap-2`.
- تحويل صورة الكيان في `show.blade.php` إلى `rounded-circle` بحجم 100×100 واستخدام fallback الشعار المشترك.
- تحديث `AssignmentService::updateAssignment` لتخزين الصورة الجديدة وتأكيدها قبل حذف الصورة السابقة.

- [ ] **Step 4: تشغيل الاختبار والتأكد من نجاحه**
Run: `php artisan test --filter="AssignmentFormParityTest"`
Expected: PASS

- [ ] **Step 5: الالتزام (Commit)**
```bash
git add resources/views/tenant/assignments/ app/Http/Requests/Tenants/Assignments/ app/Actions/Tenants/Assignments/ app/Services/Tenants/Assignments/ tests/
git commit -m "feat(assignments): unify create and edit wizard with four-step shared form parity"
```

---

### Task 4: مطابقة إدارة العقارات (Properties T06) — الفورم المشترك ودورة الملفات الآمنة

**Files:**
- Create: `resources/views/tenant/properties/partials/_form.blade.php`
- Modify: `resources/views/tenant/properties/create.blade.php`
- Modify: `resources/views/tenant/properties/edit.blade.php`
- Modify: `resources/views/tenant/properties/show.blade.php:45-50`
- Modify: `app/Http/Controllers/Tenants/Properties/PropertiesController.php:75-130`
- Modify: `app/Actions/Tenants/Properties/UpdatePropertyAction.php:55-105`
- Test: `tests/Feature/Tenants/Properties/PropertyFormParityTest.php`

**Interfaces:**
- Consumes: `$is_edit`, `Property`, boundaries, owners, units subtables.
- Produces: shared `_form.blade.php`, sanitized controller error responses, disk cleanup on image replacement.

- [ ] **Step 1: كتابة اختبار تطابق استمارة العقار وحذف الملف القديم الفعلي**

```php
// tests/Feature/Tenants/Properties/PropertyFormParityTest.php
public function test_updating_property_main_image_deletes_old_file_from_disk(): void
{
    Storage::fake('tenant_public');
    // اختبار استبدال الصورة والتأكد من عدم بقاء الملف القديم يتيماً على القرص
}
```

- [ ] **Step 2: تشغيل الاختبار للتأكد من فشله**
Run: `php artisan test --filter="PropertyFormParityTest"`
Expected: FAIL

- [ ] **Step 3: بناء الفورم المشترك ومعالجة تسريب الأخطاء والملفات**
- استخراج كود الخطوات الخمس المشتركة إلى `resources/views/tenant/properties/partials/_form.blade.php`.
- تحديث `create.blade.php` و `edit.blade.php` لتضمين الفورم الموحد عبر `$is_edit`.
- إزالة `:text="العودة"` من أزرار الويزارد وتجميع زر المسودة والتالي داخل `d-flex gap-2`.
- تصحيح رسائل الفلاش في `PropertiesController`: استبدال `$e->getMessage()` برسالة عامة ودية وتسجيل الخطأ في الـ Log.
- تعديل `UpdatePropertyAction`: استدعاء `Storage::disk()->delete()` للملف القديم عند استبدال `main_image` و `aerial_image`.
- تعديل `show.blade.php`: اعتماد `rounded-circle` للصورة الرئيسية بحجم 100×100.

- [ ] **Step 4: تشغيل الاختبار والتأكد من نجاحه**
Run: `php artisan test --filter="PropertyFormParityTest"`
Expected: PASS

- [ ] **Step 5: الالتزام (Commit)**
```bash
git add resources/views/tenant/properties/ app/Http/Controllers/Tenants/Properties/ app/Actions/Tenants/Properties/ tests/
git commit -m "feat(properties): implement shared form partial and safe storage replacement"
```

---

### Task 5: مطابقة إدارة المزادات (Auctions T07 & T08) — الفورم المشترك، الـ DataTable، وحل أخطاء الواجهة

**Files:**
- Create: `resources/views/tenant/auctions/partials/_form.blade.php`
- Modify: `resources/views/tenant/auctions/create.blade.php`
- Modify: `resources/views/tenant/auctions/edit.blade.php`
- Modify: `resources/views/tenant/auctions/show.blade.php:40-50,175-190`
- Modify: `app/DataTables/Tenants/Auctions/AuctionsDataTable.php:25-85`
- Modify: `app/Http/Controllers/Tenants/Auctions/AuctionsController.php:75-155`
- Modify: `resources/assets/js/tenant/auctions/create.js:200-210,360-370`
- Create: `app/Http/Requests/Tenants/Auctions/RetryAuctionReportsRequest.php`
- Modify: `app/Http/Controllers/Tenants/Auctions/AuctionReportController.php:110-135`
- Test: `tests/Feature/Tenants/Auctions/AuctionFormAndDataTableParityTest.php`

**Interfaces:**
- Consumes: `$is_edit`, `Auction`, `AuctionsDataTable`.
- Produces: unified `_form.blade.php`, DataTable with image column and status switch, elimination of `layout="list"`, removal of PHP 8.2 crash in edit.

- [ ] **Step 1: كتابة اختبار مطابقة جدول المزادات والواجهات الموحدة**

```php
// tests/Feature/Tenants/Auctions/AuctionFormAndDataTableParityTest.php
public function test_auctions_data_table_has_image_and_status_toggle_columns(): void
{
    $dataTable = new \App\DataTables\Tenants\Auctions\AuctionsDataTable();
    $columnNames = collect($dataTable->getColumns())->pluck('name')->all();

    $this->assertContains('image', $columnNames);
    $this->assertContains('is_active', $columnNames);
}

public function test_auctions_edit_renders_without_php_value_on_string_error(): void
{
    $auction = Auction::factory()->create();
    $response = $this->actingAs($this->tenantUser)->get(tenant_route_name('auctions.edit', $auction->id));
    $response->assertOk();
    $response->assertDontSee('under_review');
}
```

- [ ] **Step 2: تشغيل الاختبار للتأكد من فشله**
Run: `php artisan test --filter="AuctionFormAndDataTableParityTest"`
Expected: FAIL

- [ ] **Step 3: تنفيذ التعديلات البرمجية وتوحيد الواجهات**
- إنشاء `resources/views/tenant/auctions/partials/_form.blade.php` يضم الخطوات الخمس، وتوحيد العناوين وأزرار التنقل والمسودة.
- إصلاح زر الحفظ النهائي في خطوة المراجعة لـ Edit: استبدال الزر اليدوي بـ `<x-form.wizard.save-button>` وإلغاء استدعاء `$auction->status->value ?? 'under_review'`.
- في `AuctionsDataTable`:
  - إضافة عمود الصورة الدائري الموحد عبر `renderTableImage`.
  - إضافة سويتش `js-table-status-toggle` لعمود `is_active` قبل الإجراءات مباشرة.
  - إبقاء عمود مستقل لحالة الـ Workflow كـ Badge.
- في `auctions/show.blade.php`:
  - تغيير صورة الغلاف إلى `rounded-circle` بحجم 100×100.
  - استبدال `layout="list"` بـ `<x-ui.file-card :file="$attachment" layout="card" />` داخل `.assignment-file-list`.
- في `resources/assets/js/tenant/auctions/create.js`: تنظيف مستمعات القوالب الميتة واستبدال مسار `/tenant-assets/` بـ data attribute آمن.
- إنشاء `RetryAuctionReportsRequest` وتطبيقه على `AuctionReportController::retry` لحماية الطلب وصلاحية `update`.

- [ ] **Step 4: تشغيل الاختبار والتأكد من نجاحه**
Run: `php artisan test --filter="AuctionFormAndDataTableParityTest"`
Expected: PASS

- [ ] **Step 5: الالتزام (Commit)**
```bash
git add resources/views/tenant/auctions/ app/DataTables/Tenants/Auctions/ app/Http/Controllers/Tenants/Auctions/ resources/assets/js/tenant/auctions/ app/Http/Requests/Tenants/Auctions/ tests/
git commit -m "feat(auctions): implement shared form partial, datatable status toggle, and fix edit crash"
```

---

### Task 6: التحقق البصري وبناء الواجهات وتأكيد الاختبارات الأفقية (Verification Gate G3)

**Files:**
- Modify: `docs/baselines/golden-pattern-manifest.md`
- Test: All suites for Asset Modules

- [ ] **Step 1: بناء الأصول والواجهات**
Run: `npm run build`
Expected: Build successful with zero Vite errors.

- [ ] **Step 2: تشغيل الاختبارات المركزة للإدارات الثلاث**
Run: `php artisan test --filter="Assignment|Property|Auction"`
Expected: All tests pass.

- [ ] **Step 3: تشغيل اختبارات العقود الأفقية للنمط الذهبي**
Run: `php artisan test --filter="DataTableContract|SharedFormValidation|CreateFormDraft|ActiveRouteHandlerContractFeatureTest"`
Expected: All contract tests pass.

- [ ] **Step 4: تحديث بطاقات الإدارات وسجل المطابقة**
تحديث `docs/baselines/golden-pattern-manifest.md` وتوثيق أدلة الإنجاز لـ Assignments, Properties, Auctions كـ `Compliant` حقيقي ومبرهن.

- [ ] **Step 5: الالتزام النهائي (Commit)**
```bash
git add docs/baselines/golden-pattern-manifest.md
git commit -m "docs(manifest): certify compliance for assignments, properties, and auctions with full verification evidence"
```
