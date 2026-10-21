@extends('layouts.dashboard')

@section('title', $company->name)
@section('dashboard-scope', 'tenant')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ti ti-building text-primary me-2"></i>
                {{ $company->name }}
            </h4>
            <p class="text-muted mb-0">عرض تفاصيل الشركة والمفوضين المعتمدين.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('tenant.companies.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة للقائمة
            </a>
            @can('companies.edit')
                <a href="{{ route('tenant.companies.edit', $company->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل الشركة
                </a>
            @endcan
        </div>
    </div>

    <div class="row">
        {{-- العمود الأيسر: كرت الملف التعريفي للشركة (Rule 7) --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center pt-4">
                    <div class="mx-auto mb-3" style="width: 100px; height: 100px;">
                        @if ($company->logo_path)
                            <img src="{{ asset('storage/'.$company->logo_path) }}" alt="{{ $company->name }}" class="rounded-circle border p-1 w-100 h-100" style="object-fit: contain;">
                        @else
                            <div class="rounded-circle bg-label-primary d-flex align-items-center justify-content-center w-100 h-100">
                                <i class="ti ti-building fs-1"></i>
                            </div>
                        @endif
                    </div>
                    <h5 class="mb-1 text-heading">{{ $company->name }}</h5>
                    <span class="badge {{ $company->status === 'active' ? 'bg-label-success' : 'bg-label-danger' }} mb-3">
                        {{ $company->status === 'active' ? 'شركة نشطة' : 'شركة معطلة' }}
                    </span>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0">{{ count($company->authorizedPeople) }}</h5>
                            <small class="text-muted">المفوضون</small>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $company->created_at?->format('Y-m') }}</h5>
                            <small class="text-muted">تاريخ التسجيل</small>
                        </div>
                    </div>

                    <div class="text-start mt-4">
                        <small class="text-muted text-uppercase d-block mb-3 fw-bold">معلومات الشركة</small>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex align-items-center mb-3">
                                <i class="ti ti-hash me-2 text-primary"></i>
                                <span class="fw-medium me-2">رقم المعرف:</span>
                                <span class="font-monospace text-muted">#{{ $company->id }}</span>
                            </li>
                            <li class="d-flex align-items-center mb-3">
                                <i class="ti ti-calendar me-2 text-primary"></i>
                                <span class="fw-medium me-2">تاريخ الإضافة:</span>
                                <span>{{ $company->created_at?->format('Y-m-d H:i') ?? '-' }}</span>
                            </li>
                            <li class="d-flex align-items-center">
                                <i class="ti ti-clock me-2 text-primary"></i>
                                <span class="fw-medium me-2">آخر تحديث:</span>
                                <span>{{ $company->updated_at?->format('Y-m-d H:i') ?? '-' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- العمود الأيمن: الجداول الفرعية والمفوضون (Rule 7) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            <div class="card mb-4 shadow-sm">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-users text-primary me-2"></i>
                        المفوضون الرسميون المعتمدون
                    </h5>
                    <span class="badge bg-label-info">{{ count($company->authorizedPeople) }} مفوض</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>اسم المفوض</th>
                                <th>المنصب / الصفة</th>
                                <th>رقم الجوال</th>
                                <th>البريد الإلكتروني</th>
                                <th class="text-center">الصفة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($company->authorizedPeople as $person)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-xs bg-label-secondary rounded-circle">
                                                <i class="ti ti-user"></i>
                                            </span>
                                            <span class="fw-bold">{{ $person->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $person->position ?: '-' }}</td>
                                    <td>
                                        @if ($person->phone)
                                            <a href="tel:{{ $person->phone }}" class="font-monospace text-primary" dir="ltr">{{ $person->phone }}</a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($person->email)
                                            <a href="mailto:{{ $person->email }}" class="font-monospace text-muted">{{ $person->email }}</a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($person->is_default)
                                            <span class="badge bg-label-success">المفوض الرئيسي</span>
                                        @else
                                            <span class="badge bg-label-secondary">مفوض</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        لا يوجد مفوضون مضافون لهذه الشركة حتى الآن.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
