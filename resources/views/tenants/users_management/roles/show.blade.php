@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل الدور')

@section('content')
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">تفاصيل الدور: {{ $role->name }}</h5>
                    <div>
                        @can('roles.edit')
                            <a href="{{ tenant_route_name('roles.edit', $role->id) }}" class="btn btn-primary btn-sm">
                                <i class="ti ti-edit me-1"></i> تعديل
                            </a>
                        @endcan
                        <a href="{{ tenant_route_name('roles.index') }}" class="btn btn-secondary btn-sm">
                            <i class="ti ti-arrow-right me-1"></i> العودة للقائمة
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <div class="text-muted small">اسم الدور</div>
                                <div class="fs-5 fw-bold text-primary">{{ $role->name }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <div class="text-muted small">عدد الصلاحيات الممنوحة</div>
                                <div class="fs-5 fw-bold text-success">
                                    {{ $roleData['permissions_count'] ?? $role->permissions->count() }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded">
                                <div class="text-muted small">عدد المستخدمين المرتبطين</div>
                                <div class="fs-5 fw-bold text-info">{{ $roleData['users_count'] ?? $role->users->count() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-semibold mb-3">الصلاحيات المخصصة لهذا الدور:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @forelse ($role->permissions as $permission)
                            <span
                                class="badge bg-label-primary fs-6">{{ trans()->has('permissions.' . $permission->name) ? __('permissions.' . $permission->name) : $permission->name }}</span>
                        @empty
                            <p class="text-muted">لا توجد صلاحيات مخصصة لهذا الدور حالياً.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
