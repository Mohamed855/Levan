@extends('tenants.layouts.layoutMaster')

@section('title', 'خطابات الشركات')

{{-- ═══ Vendor Styles ═══ --}}
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

{{-- ═══ Vendor Scripts ═══ --}}
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

{{-- ═══ Toastr ═══ --}}
@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

{{-- ═══ Page Styles ═══ --}}
@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

{{-- ═══ Page Scripts ═══ --}}
@section('page-script')
    <script>
        window.routes = {
            companyLettersIndex: "{{ tenant_route_name('company-letters.index') }}",
            companyLettersShow: "{{ tenant_route_name('company-letters.show', ':id') }}",
        };
        window.assets = {
            arabicDataTable: "{{ asset('assets/json/ar.json') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>

    @vite(['resources/assets/js/tenants/business-center/customers/letters/index.js'])

    {!! $dataTable->scripts() !!}
@endsection

{{-- ═══ Page Content ═══ --}}
@section('content')

    {{-- ═══ الكروت الإحصائية ═══ --}}
    <div class="row g-4 mb-4">
        {{-- إجمالي الخطابات --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي الخطابات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $statistics['total'] }}</h4>
                            </div>
                            <small class="mb-0">جميع الخطابات</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-mail ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- جاهز للإرسال --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">جاهز للإرسال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $statistics['ready_to_send'] }}</h4>
                            </div>
                            <small class="mb-0">خطابات معلقة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-send ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- تم الإرسال --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">تم الإرسال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $statistics['sent'] }}</h4>
                            </div>
                            <small class="mb-0">خطابات مرسلة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- فشل الإرسال --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">فشل الإرسال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $statistics['failed'] }}</h4>
                            </div>
                            <small class="mb-0">خطابات فاشلة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-x ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-data-table.filters table="#company-letters-table">
        <div class="col-md-3">
            <x-filter.select
                id="filter-status"
                filterKey="status"
                table="#company-letters-table"
                name="status"
                label="الحالة"
                placeholder="اختر الحالة"
                :options="[
                    'ready_to_send' => 'جاهز للإرسال',
                    'approved_ready_to_send' => 'معتمد - جاهز للإرسال',
                    'sent' => 'تم الإرسال',
                    'failed' => 'فشل الإرسال'
                ]"
            />
        </div>

        <div class="col-md-3">
            <x-filter.select
                id="filter-company"
                filterKey="company_id"
                table="#company-letters-table"
                name="company_id"
                label="الشركة"
                placeholder="اختر الشركة"
                :options="$companies->pluck('name', 'id')"
            />
        </div>

        <div class="col-md-3">
            <label for="filter-portfolio" class="form-label">المحفظة</label>
            <select id="filter-portfolio" class="form-control select2" data-placeholder="اختر المحفظة" data-filter-key="portfolio_id" data-datatable-filter="true" data-datatable-target="#company-letters-table">
                <option value=""></option>
                @foreach ($portfolios as $portfolio)
                    <option value="{{ $portfolio->id }}" data-company="{{ $portfolio->company_id }}">
                        {{ $portfolio->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label for="filter-date-from" class="form-label">من تاريخ</label>
            <input type="date" id="filter-date-from" class="form-control" data-filter-key="date_from" data-datatable-filter="true" data-datatable-target="#company-letters-table">
        </div>
    </x-data-table.filters>

    {{-- ═══ جدول الخطابات ═══ --}}
    <div class="card">
        <div class="card-body">

            {{-- ═══ الجدول ═══ --}}
            <div class="table-responsive">
                {!! $dataTable->table([
                    'class' => 'table table-striped table-bordered w-100 text-center',
                    'id' => 'company-letters-table',
                ]) !!}
            </div>
        </div>
    </div>

@endsection
