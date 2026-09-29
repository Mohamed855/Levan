@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل عملية التوزيع')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
    <script>
        $(document).ready(function() {
            $('#customers-in-batch-table').DataTable({
                language: {
                    url: "{{ asset('assets/json/ar.json') }}"
                },
                responsive: true,
                order: []
            });
        });
    </script>
@endsection

@section('content')

    <!-- كارد الملخص العلوي -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row text-center gy-3">
                <div class="col-md-4">
                    <h6 class="text-muted fw-normal">طريقة التوزيع</h6>
                    <div class="border-top border-light border-dashed my-2"></div>
                    <p class="fs-5 mb-0">{{ $batchDetails['distribution_method_label'] ?? '-' }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted fw-normal">تاريخ الإنشاء</h6>
                    <div class="border-top border-light border-dashed my-2"></div>
                    <p class="fs-5 mb-0">
                        {{ isset($batchDetails['created_at'])
                            ? \Carbon\Carbon::parse($batchDetails['created_at'])->translatedFormat('d F Y')
                            : '-' }}
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted fw-normal">تمت بواسطة</h6>
                    <div class="border-top border-light border-dashed my-2"></div>
                    <p class="fs-5 mb-0">{{ $batchDetails['created_by'] ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- قائمة المحصلين (العمود الأيسر) -->
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">المحصلون</h5>
                    <span class="badge bg-label-primary">{{ count($batchDetails['collectors_details'] ?? []) }}</span>
                </div>
                <div class="border-1 border-light border-dashed"></div>
                <div class="card-body">
                    <div class="table-responsive text-nowrap">
                        {{-- **التعديل الاحترافي هنا: إضافة table-bordered** --}}
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>المحصل</th>
                                    @if (($batchDetails['distribution_method'] ?? '') === 'hybrid_balanced')
                                        <th class="text-center">العدد</th>
                                        <th class="text-center">المبلغ</th>
                                    @elseif(($batchDetails['distribution_method'] ?? '') === 'balanced_count')
                                        <th class="text-center">العدد</th>
                                    @elseif(($batchDetails['distribution_method'] ?? '') === 'balanced_amount')
                                        <th class="text-center">المبلغ</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @forelse ($batchDetails['collectors_details'] ?? [] as $collector)
                                    <tr>
                                        <td>{{ $collector['collector_name'] ?? 'غير محدد' }}</td>
                                        @if (($batchDetails['distribution_method'] ?? '') === 'hybrid_balanced')
                                            <td class="text-center"><span
                                                    class="badge bg-label-success">{{ $collector['customers_count'] ?? 0 }}</span>
                                            </td>
                                            <td class="text-center fw-bold">
                                                {{ number_format($collector['total_debt'] ?? 0, 2) }}
                                            </td>
                                        @elseif(($batchDetails['distribution_method'] ?? '') === 'balanced_count')
                                            <td class="text-center"><span
                                                    class="badge bg-label-success">{{ $collector['customers_count'] ?? 0 }}</span>
                                            </td>
                                        @elseif(($batchDetails['distribution_method'] ?? '') === 'balanced_amount')
                                            <td class="text-center fw-bold">
                                                {{ number_format($collector['total_debt'] ?? 0, 2) }}
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    @php
                                        $colspan = 2;
                                        if (($batchDetails['distribution_method'] ?? '') === 'hybrid_balanced') {
                                            $colspan = 3;
                                        }
                                    @endphp
                                    <tr>
                                        <td colspan="{{ $colspan }}" class="text-center py-3">لا يوجد محصلون.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- قائمة العملاء (العمود الأيمن) -->
        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">قائمة العملاء ({{ $customers->count() }})</h5>
                </div>
                <div class="border-1 border-light border-dashed"></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="customers-in-batch-table" class="table table-bordered table-striped w-100">
                            <thead>
                                <tr>
                                    <th>م</th>
                                    <th>اسم العميل</th>
                                    <th>الشركة</th>
                                    <th>المديونية</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($customers as $customer)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <a href="{{ tenant_route_name('customers.show', $customer->id) }}"
                                                target="_blank">{{ $customer->name }}</a>
                                        </td>
                                        <td>{{ $customer->company->name ?? '-' }}</td>
                                        <td>{{ number_format($customer->remaining_debt, 2) }} ريال</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">لا يوجد عملاء لعرضهم في هذه العملية.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
