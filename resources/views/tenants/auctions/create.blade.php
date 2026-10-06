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
    @vite(['resources/assets/js/shared/form-validation.js'])
    <script>
        $(document).ready(function() {
            // Initialize Select2 controls
            if ($.fn.select2) {
                $('.select2').not('.select2-hidden-accessible').each(function() {
                    $(this).select2({
                        placeholder: $(this).data('placeholder') || 'اختر',
                        allowClear: !$(this).prop('required'),
                        width: '100%',
                        language: 'ar',
                        dir: 'rtl'
                    });
                });
            }

            // Live Preview in Step 1
            $('#name').on('input', function() {
                $('#preview-auction-name').text($(this).val() || 'اسم المزاد الافتراضي');
                $('#review-name').text($(this).val() || '-');
            });
            $('#city').on('change', function() {
                $('#preview-auction-city').text($(this).val() || 'المدينة');
                $('#review-city').text($(this).val() || '-');
            });
            $('#category').on('change', function() {
                $('#preview-auction-category').text($('#category option:selected').text() || 'التصنيف');
                $('#review-category').text($('#category option:selected').text() || '-');
            });
            $('#start_date').on('change', function() {
                $('#preview-auction-date').text($(this).val() || 'تاريخ الانطلاق');
                $('#review-dates').text($(this).val() + ' إلى ' + ($('#end_date').val() || ''));
            });
            $('#end_date').on('change', function() {
                $('#review-dates').text(($('#start_date').val() || '') + ' إلى ' + $(this).val());
            });
            $('#type').on('change', function() {
                $('#review-type').text($('#type option:selected').text());
            });

            // Dynamic Assignment Handling in Step 2 -> Loading Properties in Step 3
            $('#assignment_id').on('change', function() {
                const assignmentId = $(this).val();
                if (!assignmentId) {
                    $('#selected-assignment-card').addClass('d-none');
                    $('#properties-container').html(
                        '<tr><td colspan="6" class="text-center py-4 text-muted">يرجى اختيار الإسناد في الخطوة السابقة لعرض العقارات التابعة له</td></tr>'
                        );
                    return;
                }

                const url = "{{ tenant_route_name('assignments.properties', ':id') }}".replace(':id',
                    assignmentId);

                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(res) {
                        if (res.success) {
                            // Populate Assignment Summary Card
                            $('#asg-summary-num').text(res.assignment.assignment_number);
                            $('#asg-summary-entity').text(res.assignment.issuing_entity);
                            $('#asg-summary-status').text(res.assignment.status_label);
                            $('#selected-assignment-card').removeClass('d-none');

                            // Set supervising entity default if empty
                            if (!$('#supervising_entity').val()) {
                                $('#supervising_entity').val(res.assignment.issuing_entity)
                                    .trigger('input');
                            }

                            // Review Card in Step 5
                            $('#review-assignment-name').text(res.assignment.name + ' (' + res
                                .assignment.assignment_number + ')');
                            $('#review-supervising').text(res.assignment.issuing_entity);

                            // Populate Properties Table in Step 3
                            let html = '';
                            if (res.properties && res.properties.length > 0) {
                                res.properties.forEach(function(prop) {
                                    const img = prop.main_image ? '/tenant-assets/' +
                                        prop.main_image :
                                        '/assets/img/illustrations/page-pricing-standard.png';
                                    html += `
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="property_ids[]" value="${prop.id}" class="form-check-input property-select-check"
                                                    data-area="${prop.area || 0}" data-val="${prop.estimated_value || 0}">
                                            </td>
                                            <td><code>${prop.property_number}</code></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="${img}" class="rounded me-2" style="width:28px;height:28px;object-fit:cover" />
                                                    <span class="fw-medium">${prop.name}</span>
                                                </div>
                                            </td>
                                            <td>${prop.city} ${prop.district ? ' - ' + prop.district : ''}</td>
                                            <td>${Number(prop.area).toLocaleString()} م²</td>
                                            <td>${Number(prop.estimated_value).toLocaleString()} ر.س</td>
                                        </tr>
                                    `;
                                });
                            } else {
                                html =
                                    '<tr><td colspan="6" class="text-center py-4 text-muted">لا توجد عقارات مسجلة لهذا الإسناد حالياً.</td></tr>';
                            }
                            $('#properties-container').html(html);
                            calculateSelectedProperties();
                        }
                    }
                });
            });

            // Select all properties checkbox
            $(document).on('change', '#select-all-properties', function() {
                $('.property-select-check').prop('checked', $(this).prop('checked'));
                calculateSelectedProperties();
            });

            $(document).on('change', '.property-select-check', function() {
                calculateSelectedProperties();
            });

            function calculateSelectedProperties() {
                let count = 0;
                let totalArea = 0;
                let totalVal = 0;

                $('.property-select-check:checked').each(function() {
                    count++;
                    totalArea += parseFloat($(this).data('area')) || 0;
                    totalVal += parseFloat($(this).data('val')) || 0;
                });

                $('#selected-props-count').text(count);
                $('#selected-props-area').text(totalArea.toLocaleString() + ' م²');
                $('#selected-props-val').text(totalVal.toLocaleString() + ' ر.س');
                $('#review-selected-props-count').text(count + ' عقارات');
            }

            // Jump to step button in Step 5
            $('.btn-jump-step').on('click', function() {
                const targetStep = $(this).data('step');
                const stepperEl = document.querySelector('#wizard-validation');
                if (window.stepper) {
                    window.stepper.to(targetStep);
                }
            });
        });
    </script>
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'step-basic-info', 'title' => 'البيانات الأساسية', 'subtitle' => 'المعلومات وتاريخ المزاد'],
            ['id' => 'step-assignment', 'title' => 'الإسناد والمنصة', 'subtitle' => 'الجهة المشرفة والمنصة'],
            ['id' => 'step-properties', 'title' => 'العقارات المرتبطة', 'subtitle' => 'تحديد عقارات المزاد'],
            ['id' => 'step-settings', 'title' => 'الإعدادات والملفات', 'subtitle' => 'القوالب والمرفقات'],
            ['id' => 'step-review', 'title' => 'المراجعة والإرسال', 'subtitle' => 'مراجعة كافة التفاصيل'],
        ];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti ti-gavel me-2 text-primary"></i>إنشاء مزاد جديد</h4>
            <p class="text-muted mb-0">حدد تفاصيل المزاد، الإسناد، العقارات المشمولة، والقوالب المعتمدة للمخرجات.</p>
        </div>
        <a href="{{ tenant_route_name('auctions.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-right me-1"></i> العودة للمزادات
        </a>
    </div>

    <div class="row">
        <div class="col-12 mb-6">
            <x-form.wizard.stepper id="wizard-validation" class="mt-2" :steps="$steps">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="wizard-validation-form" action="{{ tenant_route_name('auctions.store') }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
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
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="row g-4">
                                    <x-form.controls.input name="name" label="اسم المزاد"
                                        placeholder="مثال: مزاد جوار الحرم" required col="col-12" />

                                    <x-form.controls.textarea name="description" label="وصف المزاد"
                                        placeholder="اكتب وصفاً توضيحياً للمزاد..." col="col-12" rows="3" />

                                    <x-form.controls.input name="start_date" type="date" label="تاريخ بداية المزاد"
                                        required col="col-md-6" />
                                    <x-form.controls.input name="start_time" type="time" label="وقت البداية"
                                        col="col-md-6" />

                                    <x-form.controls.input name="end_date" type="date" label="تاريخ نهاية المزاد"
                                        required col="col-md-6" />
                                    <x-form.controls.input name="end_time" type="time" label="وقت النهاية"
                                        col="col-md-6" />

                                    <x-form.controls.select name="type" label="نوع المزاد" required col="col-md-4"
                                        :options="[
                                            ['value' => 'electronic', 'label' => 'إلكتروني'],
                                            ['value' => 'in_person', 'label' => 'حضوري'],
                                            ['value' => 'hybrid', 'label' => 'هجين'],
                                        ]" />

                                    <x-form.controls.select name="category" label="تصنيف المزاد" required col="col-md-4"
                                        :options="[
                                            ['value' => 'residential', 'label' => 'سكني'],
                                            ['value' => 'commercial', 'label' => 'تجاري'],
                                            ['value' => 'investment', 'label' => 'استثماري'],
                                            ['value' => 'educational', 'label' => 'تعليمي'],
                                            ['value' => 'lands', 'label' => 'أراضي'],
                                            ['value' => 'mixed_use', 'label' => 'متعدد الاستخدام'],
                                        ]" />

                                    <x-form.controls.select name="city" label="المدينة الرئيسية" required col="col-md-4"
                                        :options="[
                                            ['value' => 'الرياض', 'label' => 'الرياض'],
                                            ['value' => 'جدة', 'label' => 'جدة'],
                                            ['value' => 'مكة المكرمة', 'label' => 'مكة المكرمة'],
                                            ['value' => 'المدينة المنورة', 'label' => 'المدينة المنورة'],
                                            ['value' => 'الدمام', 'label' => 'الدمام'],
                                        ]" />

                                    <x-form.controls.input name="expected_properties_count" type="number"
                                        label="عدد العقارات المتوقع" :value="1" min="1" col="col-md-6" />

                                    <x-form.controls.image-upload name="cover_image" label="صورة الغلاف للمزاد"
                                        hint="JPG، PNG أو WebP. الحد الأقصى 4MB" col="col-md-6" />
                                </div>
                            </div>

                            {{-- كارت المعاينة الجانبي المتطابق مع Wireframe 17 --}}
                            <div class="col-lg-4 mt-4 mt-lg-0">
                                <div class="card bg-light border">
                                    <div class="card-body text-center p-4">
                                        <h6 class="text-muted mb-3"><i class="ti ti-eye me-1"></i>معاينة مباشرة للغلاف</h6>
                                        <div class="p-3 bg-white rounded shadow-sm border">
                                            <div class="bg-primary text-white p-4 rounded mb-3"
                                                style="min-height: 120px; display: flex; flex-direction: column; justify-content: center;">
                                                <h5 class="text-white mb-1" id="preview-auction-name">اسم المزاد</h5>
                                                <small class="text-white-50" id="preview-auction-category">التصنيف</small>
                                            </div>
                                            <div class="d-flex justify-content-between text-muted small border-top pt-2">
                                                <span id="preview-auction-city">المدينة</span>
                                                <span id="preview-auction-date">تاريخ الانطلاق</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <x-form.wizard.next-button />
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
                            <x-form.wizard.next-button />
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 3: العقارات المرتبطة --}}
                    <x-form.wizard.step id="step-properties" :fields="['property_ids']">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">العقارات المرتبطة بالإسناد المختار</h5>
                                <small class="text-muted">حدد العقارات التي سيتم إدراجها رسمياً في هذا المزاد.</small>
                            </div>
                            <div class="d-flex gap-3 text-start">
                                <div class="badge bg-label-primary fs-6 p-2">المحددة: <span
                                        id="selected-props-count">0</span></div>
                                <div class="badge bg-label-info fs-6 p-2">إجمالي المساحة: <span id="selected-props-area">0
                                        م²</span></div>
                                <div class="badge bg-label-success fs-6 p-2">إجمالي القيمة: <span
                                        id="selected-props-val">0 ر.س</span></div>
                            </div>
                        </div>

                        <div class="table-responsive border rounded mb-4">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" id="select-all-properties" class="form-check-input">
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

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <x-form.wizard.next-button />
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
                                :options="[
                                    ['value' => 'template_classic', 'label' => 'البروشور الكلاسيكي الفاخر'],
                                    ['value' => 'template_modern', 'label' => 'البروشور العصري'],
                                    ['value' => 'template_infath', 'label' => 'نموذج إنفاذ الرسمي'],
                                ]" />

                            <x-form.controls.select name="billboard_template_id" label="قالب اللوحة الإعلانية"
                                col="col-md-4" :options="[
                                    ['value' => 'billboard_banner', 'label' => 'بانر الشوارع الرئيسي'],
                                    ['value' => 'billboard_vertical', 'label' => 'لوحة موقع العقار العمودية'],
                                ]" />

                            <x-form.controls.select name="social_template_id" label="قالب منصات التواصل" col="col-md-4"
                                :options="[
                                    ['value' => 'social_post', 'label' => 'منشور مربع (Instagram / X)'],
                                    ['value' => 'social_story', 'label' => 'ستوري عمودي (Snapchat / Story)'],
                                ]" />

                            <div class="col-12 mt-4 border-top pt-3">
                                <h6 class="fw-bold mb-3"><i class="ti ti-paperclip me-1"></i>مرفقات ووثائق المزاد:</h6>
                                <input type="file" name="attachments[]" class="form-control" multiple
                                    accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" />
                                <small class="text-muted d-block mt-1">الملفات المدعومة: PDF, PNG, JPG, DOC, XLS (الحد
                                    الأقصى لكل ملف: 10MB)</small>
                            </div>
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <x-form.wizard.next-button />
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 5: المراجعة والإرسال --}}
                    <x-form.wizard.step id="step-review" :fields="['status']">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                                        <h6 class="mb-0 fw-bold">1. البيانات الأساسية</h6>
                                        <button type="button" class="btn btn-sm btn-label-primary btn-jump-step"
                                            data-step="1">تعديل</button>
                                    </div>
                                    <div class="card-body py-2 small">
                                        <div><strong>اسم المزاد:</strong> <span id="review-name">-</span></div>
                                        <div><strong>النوع والتصنيف:</strong> <span id="review-type">-</span> / <span
                                                id="review-category">-</span></div>
                                        <div><strong>المدينة:</strong> <span id="review-city">-</span></div>
                                        <div><strong>الفترة:</strong> <span id="review-dates">-</span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                                        <h6 class="mb-0 fw-bold">2. الإسناد والمنصة</h6>
                                        <button type="button" class="btn btn-sm btn-label-primary btn-jump-step"
                                            data-step="2">تعديل</button>
                                    </div>
                                    <div class="card-body py-2 small">
                                        <div><strong>الإسناد:</strong> <span id="review-assignment-name">-</span></div>
                                        <div><strong>الجهة المشرفة:</strong> <span id="review-supervising">-</span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                                        <h6 class="mb-0 fw-bold">3. العقارات المشمولة</h6>
                                        <button type="button" class="btn btn-sm btn-label-primary btn-jump-step"
                                            data-step="3">تعديل</button>
                                    </div>
                                    <div class="card-body py-2 small">
                                        <div><strong>العقارات المحددة:</strong> <span id="review-selected-props-count">0
                                                عقارات</span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                                        <h6 class="mb-0 fw-bold">4. الإعدادات والملفات</h6>
                                        <button type="button" class="btn btn-sm btn-label-primary btn-jump-step"
                                            data-step="4">تعديل</button>
                                    </div>
                                    <div class="card-body py-2 small">
                                        <div><strong>القوالب المعتمدة:</strong> تم التحديد</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <button type="submit" name="status" value="draft" class="btn btn-secondary">
                                    <i class="ti ti-device-floppy me-1"></i> حفظ كمسودة
                                </button>
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
