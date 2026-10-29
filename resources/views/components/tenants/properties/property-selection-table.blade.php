@props([
    'title' => null,
    'subtitle' => null,
    'showStats' => false,
    'initialCount' => 0,
    'cardClass' => 'assignment-properties-table-card mb-4',
    'tableId' => 'properties-selection-table',
])

<div class="card {{ $cardClass }}">
    @if ($title || $showStats)
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <div>
                @if ($title)
                    <h5 class="mb-0 fw-bold">{{ $title }}</h5>
                @endif
                @if ($subtitle)
                    <small class="text-muted">{{ $subtitle }}</small>
                @endif
            </div>
            @if ($showStats)
                <div class="d-flex flex-wrap gap-2 text-start">
                    <span class="badge bg-primary p-2">المحددة: <span id="selected-props-count">{{ $initialCount }}</span></span>
                    <span class="badge bg-info p-2">إجمالي المساحة: <span id="selected-props-area">0 م²</span></span>
                    <span class="badge bg-success p-2">إجمالي القيمة: <span id="selected-props-val">0 ر.س</span></span>
                </div>
            @endif
        </div>
    @endif

    <div class="card-body">
        @if (isset($filters))
            {{ $filters }}
        @endif

        @if ($slot->isNotEmpty())
            {{ $slot }}
        @else
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered align-middle mb-0" id="{{ $tableId }}">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 2.5rem;" class="text-center">
                                <input type="checkbox" id="select-all-properties" class="form-check-input" aria-label="تحديد الكل">
                            </th>
                            <th style="width: 100px;">رقم العقار</th>
                            <th style="width: 60px;" class="text-center">الصورة</th>
                            <th>اسم العقار</th>
                            <th>نوع العقار</th>
                            <th>المدينة / الحي</th>
                            <th>المساحة</th>
                            <th>القيمة التقديرية</th>
                            <th class="text-center">الحالة</th>
                        </tr>
                    </thead>
                    <tbody id="properties-container">
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                يرجى اختيار الإسناد في الخطوة الأولى لعرض عقاراته التابعة
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
