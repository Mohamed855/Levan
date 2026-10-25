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
            ['id' => 'step-settings', 'title' => 'الإعدادات والملفات'],
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
                    <x-form.wizard.step id="step-assignment" :fields="['assignment_id', 'supervising_entity', 'platform_name', 'platform_url', 'notes']">
                        <div class="row g-4">
                            <x-form.controls.select name="assignment_id" id="assignment_id" label="الإسناد المرتبط بالمزاد"
                                required col="col-md-6" placeholder="اختر الإسناد المسجل..." :value="$auction->assignment_id" :options="$assignments
                                    ->map(
                                        fn($a) => [
                                            'value' => $a->id,
                                            'label' => $a->name . ' (' . $a->assignment_number . ')',
                                        ],
                                    )
                                    ->toArray()">
                                <x-slot:append>
                                    <div id="selected-assignment-card"
                                        class="card bg-label-primary border-primary mt-3 d-none">
                                        <div class="card-body p-3">
                                            <h6 class="fw-bold mb-2"><i class="ti ti-file-certificate me-1"></i>بيانات الإسناد المختار:</h6>
                                            <div class="small">
                                                <div><strong>رقم الإسناد:</strong> <span id="asg-summary-num">-</span></div>
                                                <div><strong>الجهة المسندة:</strong> <span id="asg-summary-entity">-</span></div>
                                                <div><strong>الحالة:</strong> <span id="asg-summary-status">-</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </x-slot:append>
                            </x-form.controls.select>

                            <x-form.controls.input name="supervising_entity" id="supervising_entity"
                                label="الجهة المشرفة على المزاد" placeholder="مثال: مركز الإسناد والتصفية - إنفاذ" :value="$auction->supervising_entity" required
                                col="col-md-6" />

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
                                    <h5 class="mb-0">العقارات المرتبطة بالإسناد المختار</h5>
                                    <small class="text-muted">حدد العقارات التي سيتم إدراجها رسمياً في هذا المزاد.</small>
                                </div>
                                <div class="d-flex gap-3 text-start">
                                    <div class="badge bg-label-primary fs-6 p-2">المحددة: <span
                                            id="selected-props-count">0</span></div>
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
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                يرجى اختيار الإسناد في الخطوة السابقة لعرض عقاراته التابعة
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="property_ids-error" class="invalid-feedback d-block" data-form-error role="alert"
                                hidden></div>
                        </div>

                        <section class="assignment-selection mt-4" aria-live="polite">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="mb-0">العقارات المحددة</h5>
                                <span class="badge bg-label-primary"><span id="selected-properties-count">0</span> عقار</span>
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

                    {{-- الخطوة 4: الإعدادات والملفات --}}
                    <x-form.wizard.step id="step-settings" :fields="['template_versions', 'cover_image', 'attachments']">
                        <div class="row g-4">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3"><i class="ti ti-palette me-1"></i>القوالب التسويقية المنشورة والمعتمدة:</h6>
                            </div>

                            <x-form.controls.select name="template_versions[brochure]" id="template_versions_brochure"
                                label="قالب البروشور المعتمد" col="col-md-4" :value="$auction->template_versions['brochure'] ?? $auction->brochure_template_id"
                                :options="$templateOptions['brochure'] ?? $brochureTemplates" placeholder="اختر نسخة قالب البروشور..." />

                            <x-form.controls.select name="template_versions[billboard]" id="template_versions_billboard"
                                label="قالب اللوحة الإعلانية" col="col-md-4" :value="$auction->template_versions['billboard'] ?? $auction->billboard_template_id"
                                :options="$templateOptions['billboard'] ?? $billboardTemplates" placeholder="اختر نسخة قالب اللوحة..." />

                            <x-form.controls.select name="template_versions[social]" id="template_versions_social"
                                label="قالب منصات التواصل" col="col-md-4" :value="$auction->template_versions['social'] ?? $auction->social_template_id"
                                :options="$templateOptions['social'] ?? $socialTemplates" placeholder="اختر نسخة قالب منصات التواصل..." />

                            <div class="col-12">
                                <x-form.controls.file name="cover_image" label="تحديث صورة غلاف المزاد (للاستخدام في القوالب)"
                                    accept="image/*" col="col-md-6"
                                    help="صورة تظهر في مخرجات المزاد والقوالب التسويقية" />
                            </div>

                            <div class="col-12 mt-4 border-top pt-3">
                                <h6 class="fw-bold mb-3"><i class="ti ti-paperclip me-1"></i>مرفقات ووثائق المزاد:</h6>
                                <x-form.controls.file name="attachments[]" label="ملفات إضافية" multiple
                                    :required="false" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx"
                                    help="الحد الأقصى لكل ملف: 10MB" col="col-md-6" />

                                @if ($auction->attachments->isNotEmpty())
                                    <div class="card bg-label-secondary mb-3">
                                        <div class="card-body p-3">
                                            <h6 class="mb-2 fw-semibold">المرفقات الحالية المسجلة:</h6>
                                            <ul class="mb-0 ps-3">
                                                @foreach ($auction->attachments as $attachment)
                                                    <li>{{ $attachment->file_name }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif

                                <section class="assignment-properties-table-card mt-3" aria-live="polite">
                                    <header class="assignment-properties-table-card__header">
                                        <h6 class="mb-0">المرفقات والوثائق الجديدة</h6>
                                        <span id="auction-attachments-count" class="badge bg-label-primary">0 ملف</span>
                                    </header>
                                    <div id="auction-attachments-cards" class="assignment-file-list p-3">
                                        <p class="text-muted mb-0">لم يتم إرفاق ملفات بعد.</p>
                                    </div>
                                </section>
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
                                    <div><strong>النوع والتصنيف:</strong> <span id="review-type">{{ $auction->type?->label() ?? $auction->type }}</span> / <span
                                            id="review-category">{{ $auction->category?->label() ?? $auction->category }}</span></div>
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
                                    <div><strong>المنصة:</strong> <span id="review-platform">{{ $auction->platform_name ?: '-' }}</span></div>
                                </x-form.wizard.review-card>
                                <x-form.wizard.review-card title="العقارات المشمولة" step="3"
                                    class="assignment-review__card">
                                    <div><strong>العقارات المحددة:</strong> <span id="review-selected-props-count">{{ $auction->properties->count() }} عقارات</span></div>
                                </x-form.wizard.review-card>
                                <x-form.wizard.review-card title="الإعدادات والملفات" step="4"
                                    class="assignment-review__card">
                                    <div><strong>قالب البروشور:</strong> <span id="review-brochure-template">-</span></div>
                                    <div><strong>قالب اللوحة الإعلانية:</strong> <span
                                            id="review-billboard-template">-</span></div>
                                    <div><strong>قالب منصات التواصل:</strong> <span id="review-social-template">-</span>
                                    </div>
                                    <div><strong>المرفقات والوثائق:</strong> <span id="review-attachments-count">{{ $auction->attachments->count() }} ملفات</span></div>
                                </x-form.wizard.review-card>
                            </div>
                            <div class="col-lg-3">
                                <article class="card assignment-preview h-100">
                                    <div
                                        class="assignment-preview__image bg-primary d-flex flex-column justify-content-center p-4 text-white">
                                        <h5 class="text-white mb-1" id="preview-auction-name">{{ $auction->name }}</h5>
                                        <small class="text-white-50" id="preview-auction-category">{{ $auction->category?->label() ?? $auction->category }}</small>
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
