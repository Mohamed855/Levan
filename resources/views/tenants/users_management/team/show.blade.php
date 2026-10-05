@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل المجموعة')

@section('content')
    <div class="row">
        <!-- معلومات المجموعة الأساسية -->
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-users-group me-2"></i>
                        {{ $team->name }}
                    </h5>
                    <span class="badge bg-label-{{ $team->status === 'active' ? 'success' : 'danger' }}">
                        {{ $team->status === 'active' ? 'نشط' : 'غير نشط' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- عدد الأعضاء -->
                        <div class="col-md-4 col-sm-6">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-sm">
                                        <span class="avatar-initial rounded bg-label-info">
                                            <i class="ti ti-users"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <small class="text-muted d-block mb-1">عدد الأعضاء</small>
                                    <span class="badge bg-label-info">{{ $team->members->count() }} عضو</span>
                                </div>
                            </div>
                        </div>

                        <!-- عدد القادة -->
                        <div class="col-md-4 col-sm-6">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-sm">
                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class="ti ti-crown"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <small class="text-muted d-block mb-1">عدد القادة</small>
                                    <span class="badge bg-label-primary">{{ $team->leaders->count() }} قائد</span>
                                </div>
                            </div>
                        </div>

                        <!-- تاريخ الإنشاء -->
                        <div class="col-md-4 col-sm-6">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-sm">
                                        <span class="avatar-initial rounded bg-secondary">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <small class="text-muted d-block mb-1">تاريخ الإنشاء</small>
                                    <span
                                        class="fw-semibold text-body">{{ $team->created_at->locale('ar')->translatedFormat('d F Y') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- الوصف -->
                        @if ($team->description)
                            <div class="col-12">
                                <hr class="my-3">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="avatar avatar-sm">
                                            <span class="avatar-initial rounded bg-label-warning">
                                                <i class="ti ti-file-text"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <small class="text-muted d-block mb-1">الوصف</small>
                                        <p class="mb-0">{{ $team->description }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- قادة المجموعة -->
        <div class="col-lg-6 col-md-12">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">
                        <i class="ti ti-crown me-2 text-primary"></i>
                        قادة المجموعة
                    </h6>
                    <span class="badge bg-label-primary rounded-pill">{{ $team->leaders->count() }}</span>
                </div>
                <div class="card-body">
                    @if ($team->leaders->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach ($team->leaders as $leader)
                                <div class="list-group-item d-flex align-items-center px-0 border-0 mb-3">
                                    <div class="avatar avatar-sm me-3">
                                        <img src="{{ tasset($leader->image) }}" alt="{{ $leader->name }}"
                                            class="rounded-circle" style="width: 38px; height: 38px; object-fit: cover;">
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">{{ $leader->name }}</h6>
                                        <small class="text-muted">{{ $leader->email }}</small>
                                    </div>
                                    <span class="badge bg-label-primary">
                                        <i class="ti ti-crown ti-xs me-1"></i>قائد
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="ti ti-crown ti-xl text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-0">لا يوجد قادة في هذه المجموعة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- أعضاء المجموعة -->
        <div class="col-lg-6 col-md-12">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">
                        <i class="ti ti-users me-2 text-info"></i>
                        أعضاء المجموعة
                    </h6>
                    <span class="badge bg-label-info rounded-pill">{{ $team->employees->count() }}</span>
                </div>
                <div class="card-body">
                    @if ($team->employees->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach ($team->employees as $employee)
                                <div class="list-group-item d-flex align-items-center px-0 border-0 mb-3">
                                    <div class="avatar avatar-sm me-3">
                                        <img src="{{ tasset($employee->image) }}" alt="{{ $employee->name }}"
                                            class="rounded-circle" style="width: 38px; height: 38px; object-fit: cover;">
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">{{ $employee->name }}</h6>
                                        <small class="text-muted">{{ $employee->email }}</small>
                                    </div>
                                    <span class="badge bg-label-info">
                                        <i class="ti ti-user ti-xs me-1"></i>عضو
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="ti ti-users ti-xl text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-0">لا يوجد أعضاء في هذه المجموعة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
