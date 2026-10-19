@extends('tenants.layouts.layoutMaster')

@section('title', 'تعديل المزاد - ' . $auction->name)

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
            const propertiesUrl = "{{ tenant_route_name('assignments.properties', ':assignment') }}";

            $('#assignment_id').on('change', function() {
                const assignmentId = $(this).val();
                const $properties = $('#property_ids');

                if (!assignmentId) {
                    $properties.empty().trigger('change');
                    return;
                }

                $.get(propertiesUrl.replace(':assignment', assignmentId))
                    .done(function(response) {
                        const options = (response.properties || []).map(function(property) {
                            const label = `${property.property_number} — ${property.name}`;
                            return new Option(label, property.id, false, false);
                        });

                        $properties.empty().append(options).trigger('change');
                    })
                    .fail(function() {
                        $properties.empty().trigger('change');
                        window.toastr?.error?.('تعذر تحميل عقارات الإسناد المختار.');
                    });
            });
        });
    </script>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti ti-edit me-2 text-primary"></i>تعديل المزاد: {{ $auction->name }}</h4>
            <span class="badge {{ $auction->status_badge_class }}">{{ $auction->status_label }}</span>
        </div>
        <a href="{{ tenant_route_name('auctions.show', $auction->id) }}" class="btn btn-secondary">
            <i class="ti ti-arrow-right me-1"></i> العودة لتفاصيل المزاد
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ tenant_route_name('auctions.update', $auction->id) }}" method="POST"
                enctype="multipart/form-data" novalidate data-form-validate
                data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                @csrf
                @method('PUT')

                <div class="row g-4">
                    <x-form.controls.input name="name" label="اسم المزاد" :value="$auction->name" required col="col-md-6" />

                    <x-form.controls.select name="assignment_id" label="الإسناد المرتبط" required col="col-md-6"
                        :value="$auction->assignment_id" :options="$assignments
                            ->map(
                                fn($a) => ['value' => $a->id, 'label' => $a->name . ' (' . $a->assignment_number . ')'],
                            )
                            ->toArray()" />

                    <x-form.controls.input name="start_date" type="date" label="تاريخ البداية" :value="$auction->start_date?->format('Y-m-d')" required
                        col="col-md-3" />
                    <x-form.controls.input name="start_time" type="time" label="وقت البداية" :value="$auction->start_time"
                        col="col-md-3" />

                    <x-form.controls.input name="end_date" type="date" label="تاريخ النهاية" :value="$auction->end_date?->format('Y-m-d')" required
                        col="col-md-3" />
                    <x-form.controls.input name="end_time" type="time" label="وقت النهاية" :value="$auction->end_time"
                        col="col-md-3" />

                    <x-form.controls.select name="type" label="نوع المزاد" :value="$auction->type" required col="col-md-4"
                        :enum="\App\Enums\Tenants\Auctions\AuctionType::class" />

                    <x-form.controls.select name="city" label="المدينة الرئيسية" :value="$auction->city" required
                        col="col-md-4" :options="$cityOptions" />

                    <x-form.controls.select name="status" label="حالة المزاد" :value="$auction->status" col="col-md-4"
                        :enum="\App\Enums\Tenants\Auctions\AuctionStatus::class" />

                    <x-form.controls.input name="expected_properties_count" type="number" min="1"
                        label="عدد العقارات المتوقع" :value="$auction->expected_properties_count" col="col-md-4" />

                    <x-form.controls.select name="property_ids" id="property_ids" label="العقارات المرتبطة" multiple
                        col="col-12" :value="$auction->properties->pluck('id')->all()"
                        help="يتغير الاختيار تلقائياً عند تغيير الإسناد، ولا يقبل النظام إلا عقارات الإسناد المختار."
                        :options="$properties
                            ->map(
                                fn($property) => [
                                    'value' => $property->id,
                                    'label' => $property->property_number . ' — ' . $property->name,
                                ],
                            )
                            ->all()" />

                    <x-form.controls.input name="supervising_entity" label="الجهة المشرفة" :value="$auction->supervising_entity"
                        col="col-md-6" />
                    <x-form.controls.input name="platform_name" label="منصة المزاد" :value="$auction->platform_name" col="col-md-6" />
                    <x-form.controls.input name="platform_url" type="url" label="رابط المزاد" :value="$auction->platform_url"
                        col="col-12" />

                    <x-form.controls.textarea name="description" label="وصف المزاد" :value="$auction->description" col="col-12"
                        rows="3" />
                    <x-form.controls.textarea name="notes" label="الملاحظات" :value="$auction->notes" col="col-12"
                        rows="2" />

                    <div class="col-12">
                        <h6 class="fw-bold mb-1"><i class="ti ti-palette me-1"></i>القوالب التسويقية المعتمدة:</h6>
                    </div>

                    <x-form.controls.select name="brochure_template_id" label="قالب البروشور" col="col-md-4"
                        :value="$auction->brochure_template_id" :options="$brochureTemplates" placeholder="اختر قالب البروشور..." />

                    <x-form.controls.select name="billboard_template_id" label="قالب اللوحة الإعلانية" col="col-md-4"
                        :value="$auction->billboard_template_id" :options="$billboardTemplates" placeholder="اختر قالب اللوحة..." />

                    <x-form.controls.select name="social_template_id" label="قالب منصات التواصل" col="col-md-4"
                        :value="$auction->social_template_id" :options="$socialTemplates" placeholder="اختر قالب منصات التواصل..." />

                    <div class="col-12" data-form-field data-field-name="attachments[]">
                        <label class="form-label" for="attachments">إضافة مرفقات</label>
                        <input id="attachments" type="file" name="attachments[]" multiple data-form-control
                            class="form-control" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx">
                        <p class="invalid-feedback hidden" data-form-error aria-live="polite" hidden></p>
                        <div class="form-text">PDF، PNG، JPG، DOC أو XLS؛ الحد الأقصى لكل ملف 10MB.</div>
                    </div>

                    @if ($auction->attachments->isNotEmpty())
                        <div class="col-12">
                            <div class="border rounded p-3 bg-secondary">
                                <div class="fw-medium mb-2">المرفقات الحالية</div>
                                <ul class="mb-0 ps-3">
                                    @foreach ($auction->attachments as $attachment)
                                        <li>{{ $attachment->file_name }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="col-12 text-end mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i> حفظ التعديلات
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
