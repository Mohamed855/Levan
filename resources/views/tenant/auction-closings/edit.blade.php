@extends('tenant.layouts.layoutMaster')

@section('title', 'إقفال المزاد: ' . $auction->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenant/auctions/cycle-forms.js'])
@endsection

@section('content')
    @php
        $fallbackImage = asset('static/brand/logos/logo.svg');
        $totalProps = $auction->properties->count();
        $soldCount = $auction->properties->where('pivot.result_status', 'sold')->count();
        $unsoldCount = $auction->properties->where('pivot.result_status', 'unsold')->count();
        $completedCount = $soldCount + $unsoldCount;
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <div class="card">
                <div class="card-body">
                    <form id="report-cycle-form" method="POST" enctype="multipart/form-data" autocomplete="off"
                        data-form-validate action="{{ tenant_route_name('auction-closings.update', $auction) }}">
                        @csrf
                        @method('PUT')

                        {{-- البيانات العلوية للإقفال --}}
                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">رقم الموافقة على إقامة المزاد</label>
                                <div class="form-control bg-light fw-medium">
                                    {{ $auction->establishmentRequest?->approval_number ?? '-' }}
                                </div>
                            </div>
                            <x-form.controls.input name="closing_date" label="تاريخ الإقفال" type="date"
                                :value="old(
                                    'closing_date',
                                    $auction->closing?->closing_date?->format('Y-m-d') ?? now()->toDateString(),
                                )" required col="col-md-4" />
                            <x-form.controls.input name="board_removal_date" label="تاريخ فك اللوحات" type="date"
                                :value="old(
                                    'board_removal_date',
                                    $auction->marketing_data['board_removal_date'] ?? null,
                                )" required col="col-md-4" />
                            <x-form.controls.textarea name="results_narrative" label="تفاصيل نتائج المزاد"
                                :value="old('results_narrative', $auction->marketing_data['results_narrative'] ?? null)"
                                col="col-12" />
                        </div>

                        {{-- التنبيه التحذيري --}}
                        <div class="alert alert-warning d-flex align-items-center mb-4">
                            <i class="ti ti-alert-triangle ti-md me-2"></i>
                            <div>
                                يمكنك تحديث وحفظ نتائج عقار واحد أو أكثر على مراحل، ولن تتحول حالة المزاد إلى <strong>"تم الإقفال"</strong> إلا بعد تحديد النتيجة النهائية لكافة العقارات (مباعة أو غير مباعة).
                            </div>
                        </div>

                        {{-- جدول العقارات أسفل التنبيه --}}
                        <div class="card assignment-properties-table-card border shadow-none mb-4">
                            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 bg-light-subtle">
                                <div>
                                    <h5 class="mb-0 fw-bold">نتائج وتفاصيل عقارات المزاد</h5>
                                    <small class="text-muted">انقر على زر التعديل لتسجيل تفاصيل الترسية أو أسباب عدم البيع لكل عقار.</small>
                                </div>
                                <div class="d-flex flex-wrap gap-2 text-start">
                                    <span class="badge bg-primary p-2">المحددة: <span id="completed-closing-props-count">{{ $completedCount }}</span> / {{ $totalProps }}</span>
                                    <span class="badge bg-success p-2">تمت الترسية: <span id="sold-closing-props-count">{{ $soldCount }}</span></span>
                                    <span class="badge bg-danger p-2">لم تتم الترسية: <span id="unsold-closing-props-count">{{ $unsoldCount }}</span></span>
                                </div>
                            </div>

                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="closing-properties-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 3.5rem;" class="text-center" title="تسجيل واكتمال نتيجة العقار">
                                                    <i class="ti ti-checkbox fs-5" aria-hidden="true"></i>
                                                </th>
                                                <th style="width: 110px;">رقم الأصل</th>
                                                <th style="width: 60px;" class="text-center">الصورة</th>
                                                <th>اسم العقار</th>
                                                <th>نوع العقار</th>
                                                <th>المدينة / الحي</th>
                                                <th>المساحة</th>
                                                <th>سعر الافتتاح / القيمة</th>
                                                <th class="text-center" style="width: 170px;">نتيجة العقار</th>
                                                <th class="text-center" style="width: 100px;">الإجراء</th>
                                            </tr>
                                        </thead>
                                        <tbody id="closing-properties-container">
                                            @forelse($auction->properties as $property)
                                                @php
                                                    $image = $property->main_image_url ?? $property->image_url ?? $fallbackImage;
                                                    $statusVal = old('results.' . $property->id . '.status', $property->pivot->result_status);
                                                    $isDecided = in_array($statusVal, ['sold', 'unsold'], true);
                                                    $propUrl = tenant_route_name('auction-closings.property-data', [$auction, $property]);
                                                @endphp
                                                <tr class="closing-property-row {{ $statusVal === 'sold' ? 'table-success-subtle' : ($statusVal === 'unsold' ? 'table-danger-subtle' : '') }}"
                                                    data-property-row="{{ $property->id }}">
                                                    <td class="text-center">
                                                        <input type="checkbox"
                                                            class="form-check-input closing-property-check"
                                                            data-closing-check="{{ $property->id }}"
                                                            {{ $isDecided ? 'checked' : '' }}
                                                            disabled
                                                            tabindex="-1"
                                                            style="pointer-events: none; cursor: default;"
                                                            aria-label="اكتمال نتيجة {{ $property->name }}">
                                                    </td>
                                                    <td>
                                                        <code class="copyable-code">{{ $property->property_number }}</code>
                                                    </td>
                                                    <td class="text-center">
                                                        <img src="{{ $image }}" class="rounded" style="width:36px;height:36px;object-fit:cover" alt=""
                                                            onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold text-body">{{ $property->name }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-label-info">{{ $property->type_label ?? '-' }}</span>
                                                    </td>
                                                    <td>
                                                        {{ $property->city ?? '-' }}{{ $property->district ? ' - ' . $property->district : '' }}
                                                    </td>
                                                    <td>{{ number_format((float) ($property->area ?? 0)) }} م²</td>
                                                    <td data-opening-cell="{{ $property->id }}">
                                                        @if ($property->pivot->opening_price)
                                                            {{ number_format((float) $property->pivot->opening_price) }} ر.س
                                                        @elseif ($property->estimated_value)
                                                            {{ number_format((float) $property->estimated_value) }} ر.س
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($statusVal === 'sold')
                                                            <span class="badge bg-label-success closing-status-badge" data-closing-badge="{{ $property->id }}">مباع / تمت الترسية</span>
                                                        @elseif ($statusVal === 'unsold')
                                                            <span class="badge bg-label-danger closing-status-badge" data-closing-badge="{{ $property->id }}">لم تتم الترسية</span>
                                                        @else
                                                            <span class="badge bg-label-secondary closing-status-badge" data-closing-badge="{{ $property->id }}">غير محددة</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-icon btn-label-primary edit-closing-prop-btn"
                                                            data-property-id="{{ $property->id }}"
                                                            data-property-url="{{ $propUrl }}"
                                                            title="تعديل">
                                                            <i class="ti ti-edit" aria-hidden="true"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="text-center py-4 text-muted">
                                                        لا توجد عقارات مرتبطة بالمزاد لإقفالها.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- حاوية الحقول المخفية للنموذج لحفظ نتائج العقارات --}}
                        <div id="closing-properties-data-container" class="d-none">
                            @foreach ($auction->properties as $property)
                                @php
                                    $prefix = 'results[' . $property->id . ']';
                                    $statusVal = old('results.' . $property->id . '.status', $property->pivot->result_status);
                                    if ($statusVal === 'pending') {
                                        $statusVal = '';
                                    }
                                @endphp
                                <div class="property-hidden-data" data-prop-hidden="{{ $property->id }}">
                                    <input type="hidden" name="{{ $prefix }}[status]" value="{{ $statusVal }}" data-field="status">
                                    <input type="hidden" name="{{ $prefix }}[opening_price]" value="{{ old('results.' . $property->id . '.opening_price', $property->pivot->opening_price) }}" data-field="opening_price">
                                    <input type="hidden" name="{{ $prefix }}[participants_count]" value="{{ old('results.' . $property->id . '.participants_count', $property->pivot->participants_count) }}" data-field="participants_count">
                                    <input type="hidden" name="{{ $prefix }}[bids_count]" value="{{ old('results.' . $property->id . '.bids_count', $property->pivot->bids_count) }}" data-field="bids_count">
                                    <input type="hidden" name="{{ $prefix }}[buyer_name]" value="{{ old('results.' . $property->id . '.buyer_name', $property->pivot->buyer_name) }}" data-field="buyer_name">
                                    <input type="hidden" name="{{ $prefix }}[sale_price]" value="{{ old('results.' . $property->id . '.sale_price', $property->pivot->sale_price) }}" data-field="sale_price">
                                    <input type="hidden" name="{{ $prefix }}[unsold_reason]" value="{{ old('results.' . $property->id . '.unsold_reason', $property->pivot->unsold_reason) }}" data-field="unsold_reason">
                                    <input type="hidden" name="{{ $prefix }}[result_notes]" value="{{ old('results.' . $property->id . '.result_notes', $property->pivot->result_notes) }}" data-field="result_notes">
                                    <div class="property-file-inputs" data-prop-files="{{ $property->id }}"></div>
                                </div>
                            @endforeach
                        </div>

                        {{-- أزرار الحفظ والإجراءات الأصلية --}}
                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="tenant_route_name('auction-closings.index')" text="العودة" />
                            <x-form.wizard.save-button :disabled="$auction->properties->isEmpty()" />
                        </x-form.wizard.actions>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- مودال واحد فقط لتعديل نتيجة العقار عبر Ajax --}}
    <x-bootstrap.modal id="closingPropertyModal" size="lg" scrollable="true">
        <x-slot:header>
            <div class="d-flex align-items-center gap-2">
                <i class="ti ti-gavel fs-4 text-primary"></i>
                <h5 class="modal-title fw-bold mb-0" id="closingPropertyModalTitle">
                    نتيجة إقفال العقار
                </h5>
                <span class="badge bg-label-primary" id="closingPropertyModalNumber"></span>
            </div>
        </x-slot:header>

        <div id="closing-modal-spinner" class="text-center py-5 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جار التحميل...</span>
            </div>
        </div>

        <div id="closing-modal-content">
            <input type="hidden" id="modal_property_id">
            <div class="row g-4 mb-3">
                <div class="col-12">
                    <h6 class="text-muted fw-semibold mb-3">المقاييس المالية والمزايدة</h6>
                    <div class="row g-4">
                        <x-form.controls.input name="modal_opening_price" id="modal_opening_price" label="سعر الافتتاح" type="number"
                            min="0" step="0.01" required col="col-md-4" />
                        <x-form.controls.input name="modal_participants_count" id="modal_participants_count" label="عدد المشاركين" type="number"
                            min="0" step="1" required col="col-md-4" />
                        <x-form.controls.input name="modal_bids_count" id="modal_bids_count" label="عدد المزايدات" type="number"
                            min="0" step="1" required col="col-md-4" />
                    </div>
                </div>

                <div class="col-12 border-top pt-3">
                    <x-form.controls.select name="modal_result_status" id="modal_result_status" label="نتيجة العقار"
                        :options="['' => 'اختر النتيجة', 'sold' => 'مباع / تمت الترسية', 'unsold' => 'لم تتم الترسية']"
                        required col="col-12" :select2="false" />
                </div>

                {{-- حقول الترسية (المباع) --}}
                <div class="col-12 d-none" id="modal_sold_section">
                    <div class="row g-4">
                        <x-form.controls.input name="modal_buyer_name" id="modal_buyer_name" label="اسم من تمت الترسية عليه"
                            required col="col-md-6" />
                        <x-form.controls.input name="modal_sale_price" id="modal_sale_price" label="قيمة الترسية" type="number"
                            min="0.01" step="0.01" required col="col-md-6" />
                    </div>
                </div>

                {{-- حقول عدم البيع --}}
                <div class="col-12 d-none" id="modal_unsold_section">
                    <x-form.controls.textarea name="modal_unsold_reason" id="modal_unsold_reason" label="سبب عدم البيع"
                        required col="col-12" />
                </div>

                {{-- ملاحظات النتيجة --}}
                <div class="col-12 border-top pt-3">
                    <x-form.controls.textarea name="modal_result_notes" id="modal_result_notes" label="ملاحظات النتيجة"
                        col="col-12" />
                </div>

                {{-- صور فك اللوحات --}}
                <div class="col-12 border-top pt-3">
                    <x-form.controls.file name="modal_board_images_after[]" id="modal_board_images_after" label="صور فك اللوحات" multiple
                        accept=".png,.jpg,.jpeg,.webp" col="col-12" />

                    <div id="modal_existing_attachments_container" class="mt-3 d-none">
                        <h6 class="text-muted small fw-bold mb-2">المرفقات السابقة لفك اللوحات</h6>
                        <div class="row g-3" id="modal_existing_attachments_list"></div>
                    </div>
                </div>
            </div>
        </div>

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="ti ti-x me-1"></i>إلغاء
            </button>
            <button type="button" class="btn btn-primary" id="btn-save-closing-modal">
                <i class="ti ti-check me-1"></i>حفظ
            </button>
        </x-slot:footer>
    </x-bootstrap.modal>
@endsection
