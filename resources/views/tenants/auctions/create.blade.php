@extends('tenants.layouts.layoutMaster')

@section('title', 'إنشاء مزاد جديد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenant/auctions/create.js'])
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'step-basic-info', 'title' => 'البيانات الأساسية'],
            ['id' => 'step-assignment', 'title' => 'الإسناد والمنصة'],
            ['id' => 'step-properties', 'title' => 'العقارات المرتبطة'],
            ['id' => 'step-settings', 'title' => 'الإعدادات والملفات'],
            ['id' => 'step-review', 'title' => 'المراجعة والإرسال'],
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

                <form id="wizard-validation-form" action="{{ tenant_route_name('auctions.store') }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                    data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
                    data-draft-file-url="{{ tenant_route_name('wizard-drafts.file.destroy') }}"
                    data-assignment-properties-url="{{ tenant_route_name('assignments.properties', ':id') }}"
                    data-draft-resource-key="tenant.auction-create">
                    @csrf

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
                        'cover_image',
                    ]">
                        <div class="row g-4">
                            <x-form.controls.input name="name" label="اسم المزاد" placeholder="مثال: مزاد جوار الحرم"
                                required col="col-12" />

                            <x-form.controls.textarea name="description" label="وصف المزاد"
                                placeholder="اكتب وصفاً توضيحياً للمزاد..." col="col-12" rows="3" />

                            <x-form.controls.input name="start_date" type="date" label="تاريخ بداية المزاد" required
                                col="col-md-6" />
                            <x-form.controls.input name="start_time" type="time" label="وقت البداية" col="col-md-6" />

                            <x-form.controls.input name="end_date" type="date" label="تاريخ نهاية المزاد" required
                                col="col-md-6" />
                            <x-form.controls.input name="end_time" type="time" label="وقت النهاية" col="col-md-6" />

                            <x-form.controls.select name="type" label="نوع المزاد" required col="col-md-4"
                                :enum="\App\Enums\Tenants\Auctions\AuctionType::class" />

                            <x-form.controls.select name="category" label="تصنيف المزاد" required col="col-md-4"
                                :enum="\App\Enums\Tenants\Auctions\AuctionCategory::class" />

                            <x-form.controls.select name="city" label="المدينة الرئيسية" required col="col-md-4"
                                :options="$cityOptions" />

                            <x-form.controls.input name="expected_properties_count" type="number"
                                label="عدد العقارات المتوقع" :value="1" min="1" col="col-md-6" />

                            <x-form.controls.image-upload name="cover_image" label="صورة الغلاف للمزاد"
                                accept="image/jpeg,image/png,image/webp" hint="JPG، PNG أو WebP. الحد الأقصى 4MB"
                                col="col-md-6" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 2: الإسناد والجهات والمنصة --}}
                    <x-form.wizard.step id="step-assignment" :fields="['assignment_id', 'supervising_entity', 'platform_name', 'platform_url', 'notes']">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <x-form.controls.select name="assignment_id" id="assignment_id"
                                    label="الإسناد المرتبط بالمزاد" required col="col-12"
                                    placeholder="اختر الإسناد المسجل..." :options="$assignments
                                        ->map(
                                            fn($a) => [
                                                'value' => $a->id,
                                                'label' => $a->name . ' (' . $a->assignment_number . ')',
                                            ],
                                        )
                                        ->toArray()" />

                                {{-- كارت ملخص الإسناد المختار --}}
                                <div id="selected-assignment-card" class="card bg-label-primary border-primary mt-3 d-none">
                                    <div class="card-body p-3">
                                        <h6 class="fw-bold mb-2"><i class="ti ti-file-certificate me-1"></i>بيانات الإسناد
                                            المختار:</h6>
                                        <div class="small">
                                            <div><strong>رقم الإسناد:</strong> <span id="asg-summary-num">-</span></div>
                                            <div><strong>الجهة المسندة:</strong> <span id="asg-summary-entity">-</span>
                                            </div>
                                            <div><strong>الحالة:</strong> <span id="asg-summary-status">-</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <x-form.controls.input name="supervising_entity" id="supervising_entity"
                                    label="الجهة المشرفة على المزاد" placeholder="مثال: مركز الإسناد والتصفية - إنفاذ"
                                    required col="col-12" />
                            </div>

                            <x-form.controls.input name="platform_name" label="منصة المزاد"
                                placeholder="مثال: منصة مباشر للمزادات" col="col-md-6" />

                            <x-form.controls.input name="platform_url" type="url" label="رابط المزاد على المنصة"
                                placeholder="https://mubasher.sa/auctions/..." col="col-md-6" />

                            <x-form.controls.textarea name="notes" label="ملاحظات الجهة والمنصة"
                                placeholder="أي تفاصيل أو اشتراطات خاصة بالجهة أو المنصة..." col="col-12"
                                rows="3" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
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
                                            id="selected-props-area">0
                                            م²</span></div>
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

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 4: الإعدادات والملفات --}}
                    <x-form.wizard.step id="step-settings" :fields="['brochure_template_id', 'billboard_template_id', 'social_template_id', 'attachments']">
                        <div class="row g-4">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3"><i class="ti ti-palette me-1"></i>القوالب التسويقية المعتمدة:
                                </h6>
                            </div>

                            <x-form.controls.select name="brochure_template_id" label="قالب البروشور" col="col-md-4"
                                :enum="\App\Enums\Tenants\Auctions\BrochureTemplate::class" />

                            <x-form.controls.select name="billboard_template_id" label="قالب اللوحة الإعلانية"
                                col="col-md-4" :enum="\App\Enums\Tenants\Auctions\BillboardTemplate::class" />

                            <x-form.controls.select name="social_template_id" label="قالب منصات التواصل" col="col-md-4"
                                :enum="\App\Enums\Tenants\Auctions\SocialTemplate::class" />

                            <div class="col-12 mt-4 border-top pt-3">
                                <h6 class="fw-bold mb-3"><i class="ti ti-paperclip me-1"></i>مرفقات ووثائق المزاد:</h6>
                                <input type="file" name="attachments[]" class="form-control" multiple
                                    data-form-control accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" />
                                <small class="text-muted d-block mt-1">الملفات المدعومة: PDF, PNG, JPG, DOC, XLS (الحد
                                    الأقصى لكل ملف: 10MB)</small>
                            </div>
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 5: المراجعة والإرسال --}}
                    <x-form.wizard.step id="step-review" :fields="['status']">
                        <div class="row g-4 mb-4 assignment-review">
                            <div class="col-lg-9 d-flex flex-column gap-4">
                                <x-form.wizard.review-card title="البيانات الأساسية" step="1"
                                    class="assignment-review__card">
                                    <div><strong>اسم المزاد:</strong> <span id="review-name">-</span></div>
                                    <div><strong>النوع والتصنيف:</strong> <span id="review-type">-</span> / <span
                                            id="review-category">-</span></div>
                                    <div><strong>المدينة:</strong> <span id="review-city">-</span></div>
                                    <div><strong>الفترة:</strong> <span id="review-dates">-</span></div>
                                </x-form.wizard.review-card>
                                <x-form.wizard.review-card title="الإسناد والمنصة" step="2"
                                    class="assignment-review__card">
                                    <div><strong>الإسناد:</strong> <span id="review-assignment-name">-</span></div>
                                    <div><strong>الجهة المشرفة:</strong> <span id="review-supervising">-</span></div>
                                </x-form.wizard.review-card>
                                <x-form.wizard.review-card title="العقارات المشمولة" step="3"
                                    class="assignment-review__card">
                                    <div><strong>العقارات المحددة:</strong> <span id="review-selected-props-count">0
                                            عقارات</span></div>
                                </x-form.wizard.review-card>
                                <x-form.wizard.review-card title="الإعدادات والملفات" step="4"
                                    class="assignment-review__card">
                                    <div><strong>القوالب المعتمدة:</strong> تم التحديد</div>
                                </x-form.wizard.review-card>
                            </div>
                            <div class="col-lg-3">
                                <article class="card assignment-preview h-100">
                                    <div
                                        class="assignment-preview__image bg-primary d-flex flex-column justify-content-center p-4 text-white">
                                        <h5 class="text-white mb-1" id="preview-auction-name">اسم المزاد</h5>
                                        <small class="text-white-50" id="preview-auction-category">التصنيف</small>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1" id="preview-auction-city">المدينة</p>
                                        <p class="text-muted mb-0" id="preview-auction-date">تاريخ الانطلاق</p>
                                    </div>
                                </article>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <button type="submit" name="status" value="pending_approval" class="btn btn-primary">
                                    <i class="ti ti-send me-1"></i> إرسال للاعتماد
                                </button>
                            </div>
                        </div>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
