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
                        :options="[
                            ['value' => 'electronic', 'label' => 'إلكتروني'],
                            ['value' => 'in_person', 'label' => 'حضوري'],
                            ['value' => 'hybrid', 'label' => 'هجين'],
                        ]" />

                    <x-form.controls.select name="city" label="المدينة الرئيسية" :value="$auction->city" required
                        col="col-md-4" :options="[
                            ['value' => 'الرياض', 'label' => 'الرياض'],
                            ['value' => 'جدة', 'label' => 'جدة'],
                            ['value' => 'مكة المكرمة', 'label' => 'مكة المكرمة'],
                            ['value' => 'المدينة المنورة', 'label' => 'المدينة المنورة'],
                            ['value' => 'الدمام', 'label' => 'الدمام'],
                        ]" />

                    <x-form.controls.select name="status" label="حالة المزاد" :value="$auction->status" col="col-md-4"
                        :options="[
                            ['value' => 'draft', 'label' => 'مسودة'],
                            ['value' => 'in_preparation', 'label' => 'قيد الإعداد'],
                            ['value' => 'pending_approval', 'label' => 'بانتظار الاعتماد'],
                            ['value' => 'active', 'label' => 'نشط'],
                            ['value' => 'completed', 'label' => 'منتهي'],
                            ['value' => 'suspended', 'label' => 'معلق'],
                        ]" />

                    <x-form.controls.input name="supervising_entity" label="الجهة المشرفة" :value="$auction->supervising_entity"
                        col="col-md-6" />
                    <x-form.controls.input name="platform_name" label="منصة المزاد" :value="$auction->platform_name" col="col-md-6" />
                    <x-form.controls.input name="platform_url" type="url" label="رابط المزاد" :value="$auction->platform_url"
                        col="col-12" />

                    <x-form.controls.textarea name="description" label="وصف المزاد" :value="$auction->description" col="col-12"
                        rows="3" />
                    <x-form.controls.textarea name="notes" label="الملاحظات" :value="$auction->notes" col="col-12"
                        rows="2" />

                    <x-form.controls.image-upload name="cover_image" label="صورة الغلاف" :value="$auction->cover_image ? tasset($auction->cover_image) : null"
                        hint="JPG، PNG أو WebP. الحد الأقصى 4MB" col="col-12" />

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
