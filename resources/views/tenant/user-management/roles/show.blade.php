@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل الدور - ' . \App\Support\AccessLabels::role($role->name))

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-shield-lock me-2 text-primary"></i>تفاصيل الدور:
                {{ \App\Support\AccessLabels::role($role->name) }}
            </h4>
            <div class="d-flex align-items-center gap-2">
                <code class="copyable-code fs-6"
                    onclick="navigator.clipboard.writeText('{{ \App\Support\AccessLabels::role($role->name) }}'); toastr.success('تم نسخ اسم الدور');">
                    {{ \App\Support\AccessLabels::role($role->name) }}
                </code>
                <span class="badge bg-info">{{ \App\Support\AccessLabels::guard($role->guard_name) }}</span>
                <span class="badge bg-success">{{ $roleData['users_count'] ?? $role->users->count() }} مستخدم</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('roles.edit')
                <a href="{{ tenant_route_name('roles.edit', $role->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل الدور
                </a>
            @endcan
            <a href="{{ tenant_route_name('roles.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
        </div>
    </div>

    <div class="row">
        {{-- كارت معلومات الدور (العمود الأيسر) --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center bg-primary rounded"
                        style="width: 100px; height: 100px;">
                        <i class="ti ti-shield-check text-white" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="mb-1">{{ \App\Support\AccessLabels::role($role->name) }}</h5>
                    <span class="badge bg-primary mb-3">حارس النظام:
                        {{ \App\Support\AccessLabels::guard($role->guard_name) }}</span>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0 text-primary">{{ $roleData['users_count'] ?? $role->users->count() }}</h5>
                            <small class="text-muted">المستخدمون</small>
                        </div>
                        <div>
                            <h5 class="mb-0 text-success">
                                {{ $roleData['permissions_count'] ?? $role->permissions->count() }}</h5>
                            <small class="text-muted">الصلاحيات</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <span class="fw-bold me-1">اسم الدور:</span>
                                <span>{{ \App\Support\AccessLabels::role($role->name) }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="fw-bold me-1">نطاق الحارس:</span>
                                <span>{{ \App\Support\AccessLabels::guard($role->guard_name) }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="fw-bold me-1">تاريخ الإنشاء:</span>
                                <span>{{ $role->created_at?->format('Y-m-d') ?? '-' }}</span>
                            </li>
                            <li class="mb-0">
                                <span class="fw-bold me-1">آخر تحديث:</span>
                                <span>{{ $role->updated_at?->format('Y-m-d') ?? '-' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- الجداول المرتبطة (العمود الأيمن) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- جدول المستخدمين المسندين للدور --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">المستخدمون المسندون لهذا الدور ({{ $role->users->count() }})</h5>
                    @can('users.create')
                        <a href="{{ tenant_route_name('users.create') }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-plus me-1"></i> إضافة مستخدم
                        </a>
                    @endcan
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>المستخدم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الحالة</th>
                                <th>تاريخ الإضافة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($role->users as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $user->image ? tasset($user->image) : asset('static/brand/logos/logo.svg') }}"
                                                alt="{{ $user->name }}" class="rounded-circle"
                                                style="width: 32px; height: 32px; object-fit: cover;">
                                            <a href="{{ tenant_route_name('users.show', $user->id) }}"
                                                class="fw-medium text-body">
                                                {{ $user->name }}
                                            </a>
                                        </div>
                                    </td>
                                    <td><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></td>
                                    <td>
                                        <span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $user->status === 'active' ? 'نشط' : 'غير نشط' }}
                                        </span>
                                    </td>
                                    <td>{{ $user->created_at?->format('Y-m-d') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">لا يوجد مستخدمون مسندون لهذا
                                        الدور حتى الآن.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- قائمة الصلاحيات الممنوحة --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">الصلاحيات الممنوحة لهذا الدور ({{ $role->permissions->count() }})</h5>
                </div>
                <div class="card-body">
                    @if ($role->permissions->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($role->permissions as $permission)
                                <span class="badge bg-primary fs-6 py-2 px-3">
                                    <i class="ti ti-check me-1"></i>
                                    {{ \App\Support\AccessLabels::permission($permission->name) }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">لا توجد أي صلاحيات ممنوحة لهذا الدور حالياً.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
