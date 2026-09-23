@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل الرسالة الفردية')

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
    $(document).ready(function() {
        $('#phone-numbers-table').DataTable({
            language: { url: "{{ asset('assets/json/ar.json') }}" },
            responsive: true,
            order: [],
            paging: false,
            searching: false,
            info: false
        });
    });
</script>
@endsection

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <div class="row text-center gy-3">
            <div class="col-md-4">
                <h6 class="text-muted fw-normal">حالة الرسالة</h6>
                <div class="border-top border-light border-dashed my-2"></div>
                <p class="fs-5 mb-0">
                    <span
                        class="badge bg-label-{{ $log->status == 'sent' ? 'success' : ($log->status == 'failed' ? 'danger' : 'warning') }}">
                        {{ match($log->status) {
                        'pending' => 'معلق',
                        'processing' => 'قيد المعالجة',
                        'sent' => 'تم الإرسال',
                        'failed' => 'فشل',
                        'cancelled' => 'ملغي',
                        default => $log->status
                        } }}
                    </span>
                </p>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted fw-normal">عدد الأرقام</h6>
                <div class="border-top border-light border-dashed my-2"></div>
                <p class="fs-5 mb-0">{{ $log->phone_numbers_count }}</p>
            </div>
            {{-- <div class="col-md-3">
                <h6 class="text-muted fw-normal">التكلفة</h6>
                <div class="border-top border-light border-dashed my-2"></div>
                <p class="fs-5 mb-0">{{ number_format($log->total_cost, 2) }} ريال</p>
            </div> --}}
            <div class="col-md-4">
                <h6 class="text-muted fw-normal">تاريخ الإرسال</h6>
                <div class="border-top border-light border-dashed my-2"></div>
                <p class="fs-5 mb-0">{{ $log->sent_at ? $log->sent_at->format('Y-m-d H:i') : 'لم يتم الإرسال' }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- معلومات العميل (العمود الأيسر) -->
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">معلومات العميل</h5>
                <span class="badge bg-label-primary">{{ $log->customer ? ($log->customer->customer_type == 'individual'
                    ? 'فردي' : 'شركة') : 'غير محدد' }}</span>
            </div>
            <div class="border-1 border-light border-dashed"></div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped table-bordered">
                        <tbody class="table-border-bottom-0">
                            <tr>
                                <td class="fw-medium">اسم العميل</td>
                                <td>{{ $log->customer_name }}</td>
                            </tr>
                            @if($log->company)
                            <tr>
                                <td class="fw-medium">الشركة</td>
                                <td>{{ $log->company->name }}</td>
                            </tr>
                            @endif
                            @if($log->portfolio)
                            <tr>
                                <td class="fw-medium">المحفظة</td>
                                <td>{{ $log->portfolio->name }}</td>
                            </tr>
                            @endif
                            @if($log->customer && $log->customer->identity_number)
                            <tr>
                                <td class="fw-medium">رقم الهوية</td>
                                <td>{{ $log->customer->identity_number }}</td>
                            </tr>
                            @endif
                            @if($log->sentBy)
                            <tr>
                                <td class="fw-medium">أرسل بواسطة</td>
                                <td>{{ $log->sentBy->name }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- أرقام الهاتف ومحتوى الرسالة (العمود الأيمن) -->
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">أرقام الهاتف ({{ $log->phone_numbers_count }})</h5>
            </div>
            <div class="border-1 border-light border-dashed"></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="phone-numbers-table" class="table table-bordered table-striped w-100">
                        <thead>
                            <tr>
                                <th>م</th>
                                <th>رقم الهاتف</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($log->phone_numbers && count($log->phone_numbers) > 0)
                            @foreach($log->phone_numbers as $index => $phone)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $phone }}</td>
                            </tr>
                            @endforeach
                            @else
                            <tr>
                                <td colspan="2" class="text-center py-4">لا توجد أرقام هاتف مسجلة</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- محتوى الرسالة -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">محتوى الرسالة</h5>
    </div>
    <div class="card-body">
        <div class="bg-white border rounded p-3 mb-3" style="white-space: pre-wrap; line-height: 1.6; direction: rtl;">
            {{ $log->message_content }}</div>

        <div class="row">
            <div class="col-md-6">
                <small class="text-muted">عدد الأحرف: {{ mb_strlen($log->message_content) }}</small>
            </div>
            <div class="col-md-6 text-end">
                <small class="text-muted">منصة الإرسال: {{ $log->platform }}</small>
            </div>
        </div>
    </div>
</div>
@endsection