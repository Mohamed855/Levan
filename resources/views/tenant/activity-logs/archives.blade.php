@extends('tenant.layouts.layoutMaster')

@section('title', 'أرشيف سجل النشاطات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'])
@endsection

@section('content')
    {{-- Statistics Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي الملفات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $archives->count() }}</h4>
                            </div>
                            <small class="mb-0">ملف مصدر</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-files ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي الحجم</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">
                                    @php
                                        $totalSize = 0;
                                        foreach($archives as $a) {
                                            $size = floatval(str_replace([' MB', ' KB', ' GB', ' B'], '', $a['size']));
                                            if(str_contains($a['size'], 'KB')) $size = $size / 1024;
                                            if(str_contains($a['size'], 'GB')) $size = $size * 1024;
                                            $totalSize += $size;
                                        }
                                        echo round($totalSize, 2);
                                    @endphp
                                    MB
                                </h4>
                            </div>
                            <small class="mb-0">حجم تقريبي</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-database ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">آخر تصدير</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $archives->isNotEmpty() ? \Carbon\Carbon::parse($archives->first()['date'])->format('d/m') : '--' }}</h4>
                            </div>
                            <small class="mb-0">{{ $archives->isNotEmpty() ? \Carbon\Carbon::parse($archives->first()['date'])->diffForHumans() : 'لا يوجد' }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-calendar-event ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Archives Content --}}
    @if($archives->isEmpty())
        {{-- Empty State --}}
        <div class="card">
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="ti ti-folder-off ti-xl text-muted mb-4 d-block"></i>
                    <h5 class="mb-2">لا توجد ملفات مصدرة</h5>
                    <p class="text-muted mb-4">
                        سيتم تصدير سجل النشاطات تلقائياً في بداية كل شهر<br>
                        وسيظهر الملف المصدر هنا
                    </p>
                    <a href="{{ tenant_route_name('activity-logs.index') }}" class="btn btn-primary">
                        <i class="ti ti-arrow-right me-1"></i>
                        العودة للسجل
                    </a>
                </div>
            </div>
        </div>
    @else
        {{-- Table View --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="ti ti-list me-2"></i>
                    قائمة الملفات المصدرة
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered text-center">
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
                            @foreach($archives as $index => $archive)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="text-start">
                                        <div class="d-flex align-items-center">
                                            <i class="ti ti-file-spreadsheet text-success me-2 ti-lg"></i>
                                            <span class="fw-medium">{{ $archive['name'] }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-primary">{{ $archive['month'] }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $archive['size'] }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            <div>{{ \Carbon\Carbon::parse($archive['date'])->format('Y-m-d') }}</div>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($archive['date'])->format('H:i') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ tenant_route_name('activity-logs.download', basename($archive['path'])) }}" 
                                           class="btn btn-icon btn-label-primary"
                                           title="تحميل الملف">
                                            <i class="ti ti-download"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Info Alert --}}
        <div class="alert alert-info mt-4 d-flex align-items-center" role="alert">
            <i class="ti ti-info-circle me-2 ti-lg"></i>
            <div>
                <strong>معلومة:</strong>
                يتم تصدير سجل النشاطات تلقائياً في بداية كل شهر وحفظه لمدة 6 أشهر، بعدها يتم حذف الملفات القديمة تلقائياً.
            </div>
        </div>
    @endif
@endsection