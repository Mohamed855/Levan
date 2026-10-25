@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل الشركة - ' . $company->name)

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-building me-2 text-primary"></i>تفاصيل الشركة: {{ $company->name }}
            </h4>
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $company->status === 'active' ? 'bg-label-success' : 'bg-label-danger' }}">
                    {{ $company->status === 'active' ? 'نشط' : 'غير نشط' }}
                </span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('companies.edit')
                <a href="{{ tenant_route_name('companies.edit', $company->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل الشركة
                </a>
            @endcan
            <a href="{{ tenant_route_name('companies.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
        </div>
    </div>

    <div class="row">
        {{-- كارت معلومات الشركة (العمود الأيسر) --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3">
                        <img src="{{ $company->logo_path ? tasset($company->logo_path) : asset('static/brand/logos/logo.svg') }}"
                            alt="{{ $company->name }}" class="rounded" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid #e0e0e0;">
                    </div>
                    <h5 class="mb-1">{{ $company->name }}</h5>
                    <span class="badge {{ $company->status === 'active' ? 'bg-label-success' : 'bg-label-danger' }} mb-3">
                        {{ $company->status === 'active' ? 'نشط' : 'غير نشط' }}
                    </span>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0 text-primary">{{ $company->target_percentage ?? 0 }}%</h5>
                            <small class="text-muted">النسبة المستهدفة</small>
                        </div>
                        <div>
                            <h5 class="mb-0 text-info">{{ $authorizedCount }}</h5>
                            <small class="text-muted">المفوّضون</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <span class="fw-bold me-1">اسم الشركة:</span>
                                <span>{{ $company->name }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="fw-bold me-1">الحالة:</span>
                                <span>{{ $company->status === 'active' ? 'نشط' : 'غير نشط' }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="fw-bold me-1">تاريخ الإضافة:</span>
                                <span>{{ $company->created_at?->format('Y-m-d H:i') ?? '—' }}</span>
                            </li>
                            <li class="mb-0">
                                <span class="fw-bold me-1">آخر تحديث:</span>
                                <span>{{ $company->updated_at?->format('Y-m-d H:i') ?? '—' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- جدول المفوّضين (العمود الأيمن) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-users me-2 text-primary"></i>المفوّضون
                    </h5>
                    <span class="badge bg-label-info">الإجمالي: {{ $authorizedCount }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>الاسم</th>
                                <th>المنصب</th>
                                <th>رقم الهاتف</th>
                                <th>البريد الإلكتروني</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($company->authorizedPeople as $person)
                                <tr>
                                    <td class="fw-medium">
                                        <i class="ti ti-user text-info me-1"></i>{{ $person->name ?? '—' }}
                                    </td>
                                    <td>
                                        <span class="badge bg-label-primary">{{ $person->position ?? '—' }}</span>
                                    </td>
                                    <td>{{ $person->phone ?? '—' }}</td>
                                    <td>{{ $person->email ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="ti ti-users-minus me-1"></i> لا يوجد مفوّضون مسجلون لهذه الشركة
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
