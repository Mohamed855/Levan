@extends('tenant.layouts.layoutMaster')

@section('title', 'أرشيف سجل النشاطات')

@section('content')
    {{-- Statistics Cards --}}
    <div class="row g-4 mb-4">
        <x-ui.stat-card col="col-sm-6 col-xl-4" title="إجمالي الملفات" :value="$totalCount" icon="ti-files" color="primary">
            <small class="text-muted d-block mt-1">ملف مصدر</small>
        </x-ui.stat-card>

        <x-ui.stat-card col="col-sm-6 col-xl-4" title="إجمالي الحجم" :value="$totalSizeMb" icon="ti-database" color="success">
            <small class="text-muted d-block mt-1">حجم تقريبي</small>
        </x-ui.stat-card>

        <x-ui.stat-card col="col-sm-6 col-xl-4" title="آخر تصدير" :value="$lastExportDate" icon="ti-calendar-event"
            color="info">
            <small class="text-muted d-block mt-1">{{ $lastExportDiff }}</small>
        </x-ui.stat-card>
    </div>

    {{-- Archives Content --}}
    @if ($archives->isEmpty())
        <div class="card">
            <div class="card-body">
                <x-ui.empty-state icon="ti ti-folder-off" title="لا توجد ملفات مصدرة"
                    description="سيتم تصدير سجل النشاطات تلقائياً في بداية كل شهر وسيظهر الملف المصدر هنا"
                    actionText="العودة للسجل" :actionUrl="tenant_route_name('activity-logs.index')" actionIcon="ti ti-arrow-right" />
            </div>
        </div>
    @else
        <div class="card" id="card-activity-log-archives">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="ti ti-list me-2"></i>
                    قائمة الملفات المصدرة
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered text-center align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="60">#</th>
                                <th>اسم الملف</th>
                                <th>الشهر</th>
                                <th>الحجم</th>
                                <th>تاريخ التصدير</th>
                                <th width="120">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($archives as $index => $archive)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="text-start">
                                        <div class="d-flex align-items-center">
                                            <i class="ti ti-file-spreadsheet text-success me-2 ti-lg"></i>
                                            <span class="fw-medium">{{ $archive['name'] }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $archive['month'] }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $archive['size'] }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            <div>{{ $archive['formatted_date'] }}</div>
                                            <small class="text-muted">{{ $archive['formatted_time'] }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <x-actions.row>
                                            <x-actions.show :url="tenant_route_name('activity-logs.export-download', $archive['name'])"
                                                title="تحميل الملف" icon="ti ti-download"
                                                class="btn-icon btn-primary" />
                                        </x-actions.row>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-4 d-flex align-items-center" role="alert">
            <i class="ti ti-info-circle me-2 ti-lg"></i>
            <div>
                <strong>معلومة:</strong>
                يتم تصدير سجل النشاطات تلقائياً في بداية كل شهر وحفظه لمدة 6 أشهر، بعدها يتم حذف الملفات القديمة تلقائياً.
            </div>
        </div>
    @endif
@endsection
