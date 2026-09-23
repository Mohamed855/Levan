@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل طلب التحديث')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
])
@endsection

@section('page-script')
<script>
    $(function () {
    $('#changes-table').DataTable({
      language: { url: "{{ asset('assets/json/ar.json') }}" },
      responsive: true,
      pageLength: 10,
      order: [[0, 'asc']],
      columnDefs: [
        { width: "25%", targets: 0 },
        { width: "35%", targets: 1 },
        { width: "40%", targets: 2 }
      ]
    });
  });
</script>
@endsection

@section('content')
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">رقم الطلب</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $updateRequest->id }}</h4>
                        </div>
                        <small class="mb-0">{{ $updateRequest->created_at->format('d/m/Y') }}</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ti ti-file-description ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">حالة الطلب</span>
                        <div class="d-flex align-items-center my-1">
                            @php
                            $statusConfig = [
                            'pending' => ['class' => 'bg-warning', 'text' => 'معلق', 'icon' => 'ti-clock'],
                            'approved' => ['class' => 'bg-success', 'text' => 'معتمد', 'icon' => 'ti-check'],
                            'rejected' => ['class' => 'bg-danger', 'text' => 'مرفوض', 'icon' => 'ti-x']
                            ];
                            $status = $statusConfig[$updateRequest->status] ?? $statusConfig['pending'];
                            @endphp
                            <span class="badge  {{ $status['class'] }} fs-6">
                                <i class="ti {{ $status['icon'] }} me-1"></i>{{ $status['text'] }}
                            </span>
                        </div>
                        @if($updateRequest->approved_at)
                        <small class="mb-0">{{ $updateRequest->approved_at->format('d/m/Y') }}</small>
                        @else
                        <small class="mb-0">في انتظار المعالجة</small>
                        @endif
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded {{ str_replace('bg-', 'bg-label-', $status['class']) }}">
                            <i class="ti {{ $status['icon'] }} ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">العميل</span>
                        <div class="d-flex align-items-center my-1">
                            <h5 class="mb-0 me-2">{{ $updateRequest->customer?->name ?? 'غير محدد' }}</h5>
                        </div>
                        @if($updateRequest->customer)
                        <small class="mb-0">{{ $updateRequest->customer->identity_number ?? 'رقم هوية غير محدد'
                            }}</small>
                        @else
                        <small class="mb-0 text-muted">بيانات غير متاحة</small>
                        @endif
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="ti ti-user ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">مقدم الطلب</span>
                        <div class="d-flex align-items-center my-1">
                            <h5 class="mb-0 me-2">{{ $updateRequest->requestedBy?->name ?? 'غير محدد' }}</h5>
                        </div>
                        <small class="mb-0">{{ count($changes) }} {{ count($changes) === 1 ? 'تغيير' : 'تغييرات'
                            }}</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-user-check ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@if($updateRequest->rejection_reason)
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-danger d-flex align-items-start">
            <i class="ti ti-alert-circle me-3 mt-1"></i>
            <div>
                <h6 class="alert-heading mb-1">سبب الرفض</h6>
                <p class="mb-0">{{ $updateRequest->rejection_reason }}</p>
            </div>
        </div>
    </div>
</div>
@endif
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                @if(!empty($changes))
                <div class="table-responsive">
                    <table id="changes-table" class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">الحقل</th>
                                <th class="fw-bold">القيمة الحالية</th>
                                <th class="fw-bold">القيمة الجديدة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($changes as $field => $change)
                            @if($field === 'phones')
                            @foreach($change as $phoneChange)
                            <tr>
                                <td>
                                    <span class="badge bg-label-dark">رقم الهاتف</span>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge bg-label-secondary me-2 px-2 py-1"
                                            style="font-size: 0.7em;">قديم</span>
                                        <span class="text-muted">{{ $phoneChange['old'] ?: 'غير موجود' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge bg-label-success me-2 px-2 py-1"
                                            style="font-size: 0.7em;">جديد</span>
                                        <strong class="text-primary">{{ $phoneChange['new'] }}</strong>
                                        <i class="ti ti-phone-plus ms-2 text-success"></i>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                            @else
                            <tr>
                                <td>
                                    <span class="badge bg-label-dark">{{ $field }}</span>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge bg-label-secondary me-2 px-2 py-1"
                                            style="font-size: 0.7em;">قديم</span>
                                        <span class="text-muted">{{ $change['old'] ?: 'فارغ' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge bg-label-success me-2 px-2 py-1"
                                            style="font-size: 0.7em;">جديد</span>
                                        <strong class="text-primary">{{ $change['new'] ?: 'فارغ' }}</strong>
                                        <i class="ti ti-arrow-left ms-2 text-success"></i>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <div class="avatar avatar-xl mx-auto mb-3">
                        <span class="avatar-initial bg-label-secondary rounded">
                            <i class="ti ti-file-x ti-lg"></i>
                        </span>
                    </div>
                    <h6 class="mb-1">لا توجد تغييرات</h6>
                    <p class="text-muted mb-0">لم يتم العثور على أي تغييرات في هذا الطلب</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection