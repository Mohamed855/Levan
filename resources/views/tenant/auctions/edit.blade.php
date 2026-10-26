@extends('tenant.layouts.layoutMaster')

@section('title', 'تعديل المزاد - ' . $auction->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css', 'resources/assets/scss/tenant/pages/assignment-create.scss'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenant/auctions/create.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('wizard-validation-form');
            if (form) {
                const initialData = {
                    assignment_id: '{{ $auction->assignment_id }}',
                    property_ids: @json($auction->properties->pluck('id')->map(fn($id) => (string)$id)->all())
                };
                $(form).trigger({
                    type: 'wizardDraftRestored',
                    detail: { data: initialData }
                });
            }
        });
    </script>
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'step-basic-info', 'title' => 'البيانات الأساسية'],
            ['id' => 'step-assignment', 'title' => 'الإسناد والمنصة'],
            ['id' => 'step-properties', 'title' => 'العقارات المرتبطة'],
            ['id' => 'step-settings', 'title' => 'النتائج والملفات'],
            ['id' => 'step-review', 'title' => 'المراجعة والحفظ'],
        ];
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <x-form.wizard.stepper id="wizard-validation" class="mt-2" :steps="$steps">
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="wizard-validation-form" action="{{ tenant_route_name('auctions.update', $auction->id) }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                    data-assignment-properties-url="{{ tenant_route_name('assignments.properties', ':id') }}"
                    data-property-image-fallback="{{ asset('static/brand/logos/logo.svg') }}">
                    @csrf
                    @method('PUT')

                    {{-- الخطوة 1: البيانات الأساسية --}}
                    <x-form.wizard.step id="step-basic-info" :fields="[
                        'name',
                        'description',
                        'start_date',
                        'start_time',
                        'end_date',
                        'end_time',
                        'type',
                        'category',
                        'city',
                        'expected_properties_count',
                    ]">
                        <div class="row g-4">
                            <x-form.controls.input name="name" label="اسم المزاد" placeholder="مثال: مزاد جوار الحرم"
                                :value="$auction->name" required col="col-md-6" />

                            <x-form.controls.textarea name="description" label="وصف المزاد"
                                placeholder="اكتب وصفاً توضيحياً للمزاد..." :value="$auction->description" required col="col-md-6" rows="3" />

                            <x-form.controls.input name="start_date" type="date" label="تاريخ بداية المزاد" :value="$auction->start_date?->format('Y-m-d')" required
                                col="col-md-6" />
                            <x-form.controls.input name="start_time" type="time" label="وقت البداية" :value="$auction->start_time" required
                                col="col-md-6" />

                            <x-form.controls.input name="end_date" type="date" label="تاريخ نهاية المزاد" :value="$auction->end_date?->format('Y-m-d')" required
                                col="col-md-6" />
                            <x-form.controls.input name="end_time" type="time" label="وقت النهاية" :value="$auction->end_time" required
                                col="col-md-6" />

                            <x-form.controls.select name="type" label="نوع المزاد" required col="col-md-6"
                                :value="$auction->type" :enum="\App\Enums\Tenants\Auctions\AuctionType::class" />

                            <x-form.controls.select name="category" label="تصنيف المزاد" required col="col-md-6"
                                :value="$auction->category" :enum="\App\Enums\Tenants\Auctions\AuctionCategory::class" />

                            <x-form.controls.select name="city" label="المدينة الرئيسية" required col="col-md-6"
                                :value="$auction->city" :options="$cityOptions" />

                            <x-form.controls.input name="expected_properties_count" type="number"
                                label="عدد العقارات المتوقع" :value="$auction->expected_properties_count ?? 1" min="1" required col="col-md-6" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="tenant_route_name('auctions.index')" text="العودة" />
                            <div class="d-flex gap-2">
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 2: الإسناد والجهات والمنصة --}}
                    <x-form.wizard.step id="step-assignment" :fields="['assignment_id', 'supervising_entity', 'project_manager_name', 'platform_name', 'platform_url', 'notes']">
                        <div class="row g-4">
                            {{-- الإسناد: readonly لأنه غير قابل للتغيير بعد الإنشاء --}}
                            <div class="col-md-6">
                                <label class="form-label">الإسناد المرتبط بالمزاد</label>
                                <input type="text" class="form-control" readonly
                                    value="{{ $auction->assignment?->name }} ({{ $auction->assignment?->assignment_number }})">
                                <input type="hidden" name="assignment_id" value="{{ $auction->assignment_id }}">
                                <div class="form-text">لا يمكن تغيير الإسناد بعد إنشاء المزاد.</div>
                            </div>

                            <x-form.controls.input name="supervising_entity" id="supervising_entity"
                                label="الجهة المشرفة على المزاد" placeholder="مثال: مركز الإسناد والتصفية - إنفاذ"
                                :value="$auction->supervising_entity" required col="col-md-6" />

                            <x-form.controls.input name="project_manager_name" label="اسم مدير المشروع"
                                placeholder="مثال: محمد عبدالله" :value="$auction->project_manager_name" required col="col-md-6" />

                            <x-form.controls.input name="platform_name" label="منصة المزاد"
                                placeholder="مثال: منصة مباشر للمزادات" :value="$auction->platform_name" required col="col-md-6" />

                            <x-form.controls.input name="platform_url" type="url" label="رابط المزاد على المنصة"
                                placeholder="https://mubasher.sa/auctions/..." :value="$auction->platform_url" col="col-md-6" />

                            <x-form.controls.textarea name="notes" label="ملاحظات الجهة والمنصة"
                                placeholder="أي تفاصيل أو اشتراطات خاصة بالجهة أو المنصة..." :value="$auction->notes" col="col-md-6"
                                rows="3" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 3: العقارات المرتبطة --}}
                    <x-form.wizard.step id="step-properties" :fields="['property_ids']">
                        <div data-form-field data-field-name="property_ids">
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">العقارات المرتبطة بالإسناد</h5>
                                    <small class="text-muted">حدد العقارات التي سيتم إدراجها رسمياً في هذا المزاد.</small>
                                </div>
                                <div class="d-flex gap-3 text-start">
                                    <div class="badge bg-label-primary fs-6 p-2">المحددة: <span
                                            id="selected-props-count">{{ $auction->properties->count() }}</span></div>
                                    <div class="badge bg-label-info fs-6 p-2">إجمالي المساحة: <span
                                            id="selected-props-area">0 م²</span></div>
                                    <div class="badge bg-label-success fs-6 p-2">إجمالي القيمة: <span
                                            id="selected-props-val">0 ر.س</span></div>
                                </div>
                            </div>

                            <div class="table-responsive border rounded mb-4">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 2.5rem;">
                                                <input type="checkbox" id="select-all-properties"
                                                    class="form-check-input">
                                            </th>
                                            <th>رقم العقار</th>
                                            <th>اسم العقار</th>
                                            <th>المدينة / الحي</th>
                                            <th>المساحة</th>
                                            <th>القيمة التقديرية</th>
                                        </tr>
                                    </thead>
                                    <tbody id="properties-container">
                                        @forelse($properties as $prop)
                                            @php $selected = $auction->properties->contains($prop->id); @endphp
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="property_ids[]"
                                                        value="{{ $prop->id }}"
                                                        class="form-check-input property-checkbox"
                                                        {{ $selected ? 'checked' : '' }}>
                                                </td>
                                                <td><code>{{ $prop->property_number }}</code></td>
                                                <td>{{ $prop->name }}</td>
                                                <td>-</td>
                                                <td>-</td>
                                                <td>-</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    لا توجد عقارات تابعة للإسناد المحدد
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div id="property_ids-error" class="invalid-feedback d-block" data-form-error role="alert"
                                hidden></div>
                        </div>

                        <section class="assignment-selection mt-4" aria-live="polite">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="mb-0">العقارات المحددة</h5>
                                <span class="badge bg-label-primary"><span id="selected-properties-count">{{ $auction->properties->count() }}</span> عقار</span>
                            </div>
                            <div id="selected-properties-cards" class="assignment-selection__items">
                                <p class="text-muted mb-0" data-empty>لم يتم اختيار عقارات بعد.</p>
                            </div>
                        </section>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 4: النتائج والتسويق والملفات --}}
                    <x-form.wizard.step id="step-settings" :fields="['report_image', 'cover_image', 'property_results', 'marketing', 'attachments', 'marketing_evidence']">
                        <div class="row g-4">
                            {{-- صور المزاد والتقرير --}}
                            <div class="col-12">
                                <h6 class="fw-bold mb-3"><i class="ti ti-photo me-1"></i>صور التقرير والمزاد</h6>
                            </div>

                            {{-- صورة التقرير --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    صورة غلاف التقرير
                                    @if ($auction->report_image)
                                        <span class="badge bg-label-success ms-1">محفوظة</span>
                                    @else
                                        <span class="badge bg-label-danger ms-1">مطلوبة</span>
                                    @endif
                                </label>
                                <x-form.controls.file name="report_image" label="صورة غلاف التقرير"
                                    :value="$auction->report_image ? tasset($auction->report_image) : null"
                                    help="{{ $auction->report_image ? 'اختياري للتحديث — ارفع صورة جديدة للاستبدال' : 'مطلوبة — تظهر في غلاف ملف تقرير المزاد' }}"
                                    :required="!$auction->report_image" accept="image/jpeg,image/png,image/webp" col="col-12" />
                            </div>

                            {{-- صورة الغلاف --}}
                            <div class="col-md-6">
                                <x-form.controls.file name="cover_image" label="صورة غلاف المزاد"
                                    :value="$auction->cover_image ? tasset($auction->cover_image) : null"
                                    help="اختياري — تظهر في مخرجات المزاد الرئيسية"
                                    accept="image/*" col="col-12" />
                            </div>

                            {{-- نتائج العقارات --}}
                            <div class="col-12 border-top pt-3">
                                <h6 class="fw-bold mb-3"><i class="ti ti-chart-bar me-1"></i>نتائج عقارات المزاد</h6>
                                <p class="text-muted small mb-3">سجّل أو حدّث نتيجة كل عقار في المزاد.</p>
                                <div id="property-results-container">
                                    @forelse($auction->properties as $prop)
                                        @php
                                            $pivot = $prop->pivot;
                                            $resultStatus = $pivot->result_status ?? 'pending';
                                        @endphp
                                        <div class="card mb-3 border" data-property-result="{{ $prop->id }}">
                                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                                                <h6 class="mb-0">{{ $prop->name }} <code class="ms-1">{{ $prop->property_number }}</code></h6>
                                                <span class="badge {{ $resultStatus === 'sold' ? 'bg-success' : ($resultStatus === 'unsold' ? 'bg-danger' : 'bg-warning') }}">
                                                    {{ $resultStatus === 'sold' ? 'مُباع' : ($resultStatus === 'unsold' ? 'لم يُباع' : 'قيد الانتظار') }}
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">الحالة</label>
                                                        <select name="property_results[{{ $prop->id }}][status]" class="form-select">
                                                            <option value="pending" {{ $resultStatus === 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                                                            <option value="sold" {{ $resultStatus === 'sold' ? 'selected' : '' }}>مُباع</option>
                                                            <option value="unsold" {{ $resultStatus === 'unsold' ? 'selected' : '' }}>لم يُباع</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">سعر الافتتاح</label>
                                                        <input type="number" class="form-control" name="property_results[{{ $prop->id }}][opening_price]"
                                                            value="{{ $pivot->opening_price }}" min="0" step="0.01">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">سعر البيع</label>
                                                        <input type="number" class="form-control" name="property_results[{{ $prop->id }}][sale_price]"
                                                            value="{{ $pivot->sale_price }}" min="0" step="0.01">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">عدد المشاركين</label>
                                                        <input type="number" class="form-control" name="property_results[{{ $prop->id }}][participants_count]"
                                                            value="{{ $pivot->participants_count }}" min="0">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">عدد المزايدات</label>
                                                        <input type="number" class="form-control" name="property_results[{{ $prop->id }}][bids_count]"
                                                            value="{{ $pivot->bids_count }}" min="0">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">سبب عدم البيع</label>
                                                        <input type="text" class="form-control" name="property_results[{{ $prop->id }}][unsold_reason]"
                                                            value="{{ $pivot->unsold_reason }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted">لا توجد عقارات مرتبطة بعد.</p>
                                    @endforelse
                                </div>
                            </div>

                            {{-- بيانات التسويق --}}
                            <div class="col-12 border-top pt-3">
                                <h6 class="fw-bold mb-3"><i class="ti ti-speakerphone me-1"></i>بيانات التسويق والحملات</h6>

                                <section class="assignment-properties-table-card mb-4" aria-live="polite">
                                    <header class="assignment-properties-table-card__header">
                                        <h6 class="mb-0">حملات التسويق</h6>
                                        <button type="button" class="btn btn-sm btn-primary" id="add-campaign-btn">
                                            <i class="ti ti-plus me-1"></i> إضافة حملة
                                        </button>
                                    </header>
                                    <div class="p-3">
                                        <div id="campaigns-container">
                                            @if (!empty($auction->marketing_data['campaigns']))
                                                @foreach($auction->marketing_data['campaigns'] as $i => $campaign)
                                                    <div class="row g-2 mb-3 border-bottom pb-3 campaign-row" data-index="{{ $i }}">
                                                        <div class="col-md-3">
                                                            <input type="text" class="form-control" name="marketing[campaigns][{{ $i }}][platform]"
                                                                placeholder="المنصة" value="{{ $campaign['platform'] ?? '' }}" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <input type="number" class="form-control" name="marketing[campaigns][{{ $i }}][reach]"
                                                                placeholder="الوصول" value="{{ $campaign['reach'] ?? '' }}" min="0">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <input type="number" class="form-control" name="marketing[campaigns][{{ $i }}][impressions]"
                                                                placeholder="المشاهدات" value="{{ $campaign['impressions'] ?? '' }}" min="0">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <input type="number" class="form-control" name="marketing[campaigns][{{ $i }}][engagements]"
                                                                placeholder="التفاعل" value="{{ $campaign['engagements'] ?? '' }}" min="0">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <input type="number" class="form-control" name="marketing[campaigns][{{ $i }}][clicks]"
                                                                placeholder="النقرات" value="{{ $campaign['clicks'] ?? '' }}" min="0">
                                                        </div>
                                                        <div class="col-md-1">
                                                            <button type="button" class="btn btn-icon btn-sm btn-label-danger remove-campaign">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </div>
                                                        <div class="col-12">
                                                            <input type="url" class="form-control" name="marketing[campaigns][{{ $i }}][url]"
                                                                placeholder="رابط الحملة" value="{{ $campaign['url'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <p class="text-muted mb-0" id="campaigns-empty">لا توجد حملات مضافة بعد.</p>
                                            @endif
                                        </div>
                                    </div>
                                </section>

                                <section class="assignment-properties-table-card mb-4" aria-live="polite">
                                    <header class="assignment-properties-table-card__header">
                                        <h6 class="mb-0">روابط التسويق</h6>
                                        <button type="button" class="btn btn-sm btn-primary" id="add-link-btn">
                                            <i class="ti ti-plus me-1"></i> إضافة رابط
                                        </button>
                                    </header>
                                    <div class="p-3">
                                        <div id="links-container">
                                            @if (!empty($auction->marketing_data['links']))
                                                @foreach($auction->marketing_data['links'] as $i => $link)
                                                    <div class="row g-2 mb-2 link-row" data-index="{{ $i }}">
                                                        <div class="col-md-4">
                                                            <input type="text" class="form-control" name="marketing[links][{{ $i }}][label]"
                                                                placeholder="التسمية" value="{{ $link['label'] ?? '' }}" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="url" class="form-control" name="marketing[links][{{ $i }}][url]"
                                                                placeholder="الرابط" value="{{ $link['url'] ?? '' }}" required>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <button type="button" class="btn btn-icon btn-sm btn-label-danger remove-link">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <p class="text-muted mb-0" id="links-empty">لا توجد روابط مضافة بعد.</p>
                                            @endif
                                        </div>
                                    </div>
                                </section>

                                <x-form.controls.file name="marketing_evidence[]" label="أدلة وصور حملات التسويق (جديدة)" multiple
                                    :required="false" accept=".pdf,.png,.jpg,.jpeg,.webp"
                                    help="صور الإعلانات والمنشورات والإثباتات (الحد الأقصى لكل ملف: 10MB)"
                                    col="col-md-6" />
                            </div>

                            <div class="col-12 border-top pt-3">
                                <h6 class="fw-bold mb-3"><i class="ti ti-billboard me-1"></i>صور اللوحات الإعلانية</h6>
                                @if ($auction->attachments->whereIn('report_section', ['board_before', 'board_after'])->isNotEmpty())
                                    <div class="alert alert-info py-2">الصور المسجلة حاليًا محفوظة، ويمكن إضافة صور أخرى لكل عقار أدناه.</div>
                                @endif
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">صور اللوحات قبل الفك</label>
                                        <div id="board-before-container"><p class="text-muted small">اختر العقارات لإضافة الصور.</p></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">صور اللوحات بعد الفك</label>
                                        <div id="board-after-container"><p class="text-muted small">اختر العقارات لإضافة الصور.</p></div>
                                    </div>
                                </div>
                            </div>

                            {{-- المرفقات الرسمية --}}
                            <div class="col-12 border-top pt-3">
                                <h6 class="fw-bold mb-3"><i class="ti ti-paperclip me-1"></i>مرفقات ووثائق المزاد</h6>

                                @if ($auction->attachments->where('category', 'auction_doc')->isNotEmpty())
                                    <div class="card bg-label-secondary mb-3">
                                        <div class="card-body p-3">
                                            <h6 class="mb-2 fw-semibold">المرفقات الحالية المسجلة:</h6>
                                            <ul class="mb-0 ps-3">
                                                @foreach ($auction->attachments->where('category', 'auction_doc') as $attachment)
                                                    <li>{{ $attachment->file_name }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif

                                <x-form.controls.file name="attachments[]" label="ملفات إضافية جديدة" multiple
                                    :required="false" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx"
                                    help="الحد الأقصى لكل ملف: 10MB" col="col-12" />
                            </div>
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 5: المراجعة والحفظ --}}
                    <x-form.wizard.step id="step-review" :fields="['status']">
                        <div class="row g-4 mb-4 assignment-review">
                            <div class="col-lg-9 d-flex flex-column gap-4">
                                <x-form.wizard.review-card title="البيانات الأساسية" step="1"
                                    class="assignment-review__card">
                                    <div><strong>اسم المزاد:</strong> <span id="review-name">{{ $auction->name }}</span></div>
                                    <div><strong>النوع والتصنيف:</strong> <span id="review-type">{{ $auction->type_label }}</span> / <span
                                            id="review-category">{{ $auction->category_label }}</span></div>
                                    <div><strong>المدينة:</strong> <span id="review-city">{{ $auction->city }}</span></div>
                                    <div><strong>الفترة:</strong> <span id="review-dates">{{ $auction->start_date?->format('Y-m-d') }} إلى {{ $auction->end_date?->format('Y-m-d') }}</span></div>
                                    <div><strong>الوصف:</strong> <span id="review-description">{{ $auction->description ?: '-' }}</span></div>
                                    <div><strong>عدد العقارات المتوقع:</strong> <span
                                            id="review-expected-properties">{{ $auction->expected_properties_count }}</span></div>
                                </x-form.wizard.review-card>
                                <x-form.wizard.review-card title="الإسناد والمنصة" step="2"
                                    class="assignment-review__card">
                                    <div><strong>الإسناد:</strong> <span id="review-assignment-name">{{ $auction->assignment?->name ?: '-' }}</span></div>
                                    <div><strong>الجهة المشرفة:</strong> <span id="review-supervising">{{ $auction->supervising_entity ?: '-' }}</span></div>
                                    <div><strong>مدير المشروع:</strong> <span id="review-project-manager">{{ $auction->project_manager_name ?: '-' }}</span></div>
                                    <div><strong>المنصة:</strong> <span id="review-platform">{{ $auction->platform_name ?: '-' }}</span></div>
                                </x-form.wizard.review-card>
                                <x-form.wizard.review-card title="العقارات المشمولة" step="3"
                                    class="assignment-review__card">
                                    <div><strong>العقارات المحددة:</strong> <span id="review-selected-props-count">{{ $auction->properties->count() }} عقارات</span></div>
                                </x-form.wizard.review-card>
                                <x-form.wizard.review-card title="النتائج والملفات" step="4"
                                    class="assignment-review__card">
                                    <div><strong>صورة التقرير:</strong>
                                        <span id="review-report-image">{{ $auction->report_image ? 'محفوظة' : 'غير محددة (مطلوبة)' }}</span>
                                    </div>
                                    <div><strong>صورة الغلاف:</strong>
                                        <span id="review-cover-image">{{ $auction->cover_image ? 'محفوظة' : 'غير محددة' }}</span>
                                    </div>
                                    <div><strong>المرفقات المسجلة:</strong>
                                        <span id="review-attachments-count">{{ $auction->attachments->count() }} ملف</span>
                                    </div>
                                </x-form.wizard.review-card>
                            </div>
                            <div class="col-lg-3">
                                <article class="card assignment-preview h-100">
                                    <div
                                        class="assignment-preview__image bg-primary d-flex flex-column justify-content-center p-4 text-white">
                                        <h5 class="text-white mb-1" id="preview-auction-name">{{ $auction->name }}</h5>
                                        <small class="text-white-50" id="preview-auction-category">{{ $auction->category_label }}</small>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1" id="preview-auction-city">{{ $auction->city }}</p>
                                        <p class="text-muted mb-0" id="preview-auction-date">{{ $auction->start_date?->format('Y-m-d') }}</p>
                                    </div>
                                </article>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy me-1"></i> حفظ وتحديث المزاد
                                </button>
                            </div>
                        </div>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
